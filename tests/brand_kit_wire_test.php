<?php

declare(strict_types=1);

/*
 * brand_kit_wire_test.php — every page head loads the brand-kit icons, and each file
 * it names is the byte-identical kit copy. Standalone: php tests/brand_kit_wire_test.php
 */

$root = dirname(__DIR__);
$pass = 0;
$fails = [];
$ok = function ($cond, string $msg) use (&$pass, &$fails): void {
    if ($cond) { $pass++; } else { $fails[] = $msg; }
};

$heads = ['templates/layouts/site.php', 'templates/layouts/bare.php', 'static/index.html', 'static/mycryptowatch/index.html'];
$want = ['/favicon.ico', '/assets/favicon.svg', '/assets/favicon-32.png', '/assets/apple-touch-icon.png'];
foreach ($heads as $h) {
    $src = (string) file_get_contents("$root/$h");
    foreach ($want as $href) {
        $ok(str_contains($src, 'href="' . $href . '"'), "$h links $href");
    }
    $ok(!str_contains($src, 'swens-mark.svg" >') && !str_contains($src, 'rel="icon" href="data:'), "$h has no old icon link");
}

// Served copies equal the kit (static/ is the live docroot, public/ the PHP app's).
$files = ['favicon-32.png', 'favicon.svg', 'apple-touch-icon.png', 'icon-192.png', 'icon-512.png', 'icon-512-maskable.png'];
foreach (['public/assets', 'static/assets'] as $dir) {
    foreach ($files as $f) {
        $ok(is_file("$root/$dir/$f") && md5_file("$root/$dir/$f") === md5_file("$root/brand/kit/$f"), "$dir/$f equals brand/kit/$f");
    }
}
foreach (['public/favicon.ico', 'static/favicon.ico'] as $f) {
    $ok(is_file("$root/$f") && md5_file("$root/$f") === md5_file("$root/brand/kit/favicon.ico"), "$f equals brand/kit/favicon.ico");
}

// Manifest keeps its identity; icons point at existing kit copies.
$m = json_decode((string) file_get_contents("$root/public/manifest.json"), true);
$ok(($m['name'] ?? '') === 'Swens · Josh Swenson' && ($m['start_url'] ?? '') === '/', 'manifest identity unchanged');
$ok(count($m['icons'] ?? []) === 3, 'manifest lists 3 icons');
foreach ($m['icons'] ?? [] as $i) {
    $ok(is_file($root . '/public' . $i['src']), 'manifest icon exists: ' . $i['src']);
}

echo "\nTESTS: $pass passed, " . count($fails) . " failed\n";
foreach ($fails as $x) { echo "  FAIL: $x\n"; }
exit($fails ? 1 : 0);
