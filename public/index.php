<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Project root is one level above the web docroot (public/).
define('APP_ROOT', dirname(__DIR__));

// Local override takes precedence when present (gitignored; never committed).
if (file_exists(APP_ROOT . '/config.local.php')) {
    require APP_ROOT . '/config.local.php';
} elseif (!file_exists(APP_ROOT . '/config.php')) {
    // First run: no config yet → web installer.
    require APP_ROOT . '/install.php';
    exit;
} else {
    require APP_ROOT . '/config.php';
}
require APP_ROOT . '/core/helpers.php';
require APP_ROOT . '/core/App.php';

$app = new \App\Core\App(APP_ROOT);

require APP_ROOT . '/routes.php';

$app->run();
