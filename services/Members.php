<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * The invited circle and their keys.
 *
 * A key is a capability URL: /key/{key_token}. Josh issues it (issue = approval);
 * the recipient bookmarks it and clicks to enter. Revoke flips status so the same
 * URL stops working. No password, no expiry — built for non-technical family.
 *
 * All timestamps are written DB-side with NOW() so they share the connection's
 * UTC zone (Database sets +00:00) with posts/checkins.created_at. "New since your
 * last visit" compares last_seen_at against those, so the zones MUST match.
 */
final class Members
{
    /** Where keyed story posts live (Buildings::KEYED_SECTIONS). */
    public const BUCKET = 'inside';

    public static function newToken(): string
    {
        return bin2hex(random_bytes(32)); // 64 hex chars
    }

    /** Resolve an *approved* member from a key token. Null = no entry. */
    public static function byToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return Database::fetch(
            "SELECT * FROM members WHERE key_token = ? AND status = 'approved'",
            [$token]
        );
    }

    public static function byId(int $id): ?array
    {
        return Database::fetch("SELECT * FROM members WHERE id = ?", [$id]);
    }

    public static function all(): array
    {
        return Database::fetchAll("SELECT * FROM members ORDER BY created_at DESC");
    }

    /**
     * Issue a key = create an approved member with a fresh token.
     * @return array{0:bool,1:string} [ok, token-or-error]
     */
    public static function issue(string $email, string $displayName, ?string $relationship): array
    {
        $token = self::newToken();
        try {
            Database::query(
                "INSERT INTO members (email, display_name, relationship, status, key_token, approved_at)
                 VALUES (?, ?, ?, 'approved', ?, NOW())",
                [$email, $displayName, ($relationship ?: null), $token]
            );
        } catch (\PDOException $e) {
            // Duplicate email (uq_member_email) is the common, expected case.
            if ((int) $e->errorInfo[1] === 1062) {
                return [false, 'Someone with that email already has a key.'];
            }
            throw $e;
        }
        return [true, $token];
    }

    public static function setStatus(int $id, string $status): void
    {
        if (!in_array($status, ['pending', 'approved', 'revoked'], true)) {
            return;
        }
        $approved = $status === 'approved' ? ', approved_at = NOW()' : '';
        Database::query(
            "UPDATE members SET status = ?{$approved} WHERE id = ?",
            [$status, $id]
        );
    }

    /** Rotate a member's key (the old URL stops working). Returns the new token. */
    public static function rotate(int $id): string
    {
        $token = self::newToken();
        Database::query("UPDATE members SET key_token = ? WHERE id = ?", [$token, $id]);
        return $token;
    }

    /** Stamp the current visit. Call AFTER computing whatsNew(). */
    public static function stampSeen(int $id): void
    {
        Database::query("UPDATE members SET last_seen_at = NOW() WHERE id = ?", [$id]);
    }

    /**
     * The load-bearing mechanic: what changed since the member was last here.
     * First visit (last_seen NULL) returns nothing — everything is new by
     * definition, so flagging it adds no signal. Compared in UTC (see class doc).
     *
     * @return array{posts: array, checkins: array}
     */
    public static function whatsNew(?string $lastSeen): array
    {
        if ($lastSeen === null || $lastSeen === '') {
            return ['posts' => [], 'checkins' => []];
        }
        $posts = Database::fetchAll(
            "SELECT title, slug, kind, created_at FROM posts
             WHERE building = ? AND tier = 'keyed' AND created_at > ?
             ORDER BY created_at DESC",
            [self::BUCKET, $lastSeen]
        );
        $checkins = Database::fetchAll(
            "SELECT c.body, c.created_at, m.display_name
             FROM checkins c JOIN members m ON m.id = c.member_id
             WHERE c.created_at > ?
             ORDER BY c.created_at DESC",
            [$lastSeen]
        );
        return ['posts' => $posts, 'checkins' => $checkins];
    }
}
