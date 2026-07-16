<?php

declare(strict_types=1);

/*
 * route_handler_test.php — route-table <-> handler coherence (template-owned, synced).
 *
 * Every [Class::class, 'method'] pair declared in routes.php must resolve to a real
 * public instance method — Router::dispatch() runs (new $class())->$fn(...), so a
 * route committed without its handler is a fatal on first dispatch, not a test-time
 * error. The incident this pins (Marquee 2026-07-16): on Windows, `git add
 * controllers/Web/...` (capital W, index tracked lowercase web/) silently staged
 * NOTHING and the first commit shipped a route without its handler class. Handlers
 * can't be invoked in-process (Response::redirect() is `never` and exits), so the
 * check is static: parse routes.php, resolve each short class name through its
 * use-statements, then reflect. Pattern adopted from Marquee's
 * tests/root_route_test.php, which keeps its stricter repo-specific assertions
 * (apex route, favicon) on top of this generic one.
 *
 * Layout-aware (flat root, app/, crm/ — same candidates as tests/run.php) and
 * loud-skip: a repo with no routes.php passes with a STDERR note. The parse
 * regexes are exercised against a built-in fixture first, so silent regex rot
 * reads as a red test — never as "zero routes, all good".
 *
 * DB-free. Auto-discovered by tests/run.php; also standalone:
 *   php tests/route_handler_test.php
 */

$isStandalone = !function_exists('t_ok');
if ($isStandalone) {
    define('APP_ROOT', dirname(__DIR__));
    foreach (['/core/bootstrap.php', '/app/core/bootstrap.php', '/crm/core/bootstrap.php'] as $cand) {
        if (is_file(APP_ROOT . $cand)) { require APP_ROOT . $cand; break; }
    }
    $GLOBALS['T'] = ['pass' => 0, 'fail' => 0, 'fails' => []];
    function t_ok($cond, string $msg): void
    {
        if ($cond) { $GLOBALS['T']['pass']++; }
        else { $GLOBALS['T']['fail']++; $GLOBALS['T']['fails'][] = $msg; }
    }
}

/**
 * Parse a routes.php source: [useMap shortName => FQCN, pairs [shortOrFqcn, method]].
 * Handles plain (`use A\B\C;`), aliased (`use A\B\C as D;`) and grouped
 * (`use A\B\{C, D as E};`) imports, and both quote styles around the method name.
 */
function rh_parse(string $src): array
{
    $useMap = [];
    if (preg_match_all('#^use\s+([\w\\\\]+?)(?:\s+as\s+(\w+))?\s*;#m', $src, $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) {
            $parts = explode('\\', $hit[1]);
            $useMap[$hit[2] ?? end($parts)] = $hit[1];
        }
    }
    if (preg_match_all('#^use\s+([\w\\\\]+)\\\\\{([^}]+)\}\s*;#m', $src, $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) {
            foreach (explode(',', $hit[2]) as $item) {
                if (preg_match('#^\s*([\w\\\\]+?)(?:\s+as\s+(\w+))?\s*$#', $item, $g)) {
                    $parts = explode('\\', $g[1]);
                    $useMap[$g[2] ?? end($parts)] = $hit[1] . '\\' . $g[1];
                }
            }
        }
    }
    $pairs = [];
    if (preg_match_all("#\\[\\s*([\\\\\\w]+)::class\\s*,\\s*['\"](\\w+)['\"]\\s*\\]#", $src, $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) { $pairs[] = [$hit[1], $hit[2]]; }
    }
    return [$useMap, $pairs];
}

/* Canary: the parser must extract known pairs from a fixture, or every real
 * assertion below is meaningless (a rotted regex would "find" zero routes and pass). */
$rhFixture = <<<'FIX'
use App\Controllers\Web\FakeController;
use App\Controllers\Api\{AlphaController, BetaController as Beta};
$r->get('/', [FakeController::class, 'home']);
$r->post('/a', [AlphaController::class, "save"]);
$r->get('/b', [ Beta::class , 'list' ]);
$r->group(['middleware' => [FakeMw::class]], function ($r) {
    $r->get('/c', [\App\Controllers\Web\InlineController::class, 'show']);
});
FIX;
[$rhUse, $rhPairs] = rh_parse($rhFixture);
t_ok(($rhUse['FakeController'] ?? '') === 'App\Controllers\Web\FakeController',
    'canary: plain use-statement resolves to its FQCN');
t_ok(($rhUse['Beta'] ?? '') === 'App\Controllers\Api\BetaController',
    'canary: grouped + aliased use-statement resolves to its FQCN');
t_ok(count($rhPairs) === 4,
    'canary: pair regex finds all 4 fixture handler pairs, middleware-only ::class not counted (got ' . count($rhPairs) . ')');

/* The real check: every handler pair in this repo's routes.php must reflect. */
$rhRoot = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__);
$rhRoutes = null;
foreach (['/routes.php', '/app/routes.php', '/crm/routes.php'] as $cand) {
    if (is_file($rhRoot . $cand)) { $rhRoutes = $rhRoot . $cand; break; }
}
if ($rhRoutes === null) {
    fwrite(STDERR, "route_handler_test: no routes.php found (site-at-root layout?) — coherence check SKIPPED\n");
} elseif (!is_file(dirname($rhRoutes) . '/core/bootstrap.php')) {
    // No sibling bootstrap = no App\ autoloader loaded: class_exists() would fail for
    // EVERY handler and turn the whole suite red on a layout quirk, not a broken route.
    fwrite(STDERR, "route_handler_test: $rhRoutes has no sibling core/bootstrap.php (no App autoloader) — coherence check SKIPPED\n");
} else {
    [$useMap, $pairs] = rh_parse((string) file_get_contents($rhRoutes));
    if ($pairs === []) {
        fwrite(STDERR, "route_handler_test: $rhRoutes declares no [Class::class, 'method'] pairs — nothing to check\n");
    }
    $broken = [];
    foreach ($pairs as [$short, $method]) {
        $fqcn = strpos($short, '\\') !== false ? ltrim($short, '\\') : ($useMap[$short] ?? null);
        if ($fqcn === null) { $broken[] = "$short (no use-statement resolves it)"; continue; }
        if (!class_exists($fqcn)) { $broken[] = "$short ($fqcn does not autoload — check the file path AND its case: a case-mismatched `git add` stages nothing on Windows)"; continue; }
        if (!method_exists($fqcn, $method)) { $broken[] = "$short::$method (method missing)"; continue; }
        $rm = new ReflectionMethod($fqcn, $method);
        if (!$rm->isPublic() || $rm->isStatic() || $rm->isAbstract()) { $broken[] = "$short::$method (not a public non-static instance method)"; }
    }
    t_ok($broken === [],
        'every routes.php handler resolves to a real public instance method (Router does (new $class())->$fn()): '
        . ($broken ? implode(' | ', $broken) : 'ok'));
}

if ($isStandalone) {
    $t = $GLOBALS['T'];
    echo "\nTESTS: {$t['pass']} passed, {$t['fail']} failed\n";
    foreach ($t['fails'] as $x) { echo "  FAIL: {$x}\n"; }
    exit($t['fail'] > 0 ? 1 : 0);
}
