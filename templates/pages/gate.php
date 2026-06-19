<?php
/**
 * The Gate — /gate
 * Two jobs: say who Swens is; take key requests without issuing anything.
 * States: $sent (bool), $errors (array), $old (array with preserved field values).
 */
?>
<!-- Section: Who's Swens -->
<section class="section-pad narrow" style="--accent:var(--gold)">
  <p class="eyebrow">The Gate</p>
  <h1 class="h2-display">Who&rsquo;s Swens?</h1>
  <p><!-- VOICE: plain bio, no cadence (voice.md AI-tells ban). Facts: film credits, HotSync 2000-2022, Guanacaste, KillerBud canon (Mem fact pack 2026-06-12). -->
    Short version: I made the computers on TV look like they were doing something. The X-Files, Stargate SG-1, shows like that. When a console beeped like it meant it, that was usually my desk. Somebody also had to keep the real machines alive between takes, and that part quietly became the career.
  </p>
  <p>
    In 2000 I went home to small-town BC and started HotSync, the town&rsquo;s computer company. Twenty-two years of that. In 2022 we packed the family up and moved to Guanacaste, Costa Rica.
  </p>
  <p>
    Somewhere in there I started drawing myself as a cartoon with a bud for a head. One eye locked on, one eye somewhere else. People who know me say the eyes are accurate. The cartoon got friends, the friends got jobs, and these days a small crew of them helps me build everything you see here.
  </p>
  <p>
    This site is me getting my stuff back. There are versions of me scattered across old business sites, dead Facebook pages, and folders of photos nobody opens. swens.net is where the pieces come back under one roof: one quiet spot that&rsquo;s actually mine. Not a profile, not a brand. A place.
  </p>
  <p>
    The outside is just the vibe. If you know me, there&rsquo;s a door, and the real version is on the other side of it: where I actually am, what I&rsquo;m building, the long version of the story. If you don&rsquo;t, that&rsquo;s alright too. It won&rsquo;t sell you anything.
  </p>
  <p>
    The business side is in <a href="/office">The Office</a>. Everything else needs a key.
  </p>
</section>

<!-- Section: Request a key -->
<section class="section-pad gate-panel" style="--accent:var(--gold)">
  <div class="narrow">
  <?php if ($sent): ?>
    <!-- Flash: confirmation (shown after submit, rate-limit, honeypot, bad CSRF — all indistinguishable) -->
    <div class="flash" role="status" aria-live="polite">
      <!-- TODO-lyra; vein: "Got it. If a key gets cut, you'll hear from me." -->
      <p>Got it. If a key gets cut, you&rsquo;ll hear from me.</p>
    </div>
  <?php else: ?>
    <div class="gate-form-wrap">
      <!-- Left column: what keys are -->
      <div class="gate-copy">
        <h2><!-- TODO-lyra -->Request a key</h2>
        <p><!-- honest status — keys exist, none issued yet; already in-voice -->
          Keys exist. None are being cut yet. Asking now just means I know you&rsquo;d like one. No timeline, no promise.
        </p>
      </div>

      <!-- Right column: the form -->
      <form class="gate-form" method="post" action="/gate" novalidate>
        <?= csrf_field() ?>

        <!-- Honeypot field — invisible to humans, triggers bot detection -->
        <div class="honeypot" aria-hidden="true">
          <label for="gate-website">Website</label>
          <input type="text" id="gate-website" name="website" tabindex="-1" autocomplete="off" value="">
        </div>

        <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
          <label for="gate-name">Name</label>
          <input type="text" id="gate-name" name="name" required maxlength="80"
                 value="<?= e($old['name'] ?? '') ?>">
          <?php if (isset($errors['name'])): ?>
            <p class="field-error"><?= e($errors['name']) ?></p>
          <?php endif; ?>
        </div>

        <div class="field<?= isset($errors['contact']) ? ' has-error' : '' ?>">
          <label for="gate-contact"><!-- TODO-lyra; vein: "Email, or how I know you" -->Email, or how I know you</label>
          <input type="text" id="gate-contact" name="contact" required maxlength="160"
                 value="<?= e($old['contact'] ?? '') ?>">
          <?php if (isset($errors['contact'])): ?>
            <p class="field-error"><?= e($errors['contact']) ?></p>
          <?php endif; ?>
        </div>

        <div class="field<?= isset($errors['message']) ? ' has-error' : '' ?>">
          <label for="gate-message">Message</label>
          <textarea id="gate-message" name="message" rows="4" required maxlength="2000"><?= e($old['message'] ?? '') ?></textarea>
          <?php if (isset($errors['message'])): ?>
            <p class="field-error"><?= e($errors['message']) ?></p>
          <?php endif; ?>
        </div>

        <button type="submit" class="button primary">Request a key</button>

        <p class="form-note">
          <!-- TODO-lyra; honest no-keys-yet + where this goes (straight to Josh, nowhere else; nothing stored on the server) -->
          No keys are being cut yet. This goes straight to my inbox. Nothing gets stored on the server.
        </p>
      </form>
    </div>
  <?php endif; ?>
  </div>
</section>
