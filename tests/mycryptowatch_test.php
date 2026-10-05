<?php

declare(strict_types=1);

/*
 * mycryptowatch_test.php — Crypto Watch (static/mycryptowatch/index.html) price source.
 *
 * The page asks Kraken's public API for CAD prices straight from the visitor's browser
 * (BTC, ETH, XRP, SOL, USDT, USDC, in that order). Earlier free providers broke on policy
 * (CoinGecko's keyless call began answering 403 in Sept 2026), so the checks are:
 *   1. static: the page asks Kraken's Ticker + hourly OHLC, never CoinGecko/CoinPaprika,
 *      and drops a coin icon that fails to load;
 *   2. parse: the page's own krakenRows() function (cut out of the page and run in node)
 *      turns a REAL Kraken response saved in tests/fixtures/kraken/ (captured with
 *      curl.exe) into six rows: CAD price + 24h change from the hourly candle that
 *      contains "now minus 24h". Loud-skips (STDERR) when node is missing;
 *   3. live contract: Kraken still answers without a key, lets a browser on swens.net
 *      call it (CORS), and returns all six CAD pairs. Loud-skips when curl or the network
 *      is unavailable, or on a 429; any other non-200 or a changed shape is a red test.
 *
 * PHP's own curl can't reach HTTPS on Josh's machine, so the live check shells out to
 * the curl binary (Windows System32 curl.exe or Linux curl, same flags).
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

t_ok(str_contains($page, "KRAKEN = 'https://api.kraken.com/0/public/'") && str_contains($page, "'Ticker?pair='")
    && str_contains($page, "'OHLC?pair='"),
    'Crypto Watch fetches the Kraken public Ticker and hourly OHLC');
t_ok(!str_contains($page, 'api.coingecko.com') && stripos($page, 'coinpaprika') === false,
    'Crypto Watch never calls CoinGecko or CoinPaprika (both retired)');
t_ok(str_contains($page, 'onerror="this.remove()"'),
    'a coin icon that fails to load is dropped, not shown broken');
t_ok(str_contains($page, 'Prices are unavailable right now'),
    'the unavailable-prices error state is still on the page');

// --- parse: the page's krakenRows() against a real Kraken capture -----------------------------
$fx = dirname(__DIR__) . '/tests/fixtures/kraken';
$ticker = json_decode((string)@file_get_contents("$fx/ticker.json"), true);
$ohlcFiles = ['BTC' => 'XBTCAD', 'ETH' => 'ETHCAD', 'XRP' => 'XRPCAD', 'SOL' => 'SOLCAD', 'USDT' => 'USDTCAD', 'USDC' => 'USDCCAD'];
$ohlc = [];
foreach ($ohlcFiles as $sym => $pair) {
    $jf = json_decode((string)@file_get_contents("$fx/ohlc_$pair.json"), true);
    $ohlc[$sym] = $jf['result'] ?? null;
}
t_ok(is_array($ticker['result'] ?? null) && !in_array(null, $ohlc, true), 'Kraken fixtures (ticker + six OHLC) are present');

$cut = [];
$hasMarkers = preg_match('~(var COINS = \[.*?/\* cw-parse:end \*/)~s', $page, $cut) === 1;
t_ok($hasMarkers, 'page carries the cw-parse end marker after COINS and krakenRows()');
$node = trim((string)@shell_exec(PHP_OS_FAMILY === 'Windows' ? 'where node 2>NUL' : 'command -v node 2>/dev/null'));
$node = $node !== '' ? (string)strtok($node, "\r\n") : '';
if ($hasMarkers && $node !== '' && is_array($ticker['result'] ?? null)) {
    $btcCandles = $ohlc['BTC']['XXBTZCAD'] ?? [];
    $now = $btcCandles ? (int)end($btcCandles)[0] + 600 : 0;
    $run = function (array $tk, array $oh) use ($cut, $node, $now): ?array {
        $js = $cut[1] . "\nconsole.log(JSON.stringify(krakenRows(" . json_encode($tk) . ', ' . json_encode($oh) . ", $now)));";
        $p = proc_open([$node, '-'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($p)) { return null; }
        fwrite($pipes[0], $js);
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($p);
        $r = json_decode((string)$out, true);
        return is_array($r) ? $r : null;
    };
    $rows = $run($ticker['result'], $ohlc);
    t_ok(is_array($rows) && array_column($rows, 'symbol') === array_keys($ohlcFiles),
        'krakenRows returns BTC, ETH, XRP, SOL, USDT, USDC in that order');
    t_ok(is_array($rows) && array_column($rows, 'name') === ['Bitcoin', 'Ethereum', 'XRP', 'Solana', 'Tether', 'USDC'],
        'krakenRows names the coins Bitcoin, Ethereum, XRP, Solana, Tether, USDC');
    $keyOf = ['BTC' => 'XXBTZCAD', 'ETH' => 'XETHZCAD', 'XRP' => 'XXRPZCAD', 'SOL' => 'SOLCAD', 'USDT' => 'USDTCAD', 'USDC' => 'USDCCAD'];
    $priceOk = true;
    $chgOk = true;
    foreach ($rows ?? [] as $r) {
        $sym = $r['symbol'];
        $want = (float)$ticker['result'][$keyOf[$sym]]['c'][0];
        if ($want <= 0 || abs($r['price'] - $want) > 1e-9) { $priceOk = false; }
        $ref = null;
        foreach ($ohlc[$sym][$keyOf[$sym]] as $k) { if ($k[0] <= $now - 86400) { $ref = $k; } }
        $wantChg = $ref ? ($want / (float)$ref[1] - 1) * 100 : null;
        if ($wantChg === null || !is_numeric($r['change'] ?? null) || abs($r['change'] - $wantChg) > 1e-9 || abs($r['change']) > 60) { $chgOk = false; }
    }
    t_ok($priceOk && count($rows ?? []) === 6, 'krakenRows prices equal the Ticker last-trade CAD price for all six pairs');
    t_ok($chgOk, 'krakenRows 24h change = last price vs the open of the hourly candle containing now-24h (all six)');
    $noOhlc = $run($ticker['result'], array_map(fn($x) => null, $ohlc));
    t_ok(is_array($noOhlc) && count($noOhlc) === 6 && array_filter(array_column($noOhlc, 'change'), fn($c) => $c !== null) === [],
        'without OHLC every row keeps its price and shows no 24h change (never a faked one)');
    $partial = $ticker['result'];
    unset($partial['SOLCAD']);
    $part = $run($partial, $ohlc);
    t_ok(is_array($part) && array_column($part, 'symbol') === ['BTC', 'ETH', 'XRP', 'USDT', 'USDC'],
        'a pair missing from the Ticker is dropped, the rest still render');
} else {
    fwrite(STDERR, "SKIP krakenRows parse check: node not found\n");
}

// --- live contract ------------------------------------------------------------------------------
$out = @shell_exec('curl -sS -m 30 -D - -H "Origin: https://swens.net" "https://api.kraken.com/0/public/Ticker?pair=XBTCAD,ETHCAD,XRPCAD,SOLCAD,USDTCAD,USDCCAD" 2>&1');
$out = is_string($out) ? $out : '';
if (!preg_match('~^HTTP/[\d.]+ (\d{3})~m', $out, $m)) {
    fwrite(STDERR, "SKIP live Kraken check: no HTTP response (curl or network unavailable)\n");
} elseif ($m[1] === '429') {
    fwrite(STDERR, "SKIP live Kraken check: rate-limited (429)\n");
} else {
    $parts = preg_split("~\r?\n\r?\n~", $out, 2);
    $headers = $parts[0] ?? '';
    $lj = json_decode($parts[1] ?? '', true);
    t_ok($m[1] === '200', "Kraken Ticker answers 200 without a key (got {$m[1]})");
    t_ok((bool)preg_match('~^access-control-allow-origin: (\*|https://swens\.net)~mi', $headers),
        'Kraken lets a browser on swens.net call it (CORS header present)');
    $res = is_array($lj['result'] ?? null) ? $lj['result'] : [];
    $good = 0;
    foreach (['XXBTZCAD', 'XETHZCAD', 'XXRPZCAD', 'SOLCAD', 'USDTCAD', 'USDCCAD'] as $k) {
        if ((float)($res[$k]['c'][0] ?? 0) > 0) { $good++; }
    }
    t_ok(($lj['error'] ?? ['x']) === [] && $good === 6,
        "Kraken Ticker returns all six CAD pairs under the keys the page reads ($good of 6)");
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
