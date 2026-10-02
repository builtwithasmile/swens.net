<?php

declare(strict_types=1);

/*
 * mycryptowatch_test.php — Crypto Watch (static/mycryptowatch/index.html) price source.
 *
 * The page asks a free price API for prices straight from the visitor's browser, so the
 * failure that matters is the provider changing its rules: CoinGecko's keyless markets
 * call began answering 403 in Sept 2026 and the page went blank. Two checks:
 *   1. static — the page asks CoinPaprika for CAD tickers, reads the fields that API
 *      returns, never calls CoinGecko, and drops a coin icon that fails to load;
 *   2. live contract — CoinPaprika still answers without a key, lets a browser on
 *      swens.net call it (CORS), and returns ranked coins with a CAD price + 24h change.
 *      Loud-skips (STDERR) when curl or the network is unavailable, or on a 429; any
 *      other non-200, a missing CORS header, or a changed shape is a red test.
 *
 * PHP's own curl can't reach HTTPS on Josh's machine, so the live check shells out to
 * the curl binary (Windows System32 curl.exe or Linux curl — same flags).
 *
 * Standalone: php tests/mycryptowatch_test.php
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

$page = @file_get_contents(dirname(__DIR__) . '/static/mycryptowatch/index.html');
t_ok(is_string($page) && $page !== '', 'static/mycryptowatch/index.html exists');
$page = (string)$page;

t_ok(str_contains($page, "fetch('https://api.coinpaprika.com/v1/tickers?quotes=CAD'"),
    'Crypto Watch fetches CoinPaprika CAD tickers');
t_ok(!str_contains($page, 'api.coingecko.com'),
    'Crypto Watch never calls CoinGecko (its keyless markets call answers 403)');
foreach (['c.quotes.CAD', 'q.price', 'q.percent_change_24h', 'a.rank - b.rank', '.slice(0, 10)'] as $needle) {
    t_ok(str_contains($page, $needle), "Crypto Watch reads CoinPaprika's shape: {$needle}");
}
t_ok(str_contains($page, 'onerror="this.remove()"'),
    'a coin icon that fails to load is dropped, not shown broken');

$out = @shell_exec('curl -sS -m 30 -D - -H "Origin: https://swens.net" "https://api.coinpaprika.com/v1/tickers?quotes=CAD" 2>&1');
$out = is_string($out) ? $out : '';
if (!preg_match('~^HTTP/[\d.]+ (\d{3})~m', $out, $m)) {
    fwrite(STDERR, "SKIP live CoinPaprika check: no HTTP response (curl or network unavailable)\n");
} elseif ($m[1] === '429') {
    fwrite(STDERR, "SKIP live CoinPaprika check: rate-limited (429)\n");
} else {
    $parts = preg_split("~\r?\n\r?\n~", $out, 2);
    $headers = $parts[0] ?? '';
    $rows = json_decode($parts[1] ?? '', true);
    t_ok($m[1] === '200', "CoinPaprika tickers answer 200 without a key (got {$m[1]})");
    t_ok((bool)preg_match('~^access-control-allow-origin: (\*|https://swens\.net)~mi', $headers),
        'CoinPaprika lets a browser on swens.net call it (CORS header present)');
    $btc = null;
    foreach (is_array($rows) ? $rows : [] as $r) {
        if (($r['id'] ?? '') === 'btc-bitcoin') { $btc = $r; break; }
    }
    $cad = $btc['quotes']['CAD'] ?? null;
    t_ok(is_array($rows) && count($rows) >= 10, 'CoinPaprika returns a list of at least 10 coins');
    t_ok(($btc['rank'] ?? 0) >= 1 && is_array($cad) && ($cad['price'] ?? 0) > 0
        && is_numeric($cad['percent_change_24h'] ?? null),
        'CoinPaprika bitcoin row has rank + CAD price + 24h change');
}

// --- Currency charts (CAD→USD, CAD→CRC, USD→CRC) on the same page -----------------------------
t_ok(str_contains($page, 'https://api.frankfurter.dev/v2/rates?base=CAD&quotes=USD,CRC'),
    'Currency charts fetch Frankfurter v2 CAD rates for USD and CRC');
t_ok(!str_contains($page, 'frankfurter.dev/v1') && !str_contains($page, 'frankfurter.app'),
    'Currency charts never use the deprecated Frankfurter v1 / .app endpoints');
foreach (["k: '5D'", "k: '1M'", "k: '1Y'", "k: '5Y'", "k: 'Max'", "group: 'week'", "group: 'month'", 'd.CRC / d.USD'] as $needle) {
    t_ok(str_contains($page, $needle), "Currency charts define the range/pair: {$needle}");
}

$fx = @shell_exec('curl -sS -m 30 -D - -H "Origin: https://swens.net" "https://api.frankfurter.dev/v2/rates?base=CAD&quotes=USD,CRC&from=' . date('Y-m-d', time() - 12 * 86400) . '" 2>&1');
$fx = is_string($fx) ? $fx : '';
if (!preg_match('~^HTTP/[\d.]+ (\d{3})~m', $fx, $fm)) {
    fwrite(STDERR, "SKIP live Frankfurter check: no HTTP response (curl or network unavailable)\n");
} elseif ($fm[1] === '429') {
    fwrite(STDERR, "SKIP live Frankfurter check: rate-limited (429)\n");
} else {
    $fparts = preg_split("~\r?\n\r?\n~", $fx, 2);
    $fheaders = $fparts[0] ?? '';
    $frows = json_decode($fparts[1] ?? '', true);
    t_ok($fm[1] === '200', "Frankfurter v2 rates answer 200 without a key (got {$fm[1]})");
    t_ok((bool)preg_match('~^access-control-allow-origin: (\*|https://swens\.net)~mi', $fheaders),
        'Frankfurter lets a browser on swens.net call it (CORS header present)');
    $seen = [];
    foreach (is_array($frows) ? $frows : [] as $r) {
        if (isset($r['quote'], $r['rate'], $r['date']) && $r['rate'] > 0) { $seen[$r['quote']] = ($seen[$r['quote']] ?? 0) + 1; }
    }
    t_ok(($seen['USD'] ?? 0) >= 3 && ($seen['CRC'] ?? 0) >= 3,
        'Frankfurter returns daily CAD→USD and CAD→CRC rows (date + quote + rate)');
}

if ($isStandalone) {
    $t = $GLOBALS['T'];
    echo "\nTESTS: {$t['pass']} passed, {$t['fail']} failed\n";
    foreach ($t['fails'] as $x) { echo "  FAIL: {$x}\n"; }
    exit($t['fail'] > 0 ? 1 : 0);
}
