<?php
declare(strict_types=1);

// verifier-context: standalone-template
// (The pre-commit hook only auto-runs a verifier carrying this marker. Repos with
//  a foreign/embedded verify-operator-docs.php — different repoRoot math — are left
//  alone. Same convention as scripts/verify-features.php; see ../.githooks/pre-commit's
//  "Operator-docs verifier" section.)
//
// Operator-docs gate — docs/knowledge/operator.md is the one place an operator (not a
// builder) looks for who owns a repo, what accounts/services it depends on, its cron
// jobs, and its admin panel. This verifier is PRESENCE / CO-MODIFICATION only — it never
// inspects operator.md's prose for correctness (deliberate: content is a human's job).
// It enforces:
//   1. docs/knowledge/operator.md exists in the repo at all.
//   2. A staged cron script (bin/*.php, incl. nested/vendored) travels with a staged
//      ops/crons.manifest.
//   3. A staged config.example.php travels with a staged operator.md.
//   4. A staged admin-panel file travels with a staged operator.md.
//   5. Any TRACKED cron script (not just staged ones) means ops/crons.manifest must
//      exist at all, independent of what this particular commit touches.
// Plus one content check, scoped ONLY to operator.md's own staged diff: an entropy scan
// that blocks a real secret value from landing in the one doc file every repo's
// docs/knowledge/ keeps around for operators.
//
// Usage:
//   php scripts/verify-operator-docs.php             Run all checks against the staged commit
//   php scripts/verify-operator-docs.php --selftest   Pure-fixture + temp-dir git checks, no live repo needed

$repoRoot = realpath(__DIR__ . '/..');
if ($repoRoot === false) {
    fwrite(STDERR, "ERROR: cannot resolve repo root from " . __DIR__ . "\n");
    exit(2);
}

// ── Globs — explicit, commented, not inferred/dynamic ───────────────────────

// Cron-script glob: bin/<name>.php at the repo root AND nested/vendored (e.g.
// crm/bin/<name>.php) — case-insensitive. Presence-only by design: it never asks
// whether a given bin/*.php script IS actually wired to a real cron (rule 5's
// existence-gate and rule 2's co-stage check both key off this same glob, already
// decided at spec time) — a harness file like bin/pair-device.php matches too, and
// that is the deliberate, already-decided tradeoff, not a bug to narrow later.
const VOD_CRON_SCRIPT_GLOB = '#(^|/)bin/[^/]+\.php$#i';

// Admin-controller glob (case-insensitive). Two shapes, BOTH observed in the same
// real repo (crm-platform, checked read-only 2026-08-01):
//   (a) directory-scoped:  controllers/admin/BookingController.php, CampaignController.php,
//                          KbController.php, ReviewController.php
//   (b) prefix-scoped:     controllers/web/AdminController.php,
//                          controllers/api/AdminClientController.php,
//                          AdminCatalogController.php, AdminReportController.php, ...
// Deliberately does NOT match middleware/AdminAuth.php (no Controller.php suffix — a
// middleware class, not a controller) or CrossWeb's Joomla site/administrator/**
// tree (checked read-only 2026-08-01): "administrator" fails the [/_-] boundary
// right after "admin" on purpose, and none of its filenames are Admin-prefixed —
// so the glob doesn't fire on every file under a Joomla admin tree.
const VOD_ADMIN_CONTROLLER_GLOB = '#(^|/)admin[/_-][^/]*Controller\.php$|(^|/)Admin[A-Za-z0-9]*Controller\.php$#i';

// Candidate-token charset mirrored from the factory's existing crontab-leak-masking
// pattern (factory/knowledge/hardening-patterns.md, "Redact secrets by ENTROPY, not by
// key name"): any run of 20+ chars from the base64/hex/token charset, no whitespace.
// That source pattern stops here deliberately — it feeds a REDACTION (over-masking a
// long non-secret string in a report is harmless), not a hard commit gate. Here a false
// positive BLOCKS a commit, so this charset glob is only the CANDIDATE filter; a real
// Shannon-entropy check (below) decides which candidates actually look random rather
// than being an ordinary long path/URL/hyphenated phrase (those repeat characters and
// separators, which pulls entropy well below a real secret's).
const VOD_ENTROPY_TOKEN_GLOB = '#[A-Za-z0-9+/_=-]{20,}#';

// Bits/char below which a charset-matching token is natural-language/path-shaped, not a
// secret. Measured 2026-08-01 against real fixtures: hyphenated prose and repo paths
// topped out at 4.24 bits/char (digit-bearing prose) even after excluding path-shaped
// tokens below; random base64/mixed tokens of the same length ran 4.26-4.75, and the
// original secret-shaped fixture hit 5.18. 4.3 sits just above the highest observed
// prose/path sample. KNOWN GAP: a pure-hex secret (charset of only 16 symbols) caps
// out near 4.0 bits/char even fully random, so entropy alone under-catches it — the
// separate pure-hex structural check below exists specifically to close that gap
// without relying on entropy for that shape.
const VOD_ENTROPY_BITS_THRESHOLD = 4.3;

// Minimum '/' count at which a charset-matching token is treated as a filesystem/URL
// path rather than a candidate secret — real secrets essentially never contain two or
// more literal slashes (base64's alphabet includes '/' but a 20+ char random run
// containing 2+ of them by chance is a documented near-zero-probability edge, and this
// factory's operator.md convention never puts a secret value in prose anyway).
const VOD_PATH_SLASH_COUNT_EXCLUDED = 2;

/**
 * Shannon entropy in bits/char over $token's byte distribution. Pure math, no I/O.
 */
function vod_shannon_entropy(string $token): float
{
    $len = strlen($token);
    if ($len === 0) {
        return 0.0;
    }
    $counts = [];
    for ($i = 0; $i < $len; $i++) {
        $b = $token[$i];
        $counts[$b] = ($counts[$b] ?? 0) + 1;
    }
    $entropy = 0.0;
    foreach ($counts as $count) {
        $p = $count / $len;
        $entropy -= $p * log($p, 2);
    }
    return $entropy;
}

// ── Pure checkers — no I/O, --selftest exercises these directly ─────────────

/**
 * @param string[] $files
 * @return string[] entries of $files matching $pattern
 */
function vod_matches(array $files, string $pattern): array
{
    return array_values(array_filter($files, static fn(string $f): bool => preg_match($pattern, $f) === 1));
}

/**
 * Rule 1: docs/knowledge/operator.md must exist (file-exists check, independent of
 * what this commit stages).
 * @return string[] error lines (empty = pass)
 */
function vod_check_doc_exists(bool $exists): array
{
    if ($exists) {
        return [];
    }
    return [
        'docs/knowledge/operator.md is missing.',
        'Fix:',
        '  mkdir -p docs/knowledge',
        '  cp D:\\SwensBuildEnv\\factory\\template\\docs\\knowledge\\operator.md docs\\knowledge\\operator.md',
        'Then fill in Owner / Accounts & services / Cron jobs / Admin panel and stage it.',
    ];
}

/**
 * Rule 2: a staged cron script requires ops/crons.manifest staged in the same commit.
 * @param string[] $stagedFiles
 * @return string[] error lines (empty = pass)
 */
function vod_check_cron_needs_manifest(array $stagedFiles): array
{
    $hits = vod_matches($stagedFiles, VOD_CRON_SCRIPT_GLOB);
    if ($hits === []) {
        return [];
    }
    if (in_array('ops/crons.manifest', $stagedFiles, true)) {
        return [];
    }
    return array_merge(
        ['cron script(s) staged without ops/crons.manifest:'],
        array_map(static fn(string $f): string => "  $f", $hits),
        ['Fix: stage the manifest alongside it:', '  git add ops/crons.manifest']
    );
}

/**
 * Rule 3: config.example.php staged at all requires docs/knowledge/operator.md staged too.
 * @param string[] $stagedFiles
 * @return string[] error lines (empty = pass)
 */
function vod_check_config_example_needs_doc(array $stagedFiles): array
{
    if (!in_array('config.example.php', $stagedFiles, true)) {
        return [];
    }
    if (in_array('docs/knowledge/operator.md', $stagedFiles, true)) {
        return [];
    }
    return [
        'config.example.php is staged but docs/knowledge/operator.md is not.',
        'Fix: stage the operator doc alongside the config change:',
        '  git add docs/knowledge/operator.md',
    ];
}

/**
 * Rule 4: a staged admin-controller file requires docs/knowledge/operator.md staged too.
 * @param string[] $stagedFiles
 * @return string[] error lines (empty = pass)
 */
function vod_check_admin_controller_needs_doc(array $stagedFiles): array
{
    $hits = vod_matches($stagedFiles, VOD_ADMIN_CONTROLLER_GLOB);
    if ($hits === []) {
        return [];
    }
    if (in_array('docs/knowledge/operator.md', $stagedFiles, true)) {
        return [];
    }
    return array_merge(
        ['admin-panel file(s) staged without docs/knowledge/operator.md:'],
        array_map(static fn(string $f): string => "  $f", $hits),
        ['Fix: stage the operator doc alongside the admin-panel change:', '  git add docs/knowledge/operator.md']
    );
}

/**
 * Rule 5: any TRACKED cron script means ops/crons.manifest must exist at all
 * (file-exists check, independent of what this commit stages).
 * @param string[] $trackedFiles
 * @return string[] error lines (empty = pass)
 */
function vod_check_manifest_exists_when_cron_tracked(array $trackedFiles, bool $manifestExists): array
{
    $hits = vod_matches($trackedFiles, VOD_CRON_SCRIPT_GLOB);
    if ($hits === []) {
        return [];
    }
    if ($manifestExists) {
        return [];
    }
    return array_merge(
        ['cron script(s) exist in the repo but ops/crons.manifest is missing:'],
        array_map(static fn(string $f): string => "  $f", $hits),
        [
            'Fix:',
            '  mkdir -p ops',
            '  printf "# cron-expression | path-or-URL | purpose | state | owning-repo\n" > ops/crons.manifest',
            '  git add ops/crons.manifest',
        ]
    );
}

/**
 * Entropy scan over ONLY the ADDED lines of operator.md's staged diff (never the whole
 * file — an already-committed secret from before this gate existed doesn't retroactively
 * block an unrelated later commit).
 * @return string[] error lines (empty = pass)
 */
function vod_check_operator_doc_secrets(string $diffText): array
{
    foreach (preg_split('/\R/', $diffText) ?: [] as $line) {
        if ($line === '' || $line[0] !== '+' || str_starts_with($line, '+++')) {
            continue;
        }
        if (preg_match_all(VOD_ENTROPY_TOKEN_GLOB, substr($line, 1), $m) === false || !$m[0]) {
            continue;
        }
        foreach ($m[0] as $token) {
            if (substr_count($token, '/') >= VOD_PATH_SLASH_COUNT_EXCLUDED) {
                continue; // path-shaped, not a secret candidate
            }
            // Pure-hex structural check (see VOD_ENTROPY_BITS_THRESHOLD comment): a hex
            // secret's own alphabet caps its entropy near the threshold, so a token using
            // ONLY hex digits is flagged on shape alone — real text essentially never runs
            // 20+ chars using nothing but 0-9a-f.
            $isPureHex = preg_match('/^[0-9a-fA-F]+$/', $token) === 1;
            if ($isPureHex || vod_shannon_entropy($token) >= VOD_ENTROPY_BITS_THRESHOLD) {
                return [
                    'docs/knowledge/operator.md contains a high-entropy token that looks like a secret value.',
                    'Fix: operator.md must never contain secret values — replace with a vendor/service NAME',
                    'and a pointer to config.example.php.',
                ];
            }
        }
    }
    return [];
}

// ── Git I/O helpers (thin — everything else above is pure) ──────────────────

/** @return string[]|null null on git failure (structural — caller exits 2) */
function vod_git_staged_files(string $repoRoot): ?array
{
    $out = [];
    $rc = 0;
    exec('git -C ' . escapeshellarg($repoRoot) . ' diff --cached --name-only --diff-filter=ACM 2>'
        . (PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null'), $out, $rc);
    return $rc === 0 ? $out : null;
}

/** @return string[]|null null on git failure (structural — caller exits 2) */
function vod_git_tracked_files(string $repoRoot): ?array
{
    $out = [];
    $rc = 0;
    exec('git -C ' . escapeshellarg($repoRoot) . ' ls-files 2>'
        . (PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null'), $out, $rc);
    return $rc === 0 ? $out : null;
}

/** @return string|null null on git failure (structural — caller exits 2) */
function vod_git_staged_diff(string $repoRoot, string $relPath): ?string
{
    $out = [];
    $rc = 0;
    exec('git -C ' . escapeshellarg($repoRoot) . ' diff --cached -- ' . escapeshellarg($relPath) . ' 2>'
        . (PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null'), $out, $rc);
    return $rc === 0 ? implode("\n", $out) : null;
}

/** Recursive rmdir — no shell `rm -rf` dependency (Windows-safe for git's read-only objects). */
function vod_rmtree(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir . '/' . $entry;
        if (is_dir($path) && !is_link($path)) {
            vod_rmtree($path);
        } else {
            @chmod($path, 0666);
            @unlink($path);
        }
    }
    @rmdir($dir);
}

// ── --selftest: pure fixtures for every checker, plus a temp-dir git smoke test ──

function vod_run_selftest(): int
{
    $fail = 0;
    $assert = function (bool $cond, string $label) use (&$fail): void {
        printf("  %s  %s\n", $cond ? 'PASS' : 'FAIL', $label);
        if (!$cond) {
            $fail++;
        }
    };

    // Rule 1
    $assert(vod_check_doc_exists(true) === [], 'rule1: doc exists -> pass');
    $assert(vod_check_doc_exists(false) !== [], 'rule1: doc missing -> fail');

    // Rule 2
    $assert(vod_check_cron_needs_manifest(['bin/foo.php']) !== [], 'rule2: cron staged w/o manifest -> fail');
    $assert(vod_check_cron_needs_manifest(['bin/foo.php', 'ops/crons.manifest']) === [], 'rule2: cron staged w/ manifest -> pass');
    $assert(vod_check_cron_needs_manifest(['crm/bin/foo.php']) !== [], 'rule2: nested bin/ cron staged w/o manifest -> fail');
    $assert(vod_check_cron_needs_manifest(['BIN/Foo.PHP']) !== [], 'rule2: case-insensitive match -> fail');
    $assert(vod_check_cron_needs_manifest(['bin/pair-device.php']) !== [], 'rule2: harness-safe bin script still gated (deliberate, per spec)');
    $assert(vod_check_cron_needs_manifest(['controllers/BinController.php']) === [], 'rule2: BinController.php is not bin/*.php -> pass (no false hit on the word "bin")');
    $assert(vod_check_cron_needs_manifest(['tests/foo.php']) === [], 'rule2: unrelated staged file -> pass');

    // Rule 3
    $assert(vod_check_config_example_needs_doc(['config.example.php']) !== [], 'rule3: config staged w/o doc -> fail');
    $assert(vod_check_config_example_needs_doc(['config.example.php', 'docs/knowledge/operator.md']) === [], 'rule3: config staged w/ doc -> pass');
    $assert(vod_check_config_example_needs_doc(['core/App.php']) === [], 'rule3: unrelated staged file -> pass');

    // Rule 4
    $assert(vod_check_admin_controller_needs_doc(['controllers/web/AdminController.php']) !== [], 'rule4: bare AdminController.php staged w/o doc -> fail');
    $assert(vod_check_admin_controller_needs_doc(['controllers/api/AdminClientController.php']) !== [], 'rule4: prefixed Admin*Controller.php staged w/o doc -> fail');
    $assert(vod_check_admin_controller_needs_doc(['controllers/admin/BookingController.php']) !== [], 'rule4: directory-scoped admin/*Controller.php staged w/o doc -> fail');
    $assert(vod_check_admin_controller_needs_doc(['controllers/web/AdminController.php', 'docs/knowledge/operator.md']) === [], 'rule4: admin controller staged w/ doc -> pass');
    $assert(vod_check_admin_controller_needs_doc(['middleware/AdminAuth.php']) === [], 'rule4: AdminAuth.php (not *Controller.php) -> pass, deliberately excluded');
    $assert(vod_check_admin_controller_needs_doc(['site/administrator/components/com_x/src/Controller/DisplayController.php']) === [], 'rule4: Joomla site/administrator/ tree -> pass, deliberately excluded (CrossWeb-validated)');

    // Rule 5
    $assert(vod_check_manifest_exists_when_cron_tracked(['bin/foo.php'], false) !== [], 'rule5: cron tracked, manifest missing -> fail');
    $assert(vod_check_manifest_exists_when_cron_tracked(['bin/foo.php'], true) === [], 'rule5: cron tracked, manifest exists -> pass');
    $assert(vod_check_manifest_exists_when_cron_tracked(['core/App.php'], false) === [], 'rule5: no cron tracked -> pass regardless of manifest');

    // Secret scan
    $secretDiff = "diff --git a/docs/knowledge/operator.md b/docs/knowledge/operator.md\n"
        . "+++ b/docs/knowledge/operator.md\n"
        . "+## Accounts & services\n"
        . "+API key: sk-abcdefghijklmnopqrstuvwxyz0123456789\n";
    $cleanDiff = "diff --git a/docs/knowledge/operator.md b/docs/knowledge/operator.md\n"
        . "+++ b/docs/knowledge/operator.md\n"
        . "+## Accounts & services\n"
        . "+OpenRouter -- see config.example.php AI_API_KEY\n";
    $removedOnlyDiff = "diff --git a/docs/knowledge/operator.md b/docs/knowledge/operator.md\n"
        . "-sk-abcdefghijklmnopqrstuvwxyz0123456789\n";
    // Real false positive hit 2026-08-01 (Selvatec-Website content pass): a long repo
    // path and a long hyphenated sentence both matched the charset/length glob but are
    // not secrets — repeated '/', '-', and common letters keep their entropy well under
    // the threshold, unlike a real token where each char is close to equally likely.
    $pathDiff = "diff --git a/docs/knowledge/operator.md b/docs/knowledge/operator.md\n"
        . "+++ b/docs/knowledge/operator.md\n"
        . "+Cron scripts live at /home/selvatec/htdocs/selvatec.ca/current/bin/backup-database-nightly.php\n";
    $hyphenDiff = "diff --git a/docs/knowledge/operator.md b/docs/knowledge/operator.md\n"
        . "+++ b/docs/knowledge/operator.md\n"
        . "+See migration-054-applied-2026-07-14-pre-dump-taken-before-run for context\n";
    // Pure-hex secret: entropy alone caps near this token's own alphabet size (16 symbols
    // -> ~4.0 bits/char max), so this specifically exercises the structural hex check,
    // not the entropy threshold.
    $hexDiff = "diff --git a/docs/knowledge/operator.md b/docs/knowledge/operator.md\n"
        . "+++ b/docs/knowledge/operator.md\n"
        . "+webhook secret: 4f9a2c8e1b6d3f70a5c9e2b8f1d4a6c709b3e5f2\n";
    $assert(vod_check_operator_doc_secrets($secretDiff) !== [], 'secret-scan: high-entropy token on an added line -> fail');
    $assert(vod_check_operator_doc_secrets($cleanDiff) === [], 'secret-scan: plain-prose added line -> pass');
    $assert(vod_check_operator_doc_secrets($removedOnlyDiff) === [], 'secret-scan: token only on a REMOVED line -> pass (not a new leak)');
    $assert(vod_check_operator_doc_secrets($pathDiff) === [], 'secret-scan: long repo path (2+ slashes) -> pass');
    $assert(vod_check_operator_doc_secrets($hyphenDiff) === [], 'secret-scan: long hyphenated prose sentence -> pass');
    $assert(vod_check_operator_doc_secrets($hexDiff) !== [], 'secret-scan: pure-hex secret-length token -> fail (structural check, not entropy)');

    // --- temp-dir git integration smoke test: the I/O helpers against a real scratch repo ---
    $tmp = sys_get_temp_dir() . '/vod-selftest-' . bin2hex(random_bytes(4));
    $made = @mkdir($tmp, 0777, true);
    if ($made) {
        exec('git -C ' . escapeshellarg($tmp) . ' init -q');
        exec('git -C ' . escapeshellarg($tmp) . ' config user.email vod-selftest@example.invalid');
        exec('git -C ' . escapeshellarg($tmp) . ' config user.name vod-selftest');
        @mkdir($tmp . '/bin', 0777, true);
        @mkdir($tmp . '/docs/knowledge', 0777, true);
        file_put_contents($tmp . '/bin/nightly.php', "<?php\n");
        file_put_contents($tmp . '/docs/knowledge/operator.md', "## Owner\n");
        exec('git -C ' . escapeshellarg($tmp) . ' add bin/nightly.php docs/knowledge/operator.md');

        $staged = vod_git_staged_files($tmp);
        $assert(
            is_array($staged) && in_array('bin/nightly.php', $staged, true) && in_array('docs/knowledge/operator.md', $staged, true),
            'git-io: staged-files helper sees a real staged commit'
        );

        $tracked = vod_git_tracked_files($tmp);
        // A staged-but-not-yet-committed file IS reported by `git ls-files` (it reads the
        // index, not HEAD) — this is the real behavior rule 5 relies on: a cron script
        // staged in THIS commit already counts as "tracked" for the existence gate.
        $assert(
            is_array($tracked) && in_array('bin/nightly.php', $tracked, true),
            'git-io: tracked-files helper sees a staged-not-yet-committed file (index, not HEAD)'
        );

        $diff = vod_git_staged_diff($tmp, 'docs/knowledge/operator.md');
        $assert(is_string($diff) && str_contains($diff, '+## Owner'), 'git-io: staged-diff helper returns the real added-line content');

        vod_rmtree($tmp);
    } else {
        $assert(false, 'git-io: could not create scratch temp dir — smoke test skipped as a FAILURE, not silently green');
    }

    echo $fail === 0 ? "\nselftest: all checks pass\n" : "\nselftest: $fail FAILURE(S)\n";
    return $fail === 0 ? 0 : 1;
}

// ── CLI entry ─────────────────────────────────────────────────────────────

if (in_array('--selftest', array_slice($argv, 1), true)) {
    exit(vod_run_selftest());
}

$stagedFiles = vod_git_staged_files($repoRoot);
if ($stagedFiles === null) {
    fwrite(STDERR, "ERROR: git diff --cached failed — is $repoRoot a git repository?\n");
    exit(2);
}
$trackedFiles = vod_git_tracked_files($repoRoot);
if ($trackedFiles === null) {
    fwrite(STDERR, "ERROR: git ls-files failed — is $repoRoot a git repository?\n");
    exit(2);
}

$docExists = file_exists($repoRoot . '/docs/knowledge/operator.md');
$manifestExists = file_exists($repoRoot . '/ops/crons.manifest');

$checks = [
    'operator-doc-exists' => vod_check_doc_exists($docExists),
    'cron-script-needs-manifest' => vod_check_cron_needs_manifest($stagedFiles),
    'config-example-needs-operator-doc' => vod_check_config_example_needs_doc($stagedFiles),
    'admin-controller-needs-operator-doc' => vod_check_admin_controller_needs_doc($stagedFiles),
    'crons-manifest-exists-gate' => vod_check_manifest_exists_when_cron_tracked($trackedFiles, $manifestExists),
];

// The secret scan only runs when operator.md is actually part of THIS staged diff —
// no reason to re-diff a file nothing touched.
if (in_array('docs/knowledge/operator.md', $stagedFiles, true)) {
    $diff = vod_git_staged_diff($repoRoot, 'docs/knowledge/operator.md');
    if ($diff === null) {
        fwrite(STDERR, "ERROR: git diff --cached -- docs/knowledge/operator.md failed\n");
        exit(2);
    }
    $checks['operator-doc-secret-scan'] = vod_check_operator_doc_secrets($diff);
}

$totalFail = 0;
foreach ($checks as $id => $errors) {
    if ($errors === []) {
        echo "[PASS] $id\n";
    } else {
        echo "[FAIL] $id\n";
        foreach ($errors as $line) {
            echo "         $line\n";
        }
        $totalFail++;
    }
}

echo "\n" . (count($checks) - $totalFail) . " passed, $totalFail failed (" . count($checks) . " checks)\n";
exit($totalFail > 0 ? 1 : 0);
