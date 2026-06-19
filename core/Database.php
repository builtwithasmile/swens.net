<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use PDOException;

/**
 * Thin static PDO wrapper (MariaDB, utf8mb4). Ported verbatim from the house idiom.
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function connect(string $host, int $port, string $dbName, string $user, string $pass): void
    {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";
        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 10,
        ]);
        self::$pdo->exec("SET time_zone = '+00:00'");
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            throw new \RuntimeException('Database not connected. Call Database::connect() first.');
        }
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchColumn(string $sql, array $params = []): mixed
    {
        return self::query($sql, $params)->fetchColumn();
    }

    public static function insert(string $table, array $data): int|string
    {
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $cols);
        $sql = "INSERT INTO `$table` (`" . implode('`, `', $cols) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        self::query($sql, $data);
        return self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        [$sql, $params] = self::buildUpdate($table, $data, $where, $whereParams);
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Build the UPDATE statement and its params, matching the SET placeholder
     * style to $whereParams: positional WHERE ('id = ?', [$id]) gets positional
     * SET placeholders, named WHERE ('id = :id', ['id' => $id]) keeps :set_col.
     * PDO forbids mixing the two styles in one statement (HY093), so a
     * $whereParams array that mixes integer and string keys throws.
     *
     * @return array{0: string, 1: array}
     */
    public static function buildUpdate(string $table, array $data, string $where, array $whereParams = []): array
    {
        $intKeys = count(array_filter(array_keys($whereParams), 'is_int'));
        if ($intKeys > 0 && $intKeys < count($whereParams)) {
            throw new \InvalidArgumentException(
                'Database::update(): $whereParams mixes positional and named keys; use one style ("id = ?" with [$id], or "id = :id" with [\'id\' => $id]).'
            );
        }

        $sets = [];
        $params = [];
        if ($intKeys > 0) {
            foreach ($data as $col => $val) {
                $sets[] = "`$col` = ?";
                $params[] = $val;
            }
            $params = array_merge($params, array_values($whereParams));
        } else {
            foreach ($data as $col => $val) {
                $sets[] = "`$col` = :set_$col";
                $params["set_$col"] = $val;
            }
            $params += $whereParams;
        }
        $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE $where";
        return [$sql, $params];
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        return self::query("DELETE FROM `$table` WHERE $where", $params)->rowCount();
    }

    public static function transaction(callable $fn): mixed
    {
        self::pdo()->beginTransaction();
        try {
            $result = $fn(self::pdo());
            self::pdo()->commit();
            return $result;
        } catch (\Throwable $e) {
            self::pdo()->rollBack();
            throw $e;
        }
    }

    public static function tableExists(string $table): bool
    {
        try {
            self::query("SELECT 1 FROM `$table` LIMIT 1");
            return true;
        } catch (PDOException) {
            return false;
        }
    }
}
