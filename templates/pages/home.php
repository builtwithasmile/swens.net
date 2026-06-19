<?php
/**
 * Home / — "the ride" (v1 skeleton).
 * One illustrated world you move through by scrolling: cold film-era past at the top,
 * warming down into the Guanacaste jungle (now), ending on what's next.
 * Lynn-craft + Bruno "move to discover", pure CSS/SVG + tiny progressive-enhancement JS.
 *
 * VOICE: every visible line is a NATURAL-VOICE DRAFT aimed at Josh's meaning, for Josh to
 * reshape. NOT his literal words, NOT final. No invented facts/dates/numbers/money; no
 * client names, no memorial. Renders fully with no DB, no JS, and under reduced-motion.
 * Two stations are real (film start, jungle now); the middle two are honest sketches.
 */
?>
<div class="ride">

  <!-- the thread: film strip up top, a trail by the bottom (placeholder — refine into real art) -->
  <div class="ride-thread" aria-hidden="true"></div>

  <section class="station station--intro">
    <div class="station-text">
      <p class="eyebrow">swens.net</p>
      <h1>I've had<br>a few lives.</h1>
      <p class="lead">I'm Josh. Online I go by Swens. Come down through them. It's a bit of a ride.</p>
      <p class="ride-cue" aria-hidden="true">scroll &darr;</p>
    </div>
  </section>

  <section class="station station--film">
    <div class="station-text">
      <p class="eyebrow">where it started</p>
      <h2>I made fake computers.</h2>
      <p>In the 90s I built the screens you saw in movies and on TV. The ones that beep and scroll and look like they are thinking. The X-Files. Stargate. None of it was real. People believed it anyway.</p>
    </div>
    <div class="station-art"><img src="/assets/ride/film.svg" alt="" width="640" height="460" loading="lazy"></div>
  </section>

  <section class="station station--sketch station--artleft">
    <div class="station-text">
      <p class="eyebrow">the long stretch</p>
      <h2>Then I ran the town's computer shop.</h2>
      <p class="muted">Two decades of it. Still drawing this part in.</p>
    </div>
    <div class="station-art"><img src="/assets/ride/shop.svg" alt="" width="640" height="460" loading="lazy"></div>
  </section>

  <section class="station station--sketch">
    <div class="station-text">
      <p class="eyebrow">the big one</p>
      <h2>Then I built something serious.</h2>
      <p class="muted">Still drawing this part in.</p>
    </div>
    <div class="station-art"><img src="/assets/ride/build.svg" alt="" width="640" height="460" loading="lazy"></div>
  </section>

  <section class="station station--now station--artleft">
    <div class="station-text">
      <p class="eyebrow">where i am</p>
      <h2>Now I'm in the jungle.</h2>
      <p>Costa Rica. Fifty-three, a few things started over, still building. The bike is how I turn the screen off.</p>
    </div>
    <div class="station-art"><img src="/assets/ride/jungle.svg" alt="" width="640" height="460" loading="lazy"></div>
  </section>

  <section class="station station--next">
    <div class="station-text">
      <p class="eyebrow">what's next</p>
      <h2>Honestly? Still figuring that out.</h2>
      <p class="lead">That's most of why this place exists.</p>

      <p class="ride-reach">Reach me at <a href="mailto:info@selvatec.ca">info@selvatec.ca</a>.</p>
      <p class="ride-elsewhere muted">Also here: <a href="/office">the work</a> &middot; <a href="/gate">a way in</a></p>
    </div>
    <div class="station-art"><img src="/assets/ride/horizon.svg" alt="" width="640" height="460" loading="lazy"></div>
  </section>

</div>

<script>
/* Progressive enhancement only. No JS, or reduced-motion = every station already visible. */
(function () {
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (!('IntersectionObserver' in window)) return;
  document.documentElement.classList.add('ride-js');
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
    });
  }, { threshold: 0.2 });
  document.querySelectorAll('.station').forEach(function (s) { io.observe(s); });
})();
</script>
