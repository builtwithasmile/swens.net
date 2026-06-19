<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Boot-time idempotent migration runner.
 *
 * Walks migrations/*.sql in lexicographic order, skips files already recorded
 * in `_schema_migrations`, applies the rest, and records each success.
 * Migrations must be idempotent (CREATE TABLE IF NOT EXISTS, guarded ALTERs).
 * On failure, emits a 503 and exits with operator-actionable diagnostics.
 */
class Migrator
{
    public static function run(string $migrationsDir): void
    {
        $files = glob(rtrim($migrationsDir, '/') . '/*.sql');
        if ($files === false || $files === []) {
            return;
        }
        sort($files);

        try {
            $result = Database::pdo()->query(
                "CREATE TABLE IF NOT EXISTS _schema_migrations (\n"
                . "  filename VARCHAR(255) NOT NULL PRIMARY KEY,\n"
                . "  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP\n"
                . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
            if ($result instanceof \PDOStatement) {
                $result->closeCursor();
            }
        } catch (\Throwable $e) {
            self::abort('_schema_migrations bootstrap', $e->getMessage());
        }

        try {
            $rows = Database::fetchAll("SELECT filename FROM _schema_migrations");
        } catch (\Throwable $e) {
            logger('Migrator: cannot list applied migrations: ' . $e->getMessage(), 'ERROR');
            return;
        }
        $applied = [];
        foreach ($rows as $r) {
            $applied[(string) $r['filename']] = true;
        }

        foreach ($files as $file) {
            $base = basename($file);
            if (isset($applied[$base])) {
                continue;
            }

            $sql = @file_get_contents($file);
            if ($sql === false) {
                logger("Migrator: cannot read {$base}", 'ERROR');
                continue;
            }

            foreach (self::splitStatements($sql) as $stmt) {
                try {
                    $result = Database::pdo()->query($stmt);
                    if ($result instanceof \PDOStatement) {
                        $result->closeCursor();
                    }
                } catch (\Throwable $e) {
                    logger("Migrator: {$base} failed at: " . substr($stmt, 0, 120) . ' — ' . $e->getMessage(), 'ERROR');
                    self::abort($base, $e->getMessage());
                }
            }

            try {
                Database::query("INSERT INTO _schema_migrations (filename) VALUES (?)", [$base]);
                logger("Migrator: applied {$base}", 'INFO');
            } catch (\Throwable $e) {
                logger("Migrator: cannot record {$base}: " . $e->getMessage(), 'WARN');
            }
        }
    }

    /**
     * Split a SQL script into statements via a single-pass state machine that
     * tracks quoted regions and comments so a `;` inside a string/comment does
     * not split a statement.
     */
    private static function splitStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $state = 'normal';
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            $next = $i + 1 < $len ? $sql[$i + 1] : '';
            $prev = $i > 0 ? $sql[$i - 1] : '';

            switch ($state) {
                case 'normal':
                    if ($ch === '-' && $next === '-') { $state = 'line_comment'; $i++; break; }
                    if ($ch === '/' && $next === '*') { $state = 'block_comment'; $i++; break; }
                    if ($ch === ';') {
                        $stmt = trim($current);
                        if ($stmt !== '') $statements[] = $stmt;
                        $current = '';
                        break;
                    }
                    if ($ch === "'") { $state = 'single';   $current .= $ch; break; }
                    if ($ch === '"') { $state = 'double';   $current .= $ch; break; }
                    if ($ch === '`') { $state = 'backtick'; $current .= $ch; break; }
                    $current .= $ch;
                    break;

                case 'line_comment':
                    if ($ch === "\n") { $state = 'normal'; $current .= "\n"; }
                    break;

                case 'block_comment':
                    if ($ch === '*' && $next === '/') { $state = 'normal'; $i++; }
                    break;

                case 'single':
                    $current .= $ch;
                    if ($ch === "'" && $prev !== '\\') $state = 'normal';
                    break;

                case 'double':
                    $current .= $ch;
                    if ($ch === '"' && $prev !== '\\') $state = 'normal';
                    break;

                case 'backtick':
                    $current .= $ch;
                    if ($ch === '`') $state = 'normal';
                    break;
            }
        }

        $stmt = trim($current);
        if ($stmt !== '') $statements[] = $stmt;

        return $statements;
    }

    private static function abort(string $file, string $msg): never
    {
        http_response_code(503);
        header('Retry-After: 30');
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>Schema migration in progress</title>'
            . '<body style="font-family:system-ui;background:#0a0e14;color:#e8edf4;display:flex;'
            . 'min-height:100vh;align-items:center;justify-content:center;margin:0;text-align:center">'
            . '<div><h1>Schema migration in progress</h1>'
            . '<p style="color:#94a3b8">Updating the database. Try again in a moment.</p>'
            . '<p style="color:#64748b;font-size:.85em">Operator: see storage/logs/app.log. Failed at '
            . htmlspecialchars($file, ENT_QUOTES) . '.</p></div>';
        exit;
    }
}
