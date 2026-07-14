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

/**
 * True if the session's last recorded activity is older than $timeoutSeconds.
 * Always stamps 'last_activity' to now as a side effect, so every call that
 * doesn't trip the timeout also resets the clock for the next one.
 */
function session_idle_expired(int $timeoutSeconds): bool
{
    $now = time();
    $last = $_SESSION['last_activity'] ?? null;
    $_SESSION['last_activity'] = $now;
    return $last !== null && ($now - $last) > $timeoutSeconds;
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

/**
 * CRLF-hardened wrapper around mail(). Strips any \r or \n from the
 * recipient, subject, and every header line so untrusted input (e.g. a
 * contact address echoed into Reply-To) can never inject extra headers or
 * an envelope-recipient change. $headers may be a single string (mail()'s
 * native \r\n-joined form) or an array of "Name: value" lines.
 *
 * Always the ONLY way this app sends outbound mail — never call mail()
 * directly (Selvatec security defaults).
 */
function send_mail(string $to, string $subject, string $body, string|array $headers = ''): bool
{
    $strip = static fn (string $v): string => str_replace(["\r", "\n"], '', $v);

    $to      = $strip($to);
    $subject = $strip($subject);

    if (is_array($headers)) {
        $headerLines = array_map($strip, $headers);
    } else {
        $headerLines = array_filter(array_map($strip, preg_split('/\r\n|\r|\n/', $headers) ?: []), static fn (string $l): bool => $l !== '');
    }
    $headerString = implode("\r\n", $headerLines);

    if ($to === '' || $subject === '') {
        logger('send_mail: empty to/subject after sanitizing — send skipped', 'WARN');
        return false;
    }

    // Envelope-sender (-f) must match MAIL_FROM or the header/envelope mismatch
    // reads as a spam signal at strict receivers (e.g. Gmail/Workspace), even
    // with SPF/DKIM/DMARC otherwise correct (crossroads-dd 2026-07-14: password
    // reset mail() reported success but was never seen by the recipient).
    $envelopeFrom = (defined('MAIL_FROM') && MAIL_FROM !== '') ? '-f' . MAIL_FROM : '';
    return @mail($to, $subject, $body, $headerString, $envelopeFrom);
}
