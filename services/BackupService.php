<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Nightly DB backup (standard-kit B5), invoked by bin/backup.php from cron.
 *
 * Design constraints (Selvatec stack, prod = cPanel shared hosting):
 *   - exec/shell_exec/system are DISABLED on prod; proc_open is not. mysqldump
 *     is therefore launched with proc_open's ARRAY form (execvp, no shell), so
 *     there is no shell to disable and no shell-quoting to get wrong.
 *   - The DB password is passed through the MYSQL_PWD environment variable,
 *     never on the command line: argv is visible to every other tenant via the
 *     process list, and a password file is an extra secret on disk to clean up.
 *   - The dump is streamed straight into a gzip stream, so a multi-hundred-MB
 *     dump never has to fit in PHP's 128M memory limit.
 *   - Flags are the intersection that MariaDB 10.11 (the confirmed prod engine)
 *     and MySQL both accept. No MySQL-8-only flag (e.g. --no-tablespaces,
 *     --set-gtid-purged) — MariaDB's mysqldump rejects those as unknown options
 *     and the whole backup would fail. MariaDB does not query the tablespace
 *     table the way MySQL 8 does, so it does not need them.
 */
class BackupService
{
    /** Only files this service created are ever eligible for pruning. */
    private const NAME_GLOB = 'swensnet-*.sql.gz';

    /**
     * Timestamped backup filename (pure — the one testable seam that pins the
     * naming convention prune() relies on). UTC to avoid DST-fold ambiguity.
     */
    public static function filename(\DateTimeImmutable $now): string
    {
        return 'swensnet-' . $now->format('Ymd-His') . '.sql.gz';
    }

    /**
     * Stream a gzipped mysqldump of $db into $outFile via proc_open.
     *
     * @param array{host:string,port:int,name:string,user:string,pass:string} $db
     * @return array{ok:bool, bytes:int, error:?string}
     *   bytes = on-disk size of the compressed dump on success, 0 on failure.
     *   On failure the partial/empty output file is removed — a backup that
     *   exists but is truncated is worse than no file, because it looks valid.
     */
    public static function dump(string $mysqldumpBin, array $db, string $outFile): array
    {
        $args = [
            $mysqldumpBin,
            '--host=' . $db['host'],
            '--port=' . (string) $db['port'],
            '--user=' . $db['user'],
            '--single-transaction',   // consistent InnoDB snapshot, no table locks
            '--quick',                // stream rows, don't buffer the whole table
            '--default-character-set=utf8mb4',
            $db['name'],
        ];

        // stderr goes to its own file (not a pipe) so it can never fill its pipe
        // buffer and deadlock the child while we're busy draining stdout.
        $errFile = $outFile . '.err';
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['file', $errFile, 'w'],
        ];

        // Preserve the inherited environment (PATH — so a bare "mysqldump"
        // resolves) and add the password. Passing a fresh env array to
        // proc_open REPLACES the environment, so we must merge, not overwrite.
        $env = getenv();
        if (!is_array($env)) {
            $env = [];
        }
        $env['MYSQL_PWD'] = $db['pass'];

        $proc = @proc_open($args, $descriptors, $pipes, null, $env);
        if (!is_resource($proc)) {
            @unlink($errFile);
            return ['ok' => false, 'bytes' => 0, 'error' => 'mysqldump could not be started (proc_open failed for: ' . $mysqldumpBin . ')'];
        }

        $gz = @gzopen($outFile, 'wb6');
        if ($gz === false) {
            // Drain + close the child so it isn't left as a zombie.
            if (isset($pipes[1]) && is_resource($pipes[1])) {
                stream_get_contents($pipes[1]);
                fclose($pipes[1]);
            }
            proc_close($proc);
            @unlink($errFile);
            return ['ok' => false, 'bytes' => 0, 'error' => 'cannot open backup file for writing: ' . $outFile];
        }

        $read = 0;
        while (!feof($pipes[1])) {
            $chunk = fread($pipes[1], 1 << 16);
            if ($chunk === false) {
                break;
            }
            if ($chunk !== '') {
                gzwrite($gz, $chunk);
                $read += strlen($chunk);
            }
        }
        fclose($pipes[1]);
        gzclose($gz);
        $exit = proc_close($proc);

        $err = is_file($errFile) ? trim((string) @file_get_contents($errFile)) : '';
        @unlink($errFile);

        // A real dump always produces output (even an empty schema emits its
        // header). Zero bytes read means auth/connection failed before any data.
        if ($exit !== 0 || $read === 0) {
            @unlink($outFile);
            return [
                'ok'    => false,
                'bytes' => 0,
                'error' => $err !== '' ? $err : "mysqldump exited $exit with no output",
            ];
        }

        return ['ok' => true, 'bytes' => is_file($outFile) ? (int) filesize($outFile) : 0, 'error' => null];
    }

    /**
     * Delete this service's own backups older than $retentionDays (by mtime).
     * Safety rails, because a bad prune is unrecoverable:
     *   - never deletes the single newest backup, whatever its age (so a store
     *     that stops running the cron still keeps its last good dump);
     *   - only ever touches files matching NAME_GLOB;
     *   - a retention of <= 0 prunes NOTHING (a misconfigured 0 must not be read
     *     as "delete everything").
     *
     * @return list<string> basenames actually deleted
     */
    public static function prune(string $dir, int $retentionDays, \DateTimeImmutable $now): array
    {
        if ($retentionDays <= 0 || !is_dir($dir)) {
            return [];
        }
        $files = glob(rtrim($dir, '/\\') . '/' . self::NAME_GLOB) ?: [];
        if (count($files) <= 1) {
            return []; // the only file present is, by definition, the newest
        }
        usort($files, fn($a, $b) => filemtime($a) <=> filemtime($b));
        array_pop($files); // hold back the newest — never eligible

        $cutoff = $now->getTimestamp() - $retentionDays * 86400;
        $deleted = [];
        foreach ($files as $f) {
            if (filemtime($f) < $cutoff && @unlink($f)) {
                $deleted[] = basename($f);
            }
        }
        return $deleted;
    }

    /**
     * Full run wired to config.php constants: dump -> optional secondary copy
     * -> prune both locations. Never throws; the cron reads the result array.
     *
     * @return array{ok:bool, file:?string, bytes:int, pruned:list<string>, error:?string}
     */
    public static function run(\DateTimeImmutable $now): array
    {
        $fail = fn(string $error): array => ['ok' => false, 'file' => null, 'bytes' => 0, 'pruned' => [], 'error' => $error];

        if (!defined('BACKUP_DIR') || !defined('DB_HOST')) {
            return $fail('backup not configured (BACKUP_DIR / DB_* missing from config.php)');
        }

        $dir = rtrim((string) BACKUP_DIR, '/\\');
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            return $fail('cannot create backup directory: ' . $dir);
        }

        $file = $dir . '/' . self::filename($now);
        $db = [
            'host' => (string) DB_HOST,
            'port' => defined('DB_PORT') ? (int) DB_PORT : 3306,
            'name' => (string) DB_NAME,
            'user' => (string) DB_USER,
            'pass' => (string) DB_PASS,
        ];
        $bin = defined('MYSQLDUMP_BIN') ? (string) MYSQLDUMP_BIN : 'mysqldump';

        $res = self::dump($bin, $db, $file);
        if (!$res['ok']) {
            return $fail($res['error'] ?? 'unknown dump error');
        }

        // Optional off-primary copy to a second location the installer can set
        // (e.g. a path outside the docroot, or a mounted backup volume).
        $secondary = defined('BACKUP_SECONDARY_DIR') ? rtrim((string) BACKUP_SECONDARY_DIR, '/\\') : '';
        if ($secondary !== '' && (is_dir($secondary) || @mkdir($secondary, 0750, true))) {
            @copy($file, $secondary . '/' . self::filename($now));
        }

        $retention = defined('BACKUP_RETENTION_DAYS') ? (int) BACKUP_RETENTION_DAYS : 30;
        $pruned = self::prune($dir, $retention, $now);
        if ($secondary !== '') {
            self::prune($secondary, $retention, $now);
        }

        return ['ok' => true, 'file' => $file, 'bytes' => $res['bytes'], 'pruned' => $pruned, 'error' => null];
    }
}
