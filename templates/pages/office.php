<?php
/**
 * The Office — /office
 * Company cards + The Shelf + 25-year timeline.
 * No financials, no stakes. Honest status is a feature.
 * Hard law 3: family only as brands/characters.
 * Law 7: the maple.cr slogan is banned (see docs/voice.md).
 * FACTS LOCKED 2026-06-12 from swens-memory (Operating Context 2026): KillerBud is NOT
 * related to Waka's Dog House; engine framing (funds/life/build) is Josh's own.
 */
?>
<!-- Section: Intro -->
<section class="section-pad narrow" style="--accent:var(--gold)">
  <p class="eyebrow">The Office</p>
  <h1 class="h2-display"><!-- TODO-lyra -->What funds the place.</h1>
  <p class="lead">Four companies. Three running, one closed. Between them they pay for everything else here.</p>
</section>

<!-- Section: Company cards -->
<section class="section-pad">
  <div class="narrow">
    <h2 class="eyebrow">Companies</h2>
  </div>
  <div class="cards">

    <article class="card">
      <header>
        <h3>Crossroads Cannabis</h3>
        <span class="status-pill active"><!-- TODO-lyra -->Active</span>
      </header>
      <p><!-- no stakes/financials -->Cannabis retail in BC, co-founded with family and Indigenous partners. The first store opened in Burns Lake in 2021. A second one with a drive-thru window is going in at Penticton. This is what keeps the family side running.</p>
      <a href="https://crossroadscannabis.ca" rel="noopener">crossroadscannabis.ca</a>
    </article>

    <article class="card">
      <header>
        <h3>Maple Connection</h3>
        <span class="status-pill active"><!-- TODO-lyra -->Active</span>
      </header>
      <p><!-- its slogan stays on ITS site — law 7 -->Managed IT for Guanacaste. I run it from here, on a CRM I built myself. It pays for life in Costa Rica.</p>
      <a href="https://maple.cr" rel="noopener">maple.cr</a>
    </article>

    <article class="card">
      <header>
        <h3>Selvatec</h3>
        <span class="status-pill active"><!-- TODO-lyra -->Active</span>
      </header>
      <p>Where the building happens. IT and AI development, successor to HotSync. Every tool on this site and most of what the other companies run on came out of Selvatec.</p>
      <a href="https://selvatec.ca" rel="noopener">selvatec.ca</a>
    </article>

    <article class="card">
      <header>
        <h3>HotSync</h3>
        <span class="status-pill parked"><!-- TODO-lyra -->Closed — became Selvatec</span>
      </header>
      <p>My IT company in BC, 2000 to 2022. Closed now. Everything since stands on it.</p>
    </article>

  </div>
</section>

<!-- Section: The Shelf -->
<section class="section-pad">
  <div class="narrow">
    <h2 class="eyebrow">The Shelf</h2>
    <p class="lead"><!-- VOICE: plain (AI-tells ban) -->A shelf, not a store. Most of this got built because one of the companies needed it. Then somebody else asked for it.</p>
  </div>
  <div class="shelf">

    <div class="shelf-item">
      <span class="shelf-name">SafeCheck</span>
      <span class="shelf-desc">Lone-worker check-in for cannabis retail. Built for Crossroads, used every shift.</span>
      <span class="status-pill active"><!-- TODO-lyra -->Live</span>
    </div>

    <div class="shelf-item">
      <span class="shelf-name">Maple-Lead</span>
      <span class="shelf-desc">Lead capture and follow-up. Built for Maple Connection first.</span>
      <span class="status-pill coming-soon"><!-- TODO-lyra -->In development</span>
    </div>

    <div class="shelf-item">
      <span class="shelf-name">Blaze intel reports</span>
      <span class="shelf-desc">Competitor price tracking for cannabis retail. Built for Crossroads. Ask if you want it.</span>
      <span class="status-pill coming-soon"><!-- TODO-lyra -->Contact</span>
    </div>

    <div class="shelf-item">
      <span class="shelf-name">Intake-AI widget</span>
      <span class="shelf-desc">AI-assisted intake forms for new clients.</span>
      <span class="status-pill coming-soon"><!-- TODO-lyra -->In progress</span>
    </div>

    <div class="shelf-item">
      <span class="shelf-name">Micro Processing by Swens</span>
      <span class="shelf-desc"><!-- TODO-lyra -->Control and compliance software for micro processors.</span>
      <span class="status-pill coming-soon"><!-- TODO-lyra -->Early</span>
    </div>

  </div>
</section>

<!-- Section: 25-year timeline -->
<section class="section-pad">
  <div class="narrow">
    <h2 class="eyebrow">Twenty-five years</h2>
    <div class="timeline">

      <div class="timeline-item">
        <span class="timeline-range">The film years</span>
        <span class="timeline-name">Vancouver playback</span>
        <span class="timeline-detail">On-set graphics and playback. The X-Files, Stargate SG-1, The Outer Limits, Double Jeopardy, The 6th Day. My job was making the computers on screen look like they were doing something.</span>
      </div>

      <div class="timeline-item">
        <span class="timeline-range">2000 – 2022</span>
        <span class="timeline-name">HotSync</span>
        <span class="timeline-detail">Twenty-two years of IT in small-town BC. Whatever broke, I fixed it.</span>
      </div>

      <div class="timeline-item">
        <span class="timeline-range">2021</span>
        <span class="timeline-name">Crossroads Cannabis</span>
        <span class="timeline-detail">The first store opens in Burns Lake, co-founded with family and Indigenous partners.</span>
      </div>

      <div class="timeline-item">
        <span class="timeline-range">2022</span>
        <span class="timeline-name">Guanacaste</span>
        <span class="timeline-detail"><!-- law 2: no property details -->We moved to Costa Rica. The cafe came first.</span>
      </div>

      <div class="timeline-item">
        <span class="timeline-range">2022 –</span>
        <span class="timeline-name">Selvatec + Maple Connection</span>
        <span class="timeline-detail">HotSync closed and Selvatec took its place. Maple Connection started serving Guanacaste. I build from here now.</span>
      </div>

    </div>
  </div>
</section>

<!-- Section: Office posts strip (latest public posts tagged office) -->
<?php if (!empty($officePosts ?? [])): ?>
<section class="section-pad">
  <div class="narrow">
    <h2 class="eyebrow">From the journal</h2>
    <div style="margin-top:1rem">
      <?php foreach ($officePosts as $p): ?>
      <div style="padding:.875rem 0;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:baseline;gap:1rem">
        <a href="/office/<?= e($p['slug']) ?>" style="color:var(--text);text-decoration:underline;text-underline-offset:3px;font-size:.9375rem"><?= e($p['title']) ?></a>
        <span style="font-size:.75rem;color:var(--muted);white-space:nowrap;font-family:Georgia,'Times New Roman',serif;letter-spacing:.06em"><?= e(substr($p['created_at'], 0, 10)) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <p style="margin-top:1.25rem;font-size:.875rem"><a href="/office" style="color:var(--accent)">All office entries &rarr;</a></p>
  </div>
</section>
<?php endif; ?>

<!-- Section: Quiet close -->
<section class="section-pad narrow">
  <p class="lead">That&rsquo;s the business side. The personal stuff is behind the key. <a href="/gate">Ask for one</a>, or <a href="/">head back</a>.</p>
</section>
