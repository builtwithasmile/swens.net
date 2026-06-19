<?php
declare(strict_types=1);

namespace App\Core;

/** Session-backed CSRF token (double-submit via the `_csrf` field / X-CSRF-Token header). */
class Csrf
{
    public static function token(): string
    {
        boot_session();
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function check(string $token): bool
    {
        boot_session();
        return !empty($_SESSION['_csrf']) && hash_equals((string) $_SESSION['_csrf'], $token);
    }
}
