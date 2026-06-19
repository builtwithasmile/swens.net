<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Template;
use App\Core\Database;
use App\Services\Markdown;
use App\Services\Members;

/**
 * The keyed side — the real version, behind the door. Never cached, never
 * indexed (robots=noindex), and reachable only through KeyedOnly. The story is
 * Josh's seeded words (posts tier='keyed', building='inside'); the board is live.
 */
class InsideController
{
    public function index(Request $request, Response $response): void
    {
        boot_session();
        $memberId = (int) ($_SESSION['member_id'] ?? 0);
        $member   = $memberId > 0 ? Members::byId($memberId) : null;

        // The load-bearing mechanic: what changed since they were last here.
        // Read BEFORE stamping this visit. Owner-preview (no member) = nothing flagged.
        $whatsNew = $member ? Members::whatsNew($member['last_seen_at'] ?? null) : ['posts' => [], 'checkins' => []];

        $sections = $this->keyedSections();
        $checkins = $this->checkins();

        $html = Template::render('pages/inside', [
            'title'     => 'Inside — swens.net',
            'meta_desc' => '',
            'robots'    => 'noindex,nofollow,noarchive',
            'active'    => 'inside',
            'member'    => $member,
            'isOwner'   => !empty($_SESSION['is_owner']),
            'whatsNew'  => $whatsNew,
            'sections'  => $sections,
            'checkins'  => $checkins,
            'csrf'      => csrf_field(),
        ], 'site');

        // Stamp the visit now that "what's new" has been computed against the old value.
        if ($member) {
            Members::stampSeen($memberId);
        }

        $response->html($html);
    }

    public function checkin(Request $request, Response $response): void
    {
        boot_session();
        $memberId = (int) ($_SESSION['member_id'] ?? 0);

        // Only a keyed member can leave a note (owner-preview has no member row).
        if ($memberId <= 0) {
            $response->redirect('/inside#board');
        }

        // CSRF — silent return on mismatch.
        if (!Csrf::check((string) $request->input('_csrf', ''))) {
            logger('Checkin: CSRF mismatch from member ' . $memberId, 'WARN');
            $response->redirect('/inside#board');
        }

        // Honeypot — bots fill hidden fields; humans never see it.
        if ((string) $request->input('website', '') !== '') {
            $response->redirect('/inside#board');
        }

        $body = trim((string) $request->input('body', ''));
        $mood = trim((string) $request->input('mood', ''));

        if ($body === '' || mb_strlen($body) > 2000 || mb_strlen($mood) > 40) {
            $response->redirect('/inside#board');
        }

        // Light per-member flood guard (the circle is invited, so this is just a backstop).
        $recent = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM checkins WHERE member_id = ? AND created_at > (NOW() - INTERVAL 1 HOUR)",
            [$memberId]
        );
        if ($recent >= 20) {
            logger('Checkin: flood guard hit for member ' . $memberId, 'INFO');
            $response->redirect('/inside#board');
        }

        Database::insert('checkins', [
            'member_id' => $memberId,
            'body'      => $body,
            'mood'      => ($mood !== '' ? $mood : null),
        ]);

        $response->redirect('/inside#board');
    }

    // -------------------------------------------------------------------------

    /**
     * Josh's seeded keyed posts, grouped by kind and rendered to HTML.
     * Single-instance sections (welcome/about/board) collapse to one; threads
     * (now/story) stay as lists, newest first, so they can grow over time.
     *
     * @return array{welcome:?array, about:?array, board:?array, now:array, story:array}
     */
    private function keyedSections(): array
    {
        $rows = Database::fetchAll(
            "SELECT title, slug, kind, body_md, created_at FROM posts
             WHERE building = ? AND tier = 'keyed'
             ORDER BY created_at DESC, id DESC",
            [Members::BUCKET]
        );

        $grouped = ['welcome' => null, 'about' => null, 'board' => null, 'now' => [], 'story' => []];
        foreach ($rows as $r) {
            $r['body_html'] = Markdown::render($r['body_md']);
            switch ($r['kind']) {
                case 'welcome': $grouped['welcome'] ??= $r; break;
                case 'about':   $grouped['about']   ??= $r; break;
                case 'board':   $grouped['board']   ??= $r; break;
                case 'now':     $grouped['now'][]   = $r;   break;
                case 'story':   $grouped['story'][] = $r;   break;
            }
        }
        return $grouped;
    }

    /** The presence board: every note, newest first, attributed to its author. */
    private function checkins(): array
    {
        return Database::fetchAll(
            "SELECT c.body, c.mood, c.created_at, m.display_name
             FROM checkins c JOIN members m ON m.id = c.member_id
             ORDER BY c.created_at DESC, c.id DESC
             LIMIT 200"
        );
    }
}
