<?php
// swens.net — Porch — configuration template.
// Copy to config.php at the project root and fill in real values.
// config.php is gitignored. NEVER commit it.
// This file lists every variable name with NO values — that is what Claude reads.

declare(strict_types=1);

// ---------- Environment ----------
define('APP_ENV', 'production');            // 'production' | 'development'
define('APP_URL', 'https://swens.net');
define('APP_TIMEZONE', 'America/Costa_Rica');

// ---------- Session ----------
define('SESSION_NAME', 'swens_session');
define('SESSION_SECRET', '');               // bin2hex(random_bytes(32)); used by S2 magic links

// Idle logout (optional — defaults below apply if left undefined):
// define('ADMIN_IDLE_TIMEOUT_SECONDS', 1800);  // 30 min, owner admin session
// define('KEYED_IDLE_TIMEOUT_SECONDS', 7200);  // 2 hr, keyed-circle session

// ---- The Gate (Session 1) ----
define('MAIL_FROM', '');                    // domain-aligned mailbox, e.g. gate@swens.net
define('MAIL_OWNER', '');                   // Josh's inbox — gate requests land here
define('GATE_MAX_PER_IP_PER_HOUR', 3);
define('GATE_MAX_PER_DAY_GLOBAL', 20);

// ---- Session 2 — uncomment when the engine lands ----
// define('DB_HOST', 'localhost'); define('DB_PORT', 3306);
// define('DB_NAME', ''); define('DB_USER', ''); define('DB_PASS', '');
// define('ADMIN_OWNER_EMAIL', '');         // the single allowlisted admin email
// define('APP_KEY', '');                   // bin2hex(random_bytes(32)); HMAC key for magic links (alias SESSION_SECRET)
