# Operator guide — swens.net

## Owner
Josh Swenson.

## Accounts & services
- **Outbound email — PHP's native `mail()` via the server's local MTA.** No third-party
  SMTP vendor. Sole path for every outbound message the app sends: owner magic-link
  admin login, Gate request alerts, backup-failure alerts, and keyed check-in
  notifications (`core/helpers.php::send_mail()` is the only sanctioned caller).
  Config: `MAIL_FROM` / `MAIL_OWNER` in `config.php` (names only in
  `config.example.php`, no values).
- **MySQL** — app data (posts, members, audit log, sessions). Config: `DB_*`
  constants in `config.php`; currently commented out in `config.example.php`
  (Session 2 gate — not active until uncommented, see Admin panel below).
- **CoinGecko public markets API** — client-side only, no account or API key.
  Fetched directly from the visitor's browser by `static/mycryptowatch/index.html`
  (a standalone static page, not part of the PHP app) to show top-10 CAD crypto
  prices.

## Cron jobs
See `ops/crons.manifest`. Only `bin/backup.php` is a real (intended) cron; it is
not registered on any server today because the app is shelved (see Admin panel
below — nothing live to back up). `bin/purge-cache.php` and `bin/seed-keyed.php`
are manual CLI utilities, never scheduled.

## Admin panel
`/admin` — owner-only, passwordless magic-link login (`AuthController`, single
allowlisted `ADMIN_OWNER_EMAIL`, not multi-account). Covers posts
(`PostsController`), media uploads (`MediaController`), the keyed-circle member
list — issue/revoke/approve/rotate access keys (`MembersController`) — and a
read-only audit trail (`AuditController`).

**Not currently live.** `.cpanel.yml`'s deploy task is a disabled placeholder
(a literal `/bin/echo`) — there is no automated deploy yet. The page actually
serving swens.net today is a hand-placed static page, deployed manually outside
this pipeline; this repo's PHP app, admin panel included, is fully built but
shelved. Deploy history and revival steps live in `memory/state.md`, not here.
