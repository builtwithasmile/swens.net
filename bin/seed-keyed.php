<?php
declare(strict_types=1);

/**
 * Seed the keyed story content into `posts` (tier='keyed', building='inside').
 *
 * PROVENANCE LAW: the body is read VERBATIM from docs/content-seeds/*.md — Josh's
 * own words, never retyped here. This script only strips the leading <!-- LAYER -->
 * authoring note and lifts the first `# H1` into the post title. No prose is authored.
 *
 * Idempotent: upserts by (building='inside', slug). Safe to run on every deploy.
 * Run: php bin/seed-keyed.php
 */

require __DIR__ . '/../core/bootstrap.php';

use App\Core\Database;

if (!cli_boot_db()) {
    fwrite(STDERR, "seed-keyed: no DB (config + MariaDB required). Nothing seeded.\n");
    exit(1);
}

$dir = APP_ROOT . '/docs/content-seeds';

// file => post identity. 'cut' truncates the body at a heading whose live half
// (the check-in list) is rendered dynamically, not seeded.
$seeds = [
    ['file' => '02_keyed_welcome.md',                  'slug' => 'welcome',      'kind' => 'welcome'],
    ['file' => '07_keyed_what-this-place-is.md',       'slug' => 'what-this-is', 'kind' => 'about'],
    ['file' => '03_keyed_archive_hotsync-film-era.md', 'slug' => 'long-stretch', 'kind' => 'story'],
    ['file' => '04_keyed_now_costa-rica-bike.md',      'slug' => 'now',          'kind' => 'now'],
    ['file' => '05_keyed_presence-board.md',           'slug' => 'the-board',    'kind' => 'board', 'cut' => '## Checked in'],
];

$seeded = 0;
foreach ($seeds as $s) {
    $path = $dir . '/' . $s['file'];
    $raw = @file_get_contents($path);
    if ($raw === false) {
        fwrite(STDERR, "seed-keyed: missing {$s['file']} — skipped\n");
        continue;
    }

    // 1. Strip the leading authoring note (<!-- ... -->), verbatim otherwise.
    $text = trim(preg_replace('/<!--.*?-->/s', '', $raw));

    // 2. Lift the first H1 as the title; the rest is the body, untouched.
    $title = $s['slug'];
    if (preg_match('/^#\s+(.+)$/m', $text, $m, PREG_OFFSET_CAPTURE)) {
        $title = trim($m[1][0]);
        $body  = trim(substr($text, $m[0][1] + strlen($m[0][0])));
    } else {
        $body = $text;
    }

    // 3. For the board: keep Josh's intro + status block; the check-in list below
    //    "## Checked in" is the live system, not seed content.
    if (!empty($s['cut'])) {
        $pos = strpos($body, $s['cut']);
        if ($pos !== false) {
            $body = trim(substr($body, 0, $pos));
        }
    }

    $existing = Database::fetch(
        "SELECT id FROM posts WHERE building = 'inside' AND slug = ?",
        [$s['slug']]
    );
    $fields = [
        'tier'    => 'keyed',
        'kind'    => $s['kind'],
        'title'   => $title,
        'body_md' => $body,
    ];
    if ($existing) {
        Database::update('posts', $fields, 'id = :id', ['id' => (int) $existing['id']]);
        echo "updated  keyed/{$s['slug']}  ({$title})\n";
    } else {
        Database::insert('posts', $fields + [
            'building' => 'inside',
            'slug'     => $s['slug'],
            'tags'     => null,
        ]);
        echo "inserted keyed/{$s['slug']}  ({$title})\n";
    }
    $seeded++;
}

echo "seed-keyed: {$seeded} keyed posts in place.\n";
