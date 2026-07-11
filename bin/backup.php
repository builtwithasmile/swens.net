#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Nightly database backup cron (standard-kit B5). Streams a gzipped mysqldump
 * of the swens.net DB into BACKUP_DIR, copies it to BACKUP_SECONDARY_DIR when
 * set, prunes dumps older than BACKUP_RETENTION_DAYS, and emails MAIL_OWNER on
 * failure. The real work lives in App\Services\BackupService; this file is
 * just the CLI wiring (boot, exit code, owner alert).
 *
 * Not yet registered as a live cron: the app is currently shelved (the live
 * site is a static page — see memory/state.md), so there is no production DB
 * to back up. Register once the app is redeployed with a DB:
 *
 *   15 2 * * * /usr/local/bin/ea-php83 /home/swensnet/swensnet-app/bin/backup.php >> /home/swensnet/swensnet-app/storage/logs/app.log 2>&1
 *
 * (adjust the php path to the one in cPanel > Select PHP Version)
 *
 * Exit code is 0 on success and 1 on any failure, so cron's own MAILTO also
 * catches a failure even if the app's mail() alert cannot go out.
 */

// CLI only: never let this run over HTTP even if the file is somehow reachable.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../core/bootstrap.php';

if (!cli_boot_db()) {
    fwrite(STDERR, "backup: no DB/config — cannot back up (config.php missing or DB unreachable)\n");
    exit(1);
}

set_time_limit(0); // a large dump can outrun the default CLI time limit

$now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
$res = \App\Services\BackupService::run($now);

if ($res['ok']) {
    $mb  = number_format($res['bytes'] / 1048576, 2);
    $msg = 'backup ok: ' . basename((string) $res['file']) . " ({$mb} MB)"
         . ($res['pruned'] ? ', pruned ' . count($res['pruned']) . ' old' : '');
    logger($msg, 'INFO');
    fwrite(STDOUT, $msg . "\n");
    exit(0);
}

$err = 'backup FAILED: ' . ($res['error'] ?? 'unknown error');
logger($err, 'ERROR');
fwrite(STDERR, $err . "\n");

// Owner alert — best-effort; a send failure must never mask the real error
// or change the exit code the cron scheduler reads (send_mail logs and returns
// false, never throws — and it is the one sanctioned mail path, CRLF-hardened).
if (defined('MAIL_OWNER') && MAIL_OWNER !== '' && defined('MAIL_FROM')) {
    send_mail(
        (string) MAIL_OWNER,
        'swens.net backup FAILED',
        $err . "\n\n"
            . 'Site: ' . (defined('APP_URL') ? APP_URL : '(unknown)') . "\n"
            . 'Time (UTC): ' . $now->format('Y-m-d H:i:s') . "\n",
        ['From' => MAIL_FROM]
    );
}

exit(1);
