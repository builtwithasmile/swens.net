<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLog;
use App\Core\Request;
use App\Core\Response;
use App\Core\Template;

/** Owner-only: read-only view of the audit trail (login/logout, member key
 * actions, post changes). Nothing here writes — AuditLog::record() is called
 * from the controllers that perform the actual actions. */
class AuditController
{
    public function index(Request $request, Response $response): void
    {
        $html = Template::render('pages/admin/audit', [
            'title' => 'Audit trail — Admin',
            'log'   => AuditLog::recent(200),
        ], 'admin');
        $response->html($html);
    }
}
