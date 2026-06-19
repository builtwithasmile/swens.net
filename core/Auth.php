<?php
declare(strict_types=1);

namespace App\Core;

/** Session-based auth against the `users` table (bcrypt). Two roles: owner, purchaser. */
class Auth
{
    private static ?array $user = null;

    public static function attempt(string $email, string $password): bool
    {
        $row = Database::fetch(
            "SELECT * FROM users WHERE email = ? AND active = 1",
            [strtolower(trim($email))]
        );
        if (!$row || !password_verify($password, (string) $row['password_hash'])) {
            return false;
        }
        boot_session();
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $row['id'];
        try {
            Database::query("UPDATE users SET last_login_at = NOW() WHERE id = ?", [(int) $row['id']]);
        } catch (\Throwable $e) {
            logger('Auth: last_login update failed: ' . $e->getMessage(), 'WARN');
        }
        self::$user = $row;
        return true;
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        boot_session();
        $uid = $_SESSION['uid'] ?? null;
        if (!$uid) {
            return null;
        }
        self::$user = Database::fetch("SELECT * FROM users WHERE id = ? AND active = 1", [(int) $uid]) ?: null;
        return self::$user;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function isOwner(): bool
    {
        return self::role() === 'owner';
    }

    public static function logout(): void
    {
        boot_session();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();
        self::$user = null;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
