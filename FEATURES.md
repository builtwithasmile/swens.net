# Feature Registry (locked)

Format + rules: the factory template's FEATURES.md (`## id` headings, then **Files** /
**Contains** bullets; **Test:** and **Assumptions:** by convention). Nothing is "done"
until Josh says so AND it appears here with a test.

## crypto-watch
**Name:** Crypto Watch — top-10 crypto prices in CAD at https://swens.net/mycryptowatch/
**Files:**
- static/mycryptowatch/index.html
- tests/mycryptowatch_test.php
**Contains:**
- static/mycryptowatch/index.html =~ api.coinpaprika.com/v1/tickers\?quotes=CAD
- static/mycryptowatch/index.html =~! api.coingecko.com
**Test:** tests/mycryptowatch_test.php
**Assumptions:** CoinPaprika's free tickers API stays keyless and browser-callable; board item SN-0025 carries the move to Kraken's own CAD prices.

## fx-watch
**Name:** Currency charts on Crypto Watch — CAD→USD, CAD→CRC (colón), USD→CRC, range tabs 5D/1M/1Y/5Y/Max
**Files:**
- static/mycryptowatch/index.html
- tests/mycryptowatch_test.php
**Contains:**
- static/mycryptowatch/index.html =~ api.frankfurter.dev/v2/rates\?base=CAD&quotes=USD,CRC
- static/mycryptowatch/index.html =~! frankfurter.app
**Test:** tests/mycryptowatch_test.php
**Assumptions:** Frankfurter v2 (ECB reference rates, daily only — so no 1D tab) stays keyless, browser-callable, and keeps CRC history; USD→CRC is derived as CRC/USD from the same CAD rows.
