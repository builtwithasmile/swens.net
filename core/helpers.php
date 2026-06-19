<?php
declare(strict_types=1);

use App\Core\HttpException;

/**
 * Append a structured line to storage/logs/app.log. Never throws (logging must
 * not be able to crash a request or a cron job).
 */
function logger(string $message, string $level = 'INFO'): void
{
    $dir = APP_ROOT . '/storage/logs';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $line = '[' . gmdate('Y-m-d H:i:s') . "] [$level] " . $message . PHP_EOL;
    @file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

/** HTML-escape for templates. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Render a template partial from within a view (no layout wrapping). */
function partial(string $name, array $data = []): string
{
    return \App\Core\Template::partial($name, $data);
}

/** Read a config constant with a default. */
function config(string $key, mixed $default = null): mixed
{
    return defined($key) ? constant($key) : $default;
}

/** Abort the current request with an HTTP status (caught by App::run). */
function abort(int $code, string $message = ''): never
{
    throw new HttpException($code, $message);
}

/** Send a redirect and stop. */
function redirect(string $url, int $status = 302): never
{
    header('Location: ' . $url, true, $status);
    exit;
}

/**
 * Start the PHP session once, with the configured name + secure cookie params.
 * Safe to call repeatedly.
 */
function boot_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name(defined('SESSION_NAME') ? SESSION_NAME : 'cdd_session');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (defined('APP_ENV') && APP_ENV === 'production'),
    ]);
    session_start();
}

function csrf_token(): string
{
    return \App\Core\Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Format a number as dollars. */
function dollars(float|int|null $n): string
{
    return '$' . number_format((float) $n, 2);
}

/** Format a 0..1 ratio as a percent string. */
function pct(float|int|null $n, int $dp = 1): string
{
    return number_format((float) $n * 100, $dp) . '%';
}
