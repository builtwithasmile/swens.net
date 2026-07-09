# Current State — live status only. Facts, not canon. Never "decided / locked".

<!-- BOARD COUNTING CONTRACT (scripts/status.php + the session-start hook): the fleet
     board counts the top-level "- " bullets in the three sections below — In progress /
     Open loops / Broken. Only LIVE items belong in them; keeper facts, history, and
     done-narratives go in the uncounted Facts section or they render as phantom board
     items. Date-stamp any count you write here ("471 tests green (2026-07-05)") — an
     as-of-then count reads as current to a cold reader. -->

## In progress
<!-- ONLY what's actively being worked on right now — done-narratives move to Facts -->

## Open loops (carry-forward — surfaced at session start, never blocks a close)
<!-- One line per loose end: the thing + its single next action. -->
- **Live deploy — its own repo session (Josh 2026-07-07, deferred):** the main docroot `/home/swensnet/public_html` is SHARED with live apps `capp/` (capp.swens.net — BC cannabis compliance) and `me/` (me.swens.net), so the deploy MUST rsync-exclude them or it clobbers them. `.cpanel.yml` is a deliberate disabled placeholder; going live needs the real exclude-preserving tasks + a root-rewrite written first (the app wants `/public` as docroot, which a main domain can't point at — docs/architecture.md §10, not yet written), then verify capp.swens.net + me.swens.net still serve before AND after. Do NOT blind-deploy.
- DIRECTION.md still Josh's to write (Porch pivot).
- **/build restyle to DNA (next visual session):** Design DNA landed 2026-07-09 — Josh picked "copy https://re-birth.framer.website/" (after rejecting three invented directions; his rule is now copy-what-I-link). `design/DNA.md` + token/type layer in `site.css` + Google Fonts in `layouts/site.php` + `layouts/bare.php` are live (monochrome, Plus Jakarta Sans 800 display, Fragment Mono chrome; a shared `--on-accent` token keeps button text readable across both scopes). Still owed section-by-section: viewport-wide SWENS footer wordmark, scroll-lit statement paragraph (the signature — DNA.md §7/§8 already fully specify the motion language: fade-up+settle, count-up numbers, typed-on mono eyebrow, dot cursor-follower; none of it is implemented in `home.php`/`site.css` yet, it's not "stripped," just not built), photography to replace the grayscaled `/assets/ride/*.svg` and `/assets/swens-mark.svg`/`topography.svg` (still hardcode the old gold/green/clay palette as fill/stroke — SVGs, not CSS, so the retoken pass didn't reach them), admin dashboard sweep (`templates/layouts/admin.php` + `pages/admin/*.php` were out of scope this session, still on the pre-DNA slate palette), and §15's dead-section note (§9 CAST/§13 STORY/§14 TEAM slated for removal — verify then delete). Confirmed 2026-07-09: `/office` and `/gate` are both correctly `interior`/light-scoped (site.php:22) so the color system already matches between them, but they read as different sites because of component density — office.php is card/grid/timeline-heavy, gate.php is plain narrow paragraphs + a form, no cards at all — the per-page sweep needs to reconcile that, not just re-check tokens.
- **Copy/voice pass on `home.php`'s "ride" stations (session 2026-07-09, no code touched — Josh reviewed only, said hold):** Josh flagged nearly every station as vague/AI-sounding "poster copy" — this is the SAME issue as `copy-pass-blind-spot` (21 days old, never actually fixed), now confirmed site-wide rather than hero-only. Real facts already exist on `office.php`'s 25-year timeline and `gate.php`'s bio and were never pulled into `home.php`'s draft stations. Drafts ready to apply next session (get Josh's go-ahead first):
  - Hero ("I've had a few lives...") — flagged as the original example; no rewrite drafted yet, needs the same specific-not-vague treatment as the others below.
  - "I made fake computers." → "Vancouver, on-set playback. The X-Files, Stargate SG-1, The Outer Limits — when a console beeped like it meant something, that was usually my desk. None of it did anything. Somebody still had to keep the real machines running between takes, and that's the part that became the career."
  - "Then I ran the town's computer shop." → "HotSync, 2000 to 2022. Twenty-two years of small-town BC — whatever broke, I fixed it."
  - "Then I built something serious." → **needs Josh's call**: is "the big one" Crossroads Cannabis (2021, Burns Lake, family + Indigenous partner) or Selvatec/the software side? Draft assumes Crossroads Cannabis: "Crossroads Cannabis — the first legal store in Burns Lake, built with family and an Indigenous partner. 2021. Real license, real inspectors, real risk."
  - "Now I'm in the jungle." → "Costa Rica, since 2022. The cafe came first, then Selvatec and Maple Connection. Fifty-three, still building most days. I get on the bike when I've had enough screen."
  Next action: Josh's go-ahead + the "big one" call, then apply all five and run a real copy pass using voice.md's two tests (specific over abstract; would this be quoted on a poster, cut it) — not just `copy-scan.php`, which structurally can't catch this class (see the `copy-pass-blind-spot` memory).
- **Bug: wrong contact email — `home.php:70`** links `mailto:info@selvatec.ca` (a different business, covered on /office); should be `info@swens.net`. One-line fix, not yet applied (Josh said hold, still building the list).
- Standard-kit gaps: A1 password reset, A2 change password, A4 user mgmt, A5 remember-me, B2 audit trail, B3 idle logout, B4 admin settings UI, B5 nightly backup, B6 notifications — see `factory/knowledge/standard-kit.md`. (A3 partial, B1 shipped.)

## Broken / watch out
<!-- Known-broken or fragile things a future session should not trip over. -->

## Facts (uncounted — keeper context, shipped-block history, watch notes; never work items)
- Validator missing maxLen()/pattern() (Selvatec security defaults gap) — RESOLVED 2026-07-08: added to `core/Validator.php`, matching the factory template. No callers yet — controllers still do inline checks; wiring them to use Validator is a future pass.
- Raw mail() calls (Selvatec security defaults gap) — RESOLVED 2026-07-08: CRLF-hardened `send_mail()` wrapper in `core/helpers.php`; `AuthController.php`/`GateController.php` use it instead of raw `@mail()`.
- Git remote — RESOLVED 2026-07-07: added (github.com/builtwithasmile/swens.net), `main` pushed.
- Stale "Compound" reference in config.example.php — RESOLVED 2026-07-07: header comment updated to Porch.
- Admin post-form kind options mismatched keyedSections() — RESOLVED 2026-07-08: dropdown + default aligned to welcome/about/board/now/story.
- `PostsController::validate()` missing server-side enum + maxLen checks on `kind`/`tags` (Selvatec security defaults gap) — RESOLVED 2026-07-08.
- Two invisible-text regressions from the DNA retheme (`.button.primary`, inside.php's inline submit button) — RESOLVED 2026-07-09, caught by /wrap reflection probes (pip + unstated-assumptions lenses, independently, same bug class). `bare.php` missing the Google Fonts `<link>` (404/500 pages silently falling back to system fonts) — RESOLVED same pass.
- 2026-07-09: memory/state.md was missing the standard "In progress" / "Broken / watch out" sections since scaffold (2026-07-03) — scripts/status.php requires all three tracked headers or it flags the repo UNPARSEABLE. Fixed to match the canonical 4-section contract (see swens-seo-control/memory/state.md for the reference shape).
