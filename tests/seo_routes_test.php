<?php

declare(strict_types=1);

/*
 * seo_routes_test.php — robots.txt, sitemap.xml, canonical + og:image, over real HTTP.
 *
 * Starts `php -S` on 127.0.0.1 against public/ and fetches real responses, so the status,
 * content-type and body are what a crawler sees. The sitemap must list every public route
 * (Router::publicPaths over routes.php) and no admin/gate/inside/office route; robots.txt
 * must disallow those and point at the sitemap; every page's canonical must be the site
 * origin + the request path; og:image must resolve to a file the repo ships. The live
 * site is served from static/, so static/robots.txt, static/sitemap.xml and
 * static/index.html are held to the same contract.
 *
 * DB-free. Standalone: php tests/seo_routes_test.php
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

$root = dirname(__DIR__);

// --- the route table, in-process (no config: no DB) --------------------------------------------
if (!defined('APP_ROOT')) { define('APP_ROOT', $root); }
require_once $root . '/core/helpers.php';
require_once $root . '/core/App.php';
$app = new \App\Core\App($root);
require $root . '/routes.php';
$public = \App\Controllers\Web\SeoController::sitemapPaths($app->router);
t_ok(in_array('/', $public, true), 'the home route is a public path');
foreach (['/admin', '/gate', '/inside', '/office'] as $bad) {
    t_ok(!in_array($bad, $public, true), "$bad is not a public path");
}
foreach ($public as $p) {
    t_ok(!str_starts_with($p, '/admin') && !str_contains($p, '{'), "public path $p is not an admin or parameter route");
}

// --- real HTTP against php -S ------------------------------------------------------------------
$port = 18000 + random_int(0, 900);
$cwd = $root . '/public';
$env = null;
$proc = proc_open(
    [PHP_BINARY, '-S', "127.0.0.1:$port", "$cwd/index.php"],
    [0 => ['pipe', 'r'], 1 => ['file', sys_get_temp_dir() . '/seo_test_out.log', 'w'], 2 => ['file', sys_get_temp_dir() . '/seo_test_err.log', 'w']],
    $pipes,
    $cwd
);
$up = false;
for ($i = 0; $i < 50 && is_resource($proc); $i++) {
    $c = @fsockopen('127.0.0.1', $port, $en, $es, 0.2);
    if ($c) { fclose($c); $up = true; break; }
    usleep(100000);
}
t_ok($up, 'php -S test server started');

/** @return array{status:int,type:string,body:string} */
$get = function (string $path) use ($port): array {
    $ctx = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 10, 'follow_location' => 0]]);
    $body = @file_get_contents("http://127.0.0.1:$port$path", false, $ctx);
    $status = 0; $type = '';
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('~^HTTP/\S+\s+(\d{3})~', $h, $m)) { $status = (int)$m[1]; }
        if (stripos($h, 'Content-Type:') === 0) { $type = trim(substr($h, 13)); }
    }
    return ['status' => $status, 'type' => $type, 'body' => (string)$body];
};

if ($up) {
    $origin = 'https://swens.net';

    $robots = $get('/robots.txt');
    t_ok($robots['status'] === 200, 'GET /robots.txt answers 200');
    t_ok(str_starts_with($robots['type'], 'text/plain'), 'robots.txt is text/plain');
    t_ok(str_contains($robots['body'], "User-agent: *\n") && str_contains($robots['body'], "Allow: /\n"), 'robots.txt allows public pages');
    foreach (['/admin', '/gate', '/inside', '/office'] as $d) {
        t_ok(str_contains($robots['body'], "Disallow: $d\n"), "robots.txt disallows $d");
    }
    t_ok(str_contains($robots['body'], "Sitemap: $origin/sitemap.xml"), 'robots.txt points at the sitemap');

    $sm = $get('/sitemap.xml');
    t_ok($sm['status'] === 200, 'GET /sitemap.xml answers 200');
    t_ok(str_starts_with($sm['type'], 'application/xml'), 'sitemap.xml is application/xml');
    $xml = @simplexml_load_string($sm['body']);
    t_ok($xml !== false, 'sitemap.xml parses as XML');
    $locs = [];
    if ($xml !== false) { foreach ($xml->url as $u) { $locs[] = (string)$u->loc; } }
    foreach ($public as $p) {
        t_ok(in_array($origin . $p, $locs, true), "every public route is in the sitemap: $p");
    }
    t_ok(count($locs) === count($public), 'the sitemap lists exactly the public routes');
    foreach ($locs as $loc) {
        t_ok(!preg_match('~^https://swens\.net/(admin|gate|inside|office|key)(/|$)~', $loc), "no non-public route in the sitemap: $loc");
    }

    // --- canonical + og:image on every public page the layout renders ---
    foreach (['/', '/gate', '/office'] as $path) {
        $r = $get($path);
        if ($r['status'] !== 200) { continue; }   // /office needs a DB when DB_HOST is configured
        t_ok(preg_match('~<link rel="canonical" href="([^"]+)">~', $r['body'], $m) === 1, "$path has a canonical");
        t_ok(($m[1] ?? '') === $origin . ($path === '/' ? '/' : $path), "$path canonical is the site origin + the request path");
        t_ok(preg_match('~<meta property="og:image" content="([^"]+)">~', $r['body'], $im) === 1, "$path has og:image");
        $imgPath = parse_url($im[1] ?? '', PHP_URL_PATH);
        t_ok(is_string($imgPath) && str_starts_with($im[1], $origin . '/') && is_file($root . '/public' . $imgPath),
            "$path og:image resolves to a file in public/");
        t_ok(preg_match('~<meta name="description" content="([^"]+)">~', $r['body'], $dm) === 1, "$path has a non-empty meta description");
    }
    $home = $get('/');
    t_ok($home['status'] === 200, 'home renders 200');
    $canonQ = $get('/gate?x=1');
    if ($canonQ['status'] === 200) {
        t_ok(str_contains($canonQ['body'], '<link rel="canonical" href="https://swens.net/gate">'), 'canonical drops the query string');
    }
}
if (is_resource($proc)) {
    $st = proc_get_status($proc);
    // Windows: the php -S child; terminate only the pid we started.
    proc_terminate($proc);
    if (PHP_OS_FAMILY === 'Windows' && !empty($st['pid'])) { @exec('taskkill /F /T /PID ' . (int)$st['pid'] . ' >NUL 2>&1'); }
    proc_close($proc);
}

// --- static/ (the live docroot) ----------------------------------------------------------------
$sRobots = (string)@file_get_contents($root . '/static/robots.txt');
t_ok($sRobots === \App\Controllers\Web\SeoController::robotsBody(), 'static/robots.txt equals the router robots body');
$sMap = (string)@file_get_contents($root . '/static/sitemap.xml');
t_ok($sMap === \App\Controllers\Web\SeoController::sitemapBody($public), 'static/sitemap.xml equals the router sitemap body');
$sHome = (string)@file_get_contents($root . '/static/index.html');
t_ok(str_contains($sHome, '<link rel="canonical" href="https://swens.net/">'), 'static home has a self-referencing canonical');
t_ok(preg_match('~<meta property="og:image" content="https://swens\.net(/[^"]+)">~', $sHome, $sm2) === 1
    && is_file($root . '/static' . $sm2[1]), 'static home og:image resolves to a file in static/');

if ($isStandalone) {
    $t = $GLOBALS['T'];
    echo "\nTESTS: {$t['pass']} passed, {$t['fail']} failed\n";
    foreach ($t['fails'] as $x) { echo "  FAIL: {$x}\n"; }
    exit($t['fail'] > 0 ? 1 : 0);
}
