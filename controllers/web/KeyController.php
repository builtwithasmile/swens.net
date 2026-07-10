<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;
use App\Services\Members;

/**
 * Consume a visitor key: /key/{token}. This IS the door for the circle — the
 * link Josh sends is the bookmark they return through. A valid, approved token
 * starts a keyed session and drops them inside. Anything else 404s (a wrong or
 * revoked key reveals nothing).
 */
class KeyController
{
    public function consume(Request $request, Response $response): void
    {
        $token  = (string) $request->param('token', '');
        $member = Members::byToken($token);

        if ($member === null) {
            abort(404, 'Not Found');
        }

        boot_session();
        session_regenerate_id(true);
        $_SESSION['is_keyed']  = true;
        $_SESSION['member_id'] = (int) $member['id'];
        $_SESSION['last_activity'] = time();

        redirect('/inside');
    }
}
