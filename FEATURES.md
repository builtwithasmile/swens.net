# Feature Registry (locked)

Format + rules: the factory template's FEATURES.md (`## id` headings, then **Files** /
**Contains** bullets; **Test:** and **Assumptions:** by convention). Nothing is "done"
until Josh says so AND it appears here with a test.

## crypto-watch
**Name:** Crypto Watch — Bitcoin, Ethereum, XRP, Solana, Tether, USDC prices in CAD (Kraken) at https://swens.net/mycryptowatch/
**Files:**
- static/mycryptowatch/index.html
- tests/mycryptowatch_test.php
- tests/fixtures/kraken/ticker.json
**Contains:**
- static/mycryptowatch/index.html =~ api.kraken.com/0/public/
- static/mycryptowatch/index.html =~! api.coingecko.com
- static/mycryptowatch/index.html =~! (?i)coinpaprika
**Test:** tests/mycryptowatch_test.php
**Assumptions:** Kraken's public Ticker + OHLC stay keyless and browser-callable; fixed list of six coins (no auto top 10, Josh's ruling 2026-10-05); Kraken's ticker open is since midnight UTC, so the 24h change is read from the hourly OHLC candle containing now-24h (a failed OHLC call shows a dash for that coin, never a made-up number). Parse function checked in node against a real capture in tests/fixtures/kraken/.

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

## template-exists-guard
**Name:** Template/partial existence guard — a deleted or misnamed page, layout or partial turns the suite red instead of 500-ing every page
**Files:**
- tests/template_exists_test.php
**Contains:**
- tests/template_exists_test.php =~ templates/layouts/
**Test:** tests/template_exists_test.php
**Assumptions:** templates are named by plain string literals in Template::render()/partial() calls (a dynamic name fails the test as unverifiable); lives in its own file so the synced tests/route_handler_test.php stays untouched.

## seo-robots-sitemap
**Name:** robots.txt and sitemap.xml, built from the route table so they cannot drift (PHP router routes + static/ copies for the live docroot)
**Files:**
- controllers/web/SeoController.php
- core/Router.php
- routes.php
- static/robots.txt
- static/sitemap.xml
- tests/seo_routes_test.php
**Contains:**
- routes.php =~ /sitemap\.xml
- routes.php =~ /robots\.txt
- static/robots.txt =~ Disallow: /admin
- static/sitemap.xml =~ <loc>https://swens\.net/</loc>
**Test:** tests/seo_routes_test.php
**Assumptions:** the sitemap lists only GET routes with no middleware and no {param} outside /admin /gate /inside /office (so today just `/`); /mycryptowatch/ is static and noindex, so it is not listed. The live site is served from static/, so static/robots.txt and static/sitemap.xml are held equal to the router output by the test.
