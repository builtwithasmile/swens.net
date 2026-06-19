#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * CLI: purge all full-page cache files.
 * Usage: php bin/purge-cache.php
 */

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/core/bootstrap.php';

use App\Services\PublicCache;

PublicCache::purgeAll();
echo 'Public page cache purged.' . PHP_EOL;
