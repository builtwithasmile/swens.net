<?php
declare(strict_types=1);

/**
 * Minimal bootstrap for the web installer and CLI/cron scripts: registers the
 * App\ autoloader and loads global helpers. Does NOT connect to the database
 * (callers do that explicitly, since the installer runs before config exists).
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

spl_autoload_register(function (string $class) {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $segments = explode('\\', substr($class, 4));
    $file = APP_ROOT . '/';
    for ($i = 0; $i < count($segments) - 1; $i++) {
        $file .= lcfirst($segments[$i]) . '/';
    }
    $file .= end($segments) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once APP_ROOT . '/core/helpers.php';

/**
 * For CLI/cron scripts only: load config.php and connect to the DB. Returns
 * false if config is missing (not yet installed).
 */
function cli_boot_db(): bool
{
    $cfg = is_file(APP_ROOT . '/config.local.php')
        ? APP_ROOT . '/config.local.php'
        : (is_file(APP_ROOT . '/config.php') ? APP_ROOT . '/config.php' : null);
    if ($cfg === null) {
        return false;
    }
    require_once $cfg;
    if (!defined('DB_HOST')) {
        // Config exists but has no DB block (pre-engine phase) — valid, just not a DB boot.
        return false;
    }
    try {
        \App\Core\Database::connect(DB_HOST, (int) DB_PORT, DB_NAME, DB_USER, DB_PASS);
    } catch (\Throwable $e) {
        return false;
    }
    if (defined('APP_TIMEZONE')) {
        date_default_timezone_set(APP_TIMEZONE);
    }
    // Apply any pending migrations so CLI/cron paths never run against a stale schema.
    \App\Core\Migrator::run(APP_ROOT . '/migrations');
    return true;
}
