<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Append-only trail of privileged admin actions (login/logout, member key
 * issue/revoke/approve/rotate, post create/update/delete). Never throws — a
 * logging failure must not break the action it's recording.
 */
class AuditLog
{
    public static function record(string $action, ?string $subject = null, ?string $meta = null): void
    {
        try {
            Database::insert('audit_log', [
                'actor'   => defined('ADMIN_OWNER_EMAIL') ? ADMIN_OWNER_EMAIL : 'owner',
                'action'  => $action,
                'subject' => $subject,
                'meta'    => $meta,
                'ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\Throwable $e) {
            logger('AuditLog: failed to record "' . $action . '": ' . $e->getMessage(), 'WARN');
        }
    }

    public static function recent(int $limit = 200): array
    {
        return Database::fetchAll(
            'SELECT actor, action, subject, meta, ip, created_at FROM audit_log ORDER BY created_at DESC, id DESC LIMIT ' . max(1, min($limit, 1000))
        );
    }
}
