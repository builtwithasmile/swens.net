<?php
/**
 * Public business footer — the thin brand link-outs (Phase-0 #3).
 * Content is Josh's own words, verbatim, from docs/content-seeds/06_public_business-links.md
 * (provenance law). Already-public brands only; no real-world detail (rail 2).
 * Shown on public pages; the layout omits it inside the keyed room.
 * Self-contained styling on :root tokens — no dependency on the big CSS file.
 * DNA §8 signature device: the viewport-wide ExtraBold wordmark sits just above the
 * link list, self-styled inline to match this partial's own no-big-CSS-dependency rule.
 */
?>
<div style="border-top:1px solid var(--line);margin-top:4rem">
  <a href="/" aria-label="swens.net — home" style="display:block;text-align:center;padding:2rem clamp(1rem,4vw,2rem) 0;font-family:var(--font-sans);font-weight:800;letter-spacing:-.05em;line-height:.85;font-size:clamp(3rem,16vw,14rem);color:var(--text);text-decoration:none">SWENS</a>
<footer class="brand-footer" style="padding:1.5rem 1.5rem 2.5rem">
  <div style="max-width:62rem;margin:0 auto">
    <p class="eyebrow" style="margin:0 0 .35rem">Things I'm mixed up in</p>
    <p style="margin:0 0 1.5rem;color:var(--muted);font-size:.9375rem;max-width:48ch">Light touch. If you want the real story behind any of these, that's on the other side of the door.</p>
    <ul style="list-style:none;padding:0;margin:0;display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
      <li style="font-size:.9375rem;line-height:1.55;color:var(--muted)">
        <a href="https://crossroadscannabis.ca" rel="noopener" style="color:var(--text)"><strong>Crossroads</strong></a>
        — a family-run venture in a regulated corner of retail, up in Canada.
      </li>
      <li style="font-size:.9375rem;line-height:1.55;color:var(--muted)">
        <a href="https://selvatec.ca" rel="noopener" style="color:var(--text)"><strong>Selvatec</strong></a>
        — the IT and AI work. Quietly keeps other people's systems boringly reliable.
      </li>
      <li style="font-size:.9375rem;line-height:1.55;color:var(--muted)">
        <a href="https://maple.cr" rel="noopener" style="color:var(--text)"><strong>Maple Connection</strong></a>
        — the same idea, moved south. Managed IT for people running places near the coast.
      </li>
      <li style="font-size:.9375rem;line-height:1.55;color:var(--muted)">
        <a href="https://killerbud.ca" rel="noopener" style="color:var(--text)"><strong>KillerBud</strong></a>
        — the side that doesn't take itself seriously. A small cast of characters.
      </li>
      <li style="font-size:.9375rem;line-height:1.55;color:var(--muted)">
        <!-- Blurb is Josh's own words, 2026-07-10 (provenance law, see header comment). -->
        <a href="https://selvatec.ca/own-your-software" rel="noopener" style="color:var(--text)"><strong>Selvatec Software</strong></a>
        — custom software that replaces monthly fees, buy once and done, we keep it running.
      </li>
    </ul>
  </div>
</footer>
</div>
