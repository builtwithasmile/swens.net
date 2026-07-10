<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLog;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Template;
use App\Core\Database;
use App\Services\Members;

/**
 * Owner-only management of the keyed circle: issue / revoke / approve / rotate
 * keys, and read the board. A key = a capability URL (/key/{token}); issuing one
 * approves the member. The page is behind OwnerOnly, so the live key URLs shown
 * here sit behind Josh's own auth.
 */
class MembersController
{
    public function index(Request $request, Response $response): void
    {
        boot_session();
        $base = defined('APP_URL') ? APP_URL : '';
        $members = array_map(function (array $m) use ($base) {
            $m['key_url'] = $base . '/key/' . $m['key_token'];
            return $m;
        }, Members::all());

        $checkins = Database::fetchAll(
            "SELECT c.body, c.mood, c.created_at, m.display_name
             FROM checkins c JOIN members m ON m.id = c.member_id
             ORDER BY c.created_at DESC, c.id DESC LIMIT 50"
        );

        $html = Template::render('pages/admin/members', [
            'title'    => 'Members — Admin',
            'members'  => $members,
            'checkins' => $checkins,
            'csrf'     => csrf_field(),
            'flash'    => $_SESSION['flash'] ?? '',
        ], 'admin');
        unset($_SESSION['flash']);
        $response->html($html);
    }

    public function store(Request $request, Response $response): void
    {
        $this->verifyCsrf($request);

        $email = trim((string) $request->input('email', ''));
        $name  = trim((string) $request->input('display_name', ''));
        $rel   = trim((string) $request->input('relationship', ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 160) {
            $this->flash('A valid email is required.');
            redirect('/admin/members');
        }
        if ($name === '' || mb_strlen($name) > 80) {
            $this->flash('A display name is required (up to 80 characters).');
            redirect('/admin/members');
        }
        if (mb_strlen($rel) > 80) {
            $rel = mb_substr($rel, 0, 80);
        }

        [$ok, $result] = Members::issue($email, $name, $rel);
        if (!$ok) {
            $this->flash($result);
            redirect('/admin/members');
        }

        AuditLog::record('member.issue', "{$name} <{$email}>");
        $base = defined('APP_URL') ? APP_URL : '';
        $this->flash("Key issued for {$name}. Send them this link: {$base}/key/{$result}");
        redirect('/admin/members');
    }

    public function revoke(Request $request, Response $response): void
    {
        $this->verifyCsrf($request);
        $id = (int) $request->param('id', 0);
        $member = Members::byId($id);
        Members::setStatus($id, 'revoked');
        AuditLog::record('member.revoke', $member['display_name'] ?? (string) $id);
        $this->flash('Key revoked. That link no longer works.');
        redirect('/admin/members');
    }

    public function approve(Request $request, Response $response): void
    {
        $this->verifyCsrf($request);
        $id = (int) $request->param('id', 0);
        $member = Members::byId($id);
        Members::setStatus($id, 'approved');
        AuditLog::record('member.approve', $member['display_name'] ?? (string) $id);
        $this->flash('Key re-approved.');
        redirect('/admin/members');
    }

    public function rotate(Request $request, Response $response): void
    {
        $this->verifyCsrf($request);
        $id = (int) $request->param('id', 0);
        $member = Members::byId($id);
        if (!$member) {
            abort(404, 'Member not found.');
        }
        $token = Members::rotate($id);
        AuditLog::record('member.rotate', $member['display_name']);
        $base = defined('APP_URL') ? APP_URL : '';
        $this->flash("New key for {$member['display_name']}. The old link is dead. New link: {$base}/key/{$token}");
        redirect('/admin/members');
    }

    // -------------------------------------------------------------------------

    private function verifyCsrf(Request $request): void
    {
        boot_session();
        if (!Csrf::check((string) $request->input('_csrf', ''))) {
            abort(419, 'Invalid CSRF token.');
        }
    }

    private function flash(string $msg): void
    {
        boot_session();
        $_SESSION['flash'] = $msg;
    }
}
