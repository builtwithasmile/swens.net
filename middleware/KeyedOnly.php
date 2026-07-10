<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Gate for the keyed side (/inside, the board). Lets through an authenticated
 * keyed visitor ($_SESSION['is_keyed']) or the owner ($_SESSION['is_owner'], so
 * Josh can preview his own keyed pages). Anyone else is sent to the gate to ask
 * for a key — these are invited people, not a 404 like the admin surface.
 */
class KeyedOnly
{
    public function handle(Request $request, Response $response): void
    {
        boot_session();
        $isKeyed = !empty($_SESSION['is_keyed']);
        $isOwner = !empty($_SESSION['is_owner']);

        if (!$isKeyed && !$isOwner) {
            redirect('/gate');
        }

        // Idle timeout applies to keyed visitors only — the owner's clock is
        // tracked separately by OwnerOnly, and previewing /inside shouldn't
        // cost the owner their admin session.
        if ($isKeyed && !$isOwner
            && session_idle_expired((int) config('KEYED_IDLE_TIMEOUT_SECONDS', 7200))
        ) {
            unset($_SESSION['is_keyed'], $_SESSION['member_id']);
            redirect('/gate');
        }

        // Re-validate a keyed visitor against live status every request, so a
        // revoke (or a deleted member) takes effect immediately — not just at the
        // next /key click. One indexed SELECT; keyed pages are uncached anyway.
        if ($isKeyed && !$isOwner) {
            $mid = (int) ($_SESSION['member_id'] ?? 0);
            $member = $mid > 0 ? \App\Services\Members::byId($mid) : null;
            if (!$member || $member['status'] !== 'approved') {
                unset($_SESSION['is_keyed'], $_SESSION['member_id']);
                redirect('/gate');
            }
        }

        // The keyed tier must never be indexed or cached by any layer — not the
        // browser, not a CDN, not the LiteSpeed page cache (laws 1 & 4). Headers
        // here cover every keyed response, alongside the per-page <meta robots>.
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('Cache-Control: private, no-store, max-age=0');
    }
}
