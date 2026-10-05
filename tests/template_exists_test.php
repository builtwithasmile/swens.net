<?php

declare(strict_types=1);

/*
 * template_exists_test.php — every template, layout and partial the app names must exist.
 *
 * tests/route_handler_test.php proves each route's handler CLASS resolves, but nothing
 * proved the files a handler renders are there. Template::partial() throws "Template not
 * found" at render time, so deleting templates/layouts/site.php (or one partial) left the
 * suite green while every page answered 500. This is the static guard, DB-free:
 *   - every Template::render('page', ..., 'layout') call in controllers/, middleware/,
 *     services/, core/ and public/ must resolve templates/<page>.php and
 *     templates/layouts/<layout>.php (the layout defaults to 'app', as Template::render does);
 *   - every Template::partial('x') / partial('x') call in those files and in every file
 *     under templates/ must resolve templates/<x>.php;
 *   - a reference that is not a plain string literal fails: it cannot be verified.
 * Calls are read with PhpToken (not regex), so a quoted name in a comment is not counted.
 * The scanner is exercised against a built-in fixture first, so a rotted scanner reads as
 * a red test, never as "zero references, all good".
 *
 * Kept in its own file (not in the synced route_handler_test.php). Standalone:
 *   php tests/template_exists_test.php
 */

$isStandalone = !function_exists('t_ok');
if ($isStandalone) {
    $GLOBALS['T'] = ['pass' => 0, 'fail' => 0, 'fails' => []];
    function t_ok($cond, string $msg): void
    {
        if ($cond) { $GLOBALS['T']['pass']++; }
        else { $GLOBALS['T']['fail']++; $GLOBALS['T']['fails'][] = $msg; }
    }
}

/**
 * Find template references in PHP source.
 * @return array{0: list<array{kind:string,name:?string,layout:?string,line:int}>, 1: list<string>}
 *         [references (kind = page|partial, name = literal or null when dynamic), errors]
 */
function te_scan(string $src): array
{
    $tokens = array_values(array_filter(
        PhpToken::tokenize($src),
        static fn(PhpToken $t) => !$t->isIgnorable()
    ));
    $refs = [];
    $n = count($tokens);
    for ($i = 0; $i < $n; $i++) {
        $t = $tokens[$i];
        if ($t->id !== T_STRING || ($tokens[$i + 1]->text ?? '') !== '(') { continue; }
        $prev = $tokens[$i - 1] ?? null;
        $kind = null;
        if ($t->text === 'render' || $t->text === 'partial') {
            $isStatic = $prev && $prev->id === T_DOUBLE_COLON
                && ($tokens[$i - 2]->text === 'Template' || str_ends_with($tokens[$i - 2]->text ?? '', '\Template'));
            $isBareHelper = $t->text === 'partial'
                && (!$prev || !in_array($prev->id, [T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_FUNCTION, T_NEW], true));
            if ($isStatic || $isBareHelper) {
                $kind = $t->text === 'render' ? 'page' : 'partial';
            }
        }
        if ($kind === null) { continue; }
        // split top-level arguments of this call
        $args = [[]];
        $depth = 0;
        for ($j = $i + 1; $j < $n; $j++) {
            $x = $tokens[$j];
            if ($x->text === '(' || $x->text === '[' || $x->text === '{') {
                $depth++;
                if ($depth === 1) { continue; }
            } elseif ($x->text === ')' || $x->text === ']' || $x->text === '}') {
                $depth--;
                if ($depth === 0) { break; }
            } elseif ($x->text === ',' && $depth === 1) {
                $args[] = [];
                continue;
            }
            $args[count($args) - 1][] = $x;
        }
        $literal = static function (array $arg): ?string {
            if (count($arg) === 1 && $arg[0]->id === T_CONSTANT_ENCAPSED_STRING) {
                return stripslashes(substr($arg[0]->text, 1, -1));
            }
            return null;
        };
        $name = isset($args[0]) ? $literal($args[0]) : null;
        $layout = null;
        if ($kind === 'page') {
            $layout = isset($args[2]) && $args[2] !== [] ? $literal($args[2]) : 'app';
        }
        $refs[] = ['kind' => $kind, 'name' => $name, 'layout' => $layout, 'line' => $t->line,
                   'dynamicLayout' => $kind === 'page' && isset($args[2]) && $args[2] !== [] && $layout === null];
    }
    return [$refs, []];
}

/** Every .php file under $dir (recursive), or [] if the dir is absent. */
function te_php_files(string $dir): array
{
    if (!is_dir($dir)) { return []; }
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && strtolower($f->getExtension()) === 'php') { $out[] = $f->getPathname(); }
    }
    sort($out);
    return $out;
}

/* Canary: the scanner must extract known references from a fixture. */
$teFixture = <<<'FIX'
<?php
namespace X;
use App\Core\Template;
// Template::render('pages/in-comment', [], 'site');
function partial(string $name) {}
class C {
    public function a() {
        $h = Template::render('pages/one', ['k' => [1, 2], 'm' => f(1, 2)], 'site');
        $h = Template::render('pages/two', []);
        $h = \App\Core\Template::render($dyn, [], 'bare');
        return self::partial($x) . $this->partial('nope/method');
    }
}
?>
<?= partial('partials/head', ['active' => $active ?? '']) ?>
<?= Template::partial("partials/foot") ?>
FIX;
[$teRefs] = te_scan($teFixture);
$teKey = array_map(static fn($r) => $r['kind'] . ':' . ($r['name'] ?? '?') . ':' . ($r['layout'] ?? '-'), $teRefs);
t_ok($teKey === [
        'page:pages/one:site', 'page:pages/two:app', 'page:?:bare',
        'partial:partials/head:-', 'partial:partials/foot:-',
    ],
    'canary: scanner reads render/partial calls (layout default app, comments and method calls ignored): ' . implode(' | ', $teKey));

/* The real check. */
$teRoot = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__);
$teTemplates = $teRoot . '/templates';
if (!is_dir($teTemplates)) {
    fwrite(STDERR, "template_exists_test: no templates/ directory — check SKIPPED\n");
} else {
    $sourceFiles = array_merge(
        te_php_files($teRoot . '/controllers'),
        te_php_files($teRoot . '/middleware'),
        te_php_files($teRoot . '/services'),
        te_php_files($teRoot . '/core'),
        te_php_files($teRoot . '/public'),
        te_php_files($teTemplates)
    );
    // The renderer's own definitions pass variables by design.
    $sourceFiles = array_values(array_filter($sourceFiles, static function (string $f) {
        $b = str_replace('\\', '/', $f);
        return !str_ends_with($b, '/core/Template.php') && !str_ends_with($b, '/core/helpers.php');
    }));

    $missing = [];
    $checked = 0;
    $pages = 0;
    $partials = 0;
    foreach ($sourceFiles as $file) {
        $rel = ltrim(str_replace('\\', '/', substr($file, strlen($teRoot))), '/');
        [$refs] = te_scan((string) file_get_contents($file));
        foreach ($refs as $r) {
            $where = "$rel:{$r['line']}";
            if ($r['name'] === null) {
                $missing[] = "$where ({$r['kind']} name is not a plain string literal; cannot verify)";
                continue;
            }
            $checked++;
            $r['kind'] === 'page' ? $pages++ : $partials++;
            if (!is_file("$teTemplates/{$r['name']}.php")) {
                $missing[] = "$where (templates/{$r['name']}.php does not exist)";
            }
            if ($r['kind'] === 'page') {
                if ($r['dynamicLayout']) {
                    $missing[] = "$where (layout is not a plain string literal; cannot verify)";
                } elseif (!is_file("$teTemplates/layouts/{$r['layout']}.php")) {
                    $missing[] = "$where (templates/layouts/{$r['layout']}.php does not exist)";
                }
            }
        }
    }
    t_ok($missing === [],
        'every template, layout and partial named by a render()/partial() call exists: '
        . ($missing ? implode(' | ', $missing) : "ok ($pages page render calls, $partials partial calls)"));
    t_ok($pages > 0 && $partials > 0,
        "scan sanity: found page renders and partial calls (got $pages / $partials) so a rotted scan can't pass as ok");

}

if ($isStandalone) {
    $t = $GLOBALS['T'];
    echo "\nTESTS: {$t['pass']} passed, {$t['fail']} failed\n";
    foreach ($t['fails'] as $x) { echo "  FAIL: {$x}\n"; }
    exit($t['fail'] > 0 ? 1 : 0);
}
