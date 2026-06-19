<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Middleware: allows only the authenticated owner through to admin routes.
 * Checks $_SESSION['is_owner']. Returns 404 (not 401/403) so admin does not
 * advertise itself to the public.
 */
class OwnerOnly
{
    public function handle(Request $request, Response $response): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(defined('SESSION_NAME') ? SESSION_NAME : 'swens_session');
            session_start();
        }
        if (empty($_SESSION['is_owner'])) {
            abort(404, 'Not Found');
        }
    }
}
