# Design DNA — swens.net (the Porch)

**Chosen by Josh 2026-07-09: copy https://re-birth.framer.website/ ("Rebirth Portfolio",
a Framer template by framerpod).** Not "inspired by" — copied. This file transcribes that
site's visual language, measured live (screenshots + DOM computed styles + raw CSS tokens),
so every build pass can reproduce it without re-deriving. vector enforces this as law.

## 1. Direction

Cinematic monochrome portfolio. A full-bleed photographic hero rising out of black; a light
gallery-white body; typography does ALL the branding — massive ExtraBold sans, tight-tracked,
with tiny letter-spaced mono labels as chrome. No decorative color anywhere: photography
carries the color, the UI is grayscale. Confidence through restraint and scale.

## 2. Type pairing (Google Fonts — sanctioned; supersedes the old "no webfont" spec note)

- **Plus Jakarta Sans** — the one text/display face. Display = weight 800, letter-spacing
  −5% (−0.05em), line-height 1.0, set HUGE (hero/footer wordmark: full viewport width;
  measured 160px/−8px on the reference). Body = 400/600, normal tracking.
- **Fragment Mono** — the chrome face: nav items, eyebrows, section labels, list micro-labels,
  numbers ("03 —"), captions, legal. Always uppercase, letter-spaced ~0.1em, 11–13px.
- Inter appears in the reference only as Framer's stock-component fallback — do NOT use it.

## 3. Palette (measured from the reference's own design tokens)

| token | hex | job |
|---|---|---|
| stage black | `#000000` | dark scenes: hero, statement sections, CTA band |
| gallery white | `#FAFAFA` | light body sections (with `#FFFFFF` panels) |
| ink | `#1A1A1A` | text on light |
| gray voice | `#666666` | muted text, labels (steps `#999`/`#B3B3B3` allowed on dark) |
| hairline | `#E6E6E6` | dividers on light (`#333336` is its dark-scope twin) |

Text on dark = `#F5F5F5`. **The brand has no hue.** One functional signal color is reserved
outside the brand: `#FF4400` (present in the reference's token sheet) — errors, live/REC
dots, destructive states only. Never decoration, never headings, never buttons.

## 4. Spacing rhythm

Vast vertical air between sections (~150–200px); centered, letter-spaced mono section labels
(`S E L E C T E D  P R O J E C T S`) open each block. Content sits in generous measures;
index lists are structured by 1px hairlines, not boxes. Density lives only in micro-chrome
(nav, captions, footers).

## 5. Shape language

Square media and cards — border-radius 0. Buttons are the single exception: full pills
(999px), 1px-bordered or solid.1px hairlines everywhere structure is needed. No shadows
as decoration (only whisper-soft elevation if functionally required).

## 6. Texture / background

Flat. Photography is the only texture. (The reference allows one exception: a faint
wireframe-grid backdrop on the dark contact/CTA band.) No gradients, no grain, no paper.

## 7. Motion personality

Languid, cinematic, scroll-driven; fully readable with JS off / reduced-motion:
- **Scroll-lit statement** (the signature — see §8).
- Fade-up + settle on section entry; slow scale on images.
- Count-up numbers in stats.
- Typed-on mono eyebrow in the hero.
- Dot cursor-follower on dark scenes (desktop only, pure enhancement).

## 8. Signature element

**The scroll-lit statement:** a huge ExtraBold paragraph (Plus Jakarta 800, ~40–56px) on
stage black that starts all-`#666` and lights up word-by-word to white as you scroll through
it. Supporting devices: the viewport-wide ExtraBold name wordmark just above the footer
(`SWENS` / `JOSH SWENSON` as MARK ASHTON is on the reference), and the live "My current time"
clock (Costa Rica) in the footer contact row.

## 9. Photography / illustration stance

Full-bleed personal photography: black-and-white, or moody near-black color. Portraits and
place (the jungle, the bike, the shop) — treated dark, never bright-stock. Mono captions
overlay in Fragment Mono uppercase. Forbidden: stock-smiling-people, emoji-as-icons, bright
illustration on hero surfaces. The existing flat `/assets/ride/*.svg` illustrations do not
match this DNA — the restyle pass replaces them with photography or re-cuts them monochrome.

## 10. Implementation map (this repo)

- Plain-CSS project (no Tailwind): tokens live in `public/assets/css/site.css` `:root`
  (dark scope) and `body.interior` (light scope — the old "aged paper" scope is now the
  Rebirth light mode). Token NAMES kept for compatibility (`--gold` = the accent slot,
  now monochrome).
- Fonts load via Google Fonts `<link>` in `templates/layouts/site.php`.
- Page structure mapping: home = single dark hero screen, identity + one line + contact,
  no scroll narrative (round-table 2026-07-10 chose restraint over a multi-station "ride"
  after Josh saw the personal-history version live and pulled it back — see memory/state.md).
  The scroll-lit statement device (§7/§8) is unclaimed for now — re-evaluate for a page
  with earned specific content to unspool, e.g. a future `/office` pass, not force it onto
  home. `/office` destinations as SELECTED PROJECTS rows; interiors = light gallery pages;
  footer = giant wordmark + contact columns + clock.
- Next: `/build restyle to DNA` migrates section by section.

## References

- https://re-birth.framer.website/ — the reference itself (primary; Josh's pick)
- https://framerpod.com — the studio behind the template (for any ambiguity, check their live demo)
- Fonts: https://fonts.google.com/specimen/Plus+Jakarta+Sans · https://fonts.google.com/specimen/Fragment+Mono
