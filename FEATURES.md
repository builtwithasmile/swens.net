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
