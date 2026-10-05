# Builder start: swens.net (procedure only)

Josh's personal site. Plumbing changes add no copy and change no visible wording.

## Two surfaces, one repo
- LIVE site = `static/` (a plain HTML snapshot served from the OVH/CloudPanel box, deployed with
  dploy). Only `/` and `/mycryptowatch/` exist live; `/office`, `/gate`, `/inside` answer 404.
- The PHP app (`public/index.php` + `routes.php` + `controllers/` + `templates/`) is built but
  shelved, never deployed (see docs/knowledge/operator.md). A change meant to reach the live site
  must also land in `static/` (root files such as robots.txt, sitemap.xml, index.html).
- Docroot of the PHP app is `public/`; `static/assets/` holds the images the live site serves.

## Map
- `routes.php` router table; `core/Router.php` (method + pattern, middleware groups).
- `core/helpers.php` global helpers (`e()`, `config()`, `site_url()`, `canonical_url()`).
- `templates/layouts/site.php` head for public pages; `controllers/web/*` (dir names are lowercase).
- `FEATURES.md` feature registry (`## id`, Files, Contains, Test). New behaviour gets an entry in
  the same commit as its test.

## Tests
- No `tests/run.php`. Each test is standalone: `php tests/<name>.php` (prints `TESTS: n passed`).
  php off PATH: `D:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe`.
- `route_handler_test.php` route to handler coherence; `template_exists_test.php` templates exist;
  `mycryptowatch_test.php` needs node and outbound curl (live Kraken/Frankfurter checks).
- `seo_routes_test.php` starts `php -S` on 127.0.0.1 against `public/` and fetches real responses.
- `scripts/verify-operator-docs.php` exists; the pre-commit hook runs gates it recognises.

## Commit
- `git commit -m "msg" -- <paths>` (hook `.githooks/pre-commit` checks user.email =
  builtwithasmile@gmail.com). Never `git add -A`. Deploys are Josh-triggered, never from a builder.

## Traps
- `config.local.php` is gitignored; never print it. Without DB constants the app still boots.
- Don't type the quarantine folder name in a Bash command (the permission layer refuses it).
- Visual design gate (CLAUDE.md) applies to visible changes only; head meta tags are not visual.
- `/assets/favicon.svg` and `/manifest.json` referenced by the PHP layout are not in `public/`.
