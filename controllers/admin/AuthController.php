<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLog;
use App\Core\Request;
use App\Core\Response;
use App\Core\Template;
/**
 * Magic-link owner login.
 * Pattern: hash_hmac('sha256', email.'|'.expires, SESSION_SECRET), 15-min expiry.
 * Stateless — no tokens table. One owner inbox only.
 */
class AuthController
{
    public function show(Request $request, Response $response): void
    {
        boot_session();
        $html = Template::render('pages/admin/login', [
            'title'   => 'Admin Login — swens.net',
            'csrf'    => csrf_field(),
            'flash'   => $_SESSION['flash'] ?? '',
        ], 'admin');
        unset($_SESSION['flash']);
        $response->html($html);
    }

    public function send(Request $request, Response $response): void
    {
        boot_session();
        $token = $request->input('_csrf', '');
        if (!\App\Core\Csrf::check($token)) {
            abort(419, 'Invalid CSRF token.');
        }

        $email = trim((string) $request->input('email', ''));

        // Always respond the same — never reveal whether the email matched.
        if ($email === (defined('ADMIN_OWNER_EMAIL') ? ADMIN_OWNER_EMAIL : '')
            && $email !== ''
        ) {
            $expires = time() + 900; // 15 min
            $token = $this->makeToken($email, $expires);
            $link = (defined('APP_URL') ? APP_URL : '') . '/admin/auth/' . urlencode($token) . '?expires=' . $expires;
            $subject = '[swens.net] Admin login link';
            $body = "Your admin login link (valid 15 minutes):\n\n{$link}\n\nIf you did not request this, ignore it.";
            send_mail($email, $subject, $body, 'From: ' . (defined('MAIL_FROM') ? MAIL_FROM : 'noreply@swens.net'));
        }

        $_SESSION['flash'] = 'Check your email for a login link.';
        header('Location: /admin/login');
        exit;
    }

    public function consume(Request $request, Response $response): void
    {
        boot_session();
        $token   = $request->param('token', '');
        $expires = (int) $request->query('expires', '0');

        if ($expires < time()) {
            abort(404, 'Link expired.');
        }

        $ownerEmail = defined('ADMIN_OWNER_EMAIL') ? ADMIN_OWNER_EMAIL : '';
        $expected = $this->makeToken($ownerEmail, $expires);

        if (!hash_equals($expected, $token)) {
            abort(404, 'Invalid link.');
        }

        session_regenerate_id(true);
        $_SESSION['is_owner'] = true;
        $_SESSION['last_activity'] = time();
        AuditLog::record('login');

        header('Location: /admin');
        exit;
    }

    public function logout(Request $request, Response $response): void
    {
        boot_session();
        $token = $request->input('_csrf', '');
        if (!\App\Core\Csrf::check($token)) {
            abort(419, 'Invalid CSRF token.');
        }
        AuditLog::record('logout');
        session_destroy();
        header('Location: /');
        exit;
    }

    private function makeToken(string $email, int $expires): string
    {
        $secret = defined('SESSION_SECRET') ? SESSION_SECRET : '';
        return hash_hmac('sha256', $email . '|' . $expires, $secret);
    }

}
