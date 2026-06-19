<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Template;
use App\Services\GateLimiter;

class GateController
{
    public function show(Request $request, Response $response): void
    {
        boot_session();

        $sent = $request->query('sent') === '1';

        $html = Template::render('pages/gate', [
            'title'     => 'The Gate — swens.net',
            'meta_desc' => 'Who is Swens, and how to ask for a key.',
            'active'    => 'gate',
            'sent'      => $sent,
            'errors'    => [],
            'old'       => [],
        ], 'site');

        $response->html($html);
    }

    public function submit(Request $request, Response $response): void
    {
        boot_session();

        // a. CSRF check — invalid = silent drop (log + redirect)
        $csrfToken = (string) $request->input('_csrf', '');
        if (!Csrf::check($csrfToken)) {
            logger('Gate: CSRF mismatch from ' . $request->ip(), 'WARN');
            $response->redirect('/gate?sent=1');
        }

        // b. Honeypot — non-empty = silent drop
        $website = (string) $request->input('website', '');
        if ($website !== '') {
            logger('Gate: honeypot triggered from ' . $request->ip(), 'INFO');
            $response->redirect('/gate?sent=1');
        }

        // c. Validate fields — real humans get error feedback
        $name    = trim((string) $request->input('name', ''));
        $contact = trim((string) $request->input('contact', ''));
        $message = trim((string) $request->input('message', ''));
        $errors  = [];

        if ($name === '' || mb_strlen($name) > 80) {
            $errors['name'] = 'Please enter a name (up to 80 characters).';
        }
        if ($contact === '' || mb_strlen($contact) > 160) {
            $errors['contact'] = 'Please enter your email or how I know you (up to 160 characters).';
        }
        if ($message === '' || mb_strlen($message) > 2000) {
            $errors['message'] = 'Please enter a message (up to 2000 characters).';
        }

        if ($errors !== []) {
            $html = Template::render('pages/gate', [
                'title'     => 'The Gate — swens.net',
                'meta_desc' => 'Who is Swens, and how to ask for a key.',
                'active'    => 'gate',
                'sent'      => false,
                'errors'    => $errors,
                'old'       => ['name' => $name, 'contact' => $contact, 'message' => $message],
            ], 'site');
            // Return 200 so the browser shows the form (not a redirect — preserve input)
            header('Content-Type: text/html; charset=utf-8');
            echo $html;
            exit;
        }

        // d. Rate limit — silent drop on hit
        $limiter = new GateLimiter();
        if (!$limiter->allow($request->ip())) {
            logger('Gate: rate limit hit from ' . $request->ip(), 'INFO');
            $response->redirect('/gate?sent=1');
        }

        // e. Send mail and PRG redirect
        $this->sendMail($name, $contact, $message, $request);
        $response->redirect('/gate?sent=1');
    }

    // -------------------------------------------------------------------------

    private function sendMail(string $name, string $contact, string $message, Request $request): void
    {
        $to      = defined('MAIL_OWNER') ? MAIL_OWNER : '';
        $from    = defined('MAIL_FROM')  ? MAIL_FROM  : '';

        if ($to === '') {
            logger('Gate: MAIL_OWNER not configured — mail skipped', 'WARN');
            return;
        }

        // Static subject — no user input in any header (header-injection-proof)
        $subject = '[swens.net] Gate request';

        $ts  = gmdate('Y-m-d H:i:s') . ' UTC';
        $ua  = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200);
        $ip  = $request->ip();

        $body = implode("\n", [
            'Name:    ' . $name,
            'Contact: ' . $contact,
            '',
            $message,
            '',
            '---',
            'IP:        ' . $ip,
            'User-agent: ' . $ua,
            'Sent:      ' . $ts,
        ]);

        $headers = "From: {$from}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        // Reply-To only if contact is a valid email address
        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $headers .= 'Reply-To: ' . $contact . "\r\n";
        }

        @mail($to, $subject, $body, $headers);
    }
}
