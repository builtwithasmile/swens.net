<?php
/**
 * The keyed side. Rendered only behind KeyedOnly; layout emits robots=noindex.
 * Story content is Josh's seeded words (provenance law); the board is live.
 * Voice/design: interior paper-ink world, same vocabulary as the public pages.
 */
$welcome = $sections['welcome'] ?? null;
$about   = $sections['about'] ?? null;
$board   = $sections['board'] ?? null;
$nows    = $sections['now'] ?? [];
$stories = $sections['story'] ?? [];
$newPosts    = $whatsNew['posts'] ?? [];
$newCheckins = $whatsNew['checkins'] ?? [];
$hasNew      = ($newPosts || $newCheckins);
?>

<?php if (!empty($isOwner) && empty($member)): ?>
<section class="section-pad narrow" style="padding-bottom:0">
  <p class="eyebrow" style="color:var(--accent)">Owner preview — this is what the circle sees.</p>
</section>
<?php endif; ?>

<!-- Welcome -->
<?php if ($welcome): ?>
<section class="section-pad narrow">
  <p class="eyebrow">Inside</p>
  <h1 class="h2-display"><?= e($welcome['title']) ?></h1>
  <div class="prose" style="margin-top:1rem;color:var(--muted);line-height:1.7"><?= $welcome['body_html'] ?></div>
</section>
<?php endif; ?>

<!-- New since your last visit — the load-bearing mechanic -->
<?php if ($hasNew): ?>
<section class="section-pad narrow" style="padding-top:0">
  <div style="border:1px solid var(--accent);border-radius:8px;padding:1.25rem 1.5rem">
    <p class="eyebrow" style="color:var(--accent);margin-bottom:.5rem">New since you were last here</p>
    <ul style="list-style:none;padding:0;margin:0;display:grid;gap:.4rem">
      <?php foreach ($newPosts as $p): ?>
      <li style="font-size:.9375rem">
        <a href="#<?= e($p['kind'] === 'now' ? 'now' : ($p['kind'] === 'story' ? 'story' : 'about')) ?>" style="color:var(--text)">
          <?= e($p['title']) ?></a>
        <span style="color:var(--muted)"> · updated <?= e(substr($p['created_at'], 0, 10)) ?></span>
      </li>
      <?php endforeach; ?>
      <?php foreach ($newCheckins as $c): ?>
      <li style="font-size:.9375rem">
        <a href="#board" style="color:var(--text)"><?= e($c['display_name']) ?> checked in</a>
        <span style="color:var(--muted)"> · <?= e(substr($c['created_at'], 0, 10)) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<!-- Wayfinding -->
<section class="section-pad narrow" style="padding-top:0;padding-bottom:0">
  <nav aria-label="Inside" style="display:flex;flex-wrap:wrap;gap:1.25rem;font-size:.875rem">
    <a href="#board" style="color:var(--accent)">The board</a>
    <?php if ($nows): ?><a href="#now" style="color:var(--accent)">Now</a><?php endif; ?>
    <?php if ($stories): ?><a href="#story" style="color:var(--accent)">The long stretch</a><?php endif; ?>
    <?php if ($about): ?><a href="#about" style="color:var(--accent)">What this place is</a><?php endif; ?>
  </nav>
</section>

<!-- The board: status (Josh's words) + live check-ins -->
<section id="board" class="section-pad">
  <div class="narrow">
    <?php if ($board): ?>
    <div class="prose" style="color:var(--muted);line-height:1.7"><?= $board['body_html'] ?></div>
    <?php endif; ?>

    <h2 class="eyebrow" style="margin-top:2rem">Checked in</h2>
    <p class="lead" style="font-size:.9375rem">Newest on top. Name, date, a line. That's it.</p>

    <?php if (!empty($member)): ?>
    <form method="post" action="/inside/checkin" style="margin:1.5rem 0;display:grid;gap:.75rem;max-width:36rem">
      <?= $csrf ?? '' ?>
      <div style="position:absolute;left:-9999px" aria-hidden="true">
        <label>Leave this blank<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
      </div>
      <label style="font-size:.875rem;color:var(--muted)">Your note
        <textarea name="body" rows="3" maxlength="2000" required
          style="width:100%;margin-top:.35rem;padding:.6rem .7rem;background:transparent;border:1px solid var(--line);border-radius:6px;color:var(--text);font:inherit"></textarea>
      </label>
      <label style="font-size:.875rem;color:var(--muted)">Mood (optional)
        <input type="text" name="mood" maxlength="40"
          style="width:14rem;margin-top:.35rem;padding:.5rem .7rem;background:transparent;border:1px solid var(--line);border-radius:6px;color:var(--text);font:inherit">
      </label>
      <div><button type="submit" style="min-height:44px;padding:.7rem 1.4rem;background:var(--accent);color:var(--on-accent);border:0;border-radius:6px;font:inherit;font-weight:600;cursor:pointer">Say hi</button></div>
    </form>
    <?php else: ?>
    <p class="lead" style="font-size:.9375rem;color:var(--muted)">(Sign in with your key to leave a note.)</p>
    <?php endif; ?>

    <?php if (empty($checkins)): ?>
    <p class="lead" style="color:var(--muted)">Nobody's signed the book yet. Be the first.</p>
    <?php else: ?>
    <div class="checkins" style="margin-top:1rem;display:grid;gap:1.1rem">
      <?php foreach ($checkins as $c): ?>
      <div style="border-bottom:1px solid var(--line);padding-bottom:1.1rem">
        <p style="margin:0;font-size:.8125rem;color:var(--muted)">
          <strong style="color:var(--text)"><?= e($c['display_name']) ?></strong>
          · <?= e(substr($c['created_at'], 0, 10)) ?>
          <?php if (!empty($c['mood'])): ?> · <em><?= e($c['mood']) ?></em><?php endif; ?>
        </p>
        <p style="margin:.35rem 0 0;line-height:1.6"><?= nl2br(e($c['body'])) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- Now -->
<?php if ($nows): ?>
<section id="now" class="section-pad">
  <div class="narrow">
    <h2 class="eyebrow">Now</h2>
    <?php foreach ($nows as $p): ?>
    <article style="margin-top:1.25rem">
      <h3 style="font-family:var(--font-sans);font-size:1.25rem;font-weight:600;letter-spacing:-.02em;margin-bottom:.75rem"><?= e($p['title']) ?></h3>
      <div class="prose" style="color:var(--muted);line-height:1.7"><?= $p['body_html'] ?></div>
    </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- The long stretch -->
<?php if ($stories): ?>
<section id="story" class="section-pad">
  <div class="narrow">
    <h2 class="eyebrow">The long stretch</h2>
    <div class="journal" style="margin-top:1.25rem">
      <?php foreach ($stories as $p): ?>
      <article class="journal-entry" style="margin-bottom:2.5rem;padding-bottom:2.5rem;border-bottom:1px solid var(--line)">
        <h3 style="font-family:var(--font-sans);font-size:1.25rem;font-weight:600;letter-spacing:-.02em;margin-bottom:.75rem"><?= e($p['title']) ?></h3>
        <div class="prose" style="color:var(--muted);line-height:1.7"><?= $p['body_html'] ?></div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- What this place is -->
<?php if ($about): ?>
<section id="about" class="section-pad">
  <div class="narrow">
    <h2 class="eyebrow"><?= e($about['title']) ?></h2>
    <div class="prose" style="margin-top:1rem;color:var(--muted);line-height:1.7"><?= $about['body_html'] ?></div>
  </div>
</section>
<?php endif; ?>
