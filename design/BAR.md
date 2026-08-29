# design/BAR.md — swens.net (the Porch)

> **DRAFT — authored 2026-08-28 from live recon; reference screenshots still to capture (/wow step 1); Josh to ratify.**

**Goal line:** World-class here means that within *one unscrolled screen* and roughly *five seconds*, a stranger knows this is Josh Swenson, feels a specific confident taste (not a template), and sees that the things he built are real and named — while finding **nothing to look him up by** and **nothing to buy**. The measuring stick is the single public screen, not a scroll.

This BAR sits on top of design/DNA.md (the visual floor: cinematic monochrome, Plus Jakarta 800 display doing all the branding, Fragment Mono chrome, no decorative hue). DNA says *how it looks*; BAR says *how good the best real sites are at the one job* and what numbers this screen must beat.

---

## 1. Reference class (recon'd LIVE 2026-08-28)

**re-birth.framer.website — the DNA source, and the ceiling for "typography IS the brand."**
Observed live: the hero is a massive display wordmark (heavyweight sans, ~80–120px, monochrome black-on-white — note the live template now serves a typographic "MARK / 2K26" hero rather than the photographic-black version the DNA transcribed in July), one tiny mono micro-label (`./ portfolio`), one medium subhead about a third the size, and a single live GMT clock. **The world-class move:** no hero image at all — scale + a lone live element (the clock) carry both authority and "a real person is here right now." *Why it wins the key task:* identity lands in one glance because exactly one thing is huge and everything else is deliberately small; the clock quietly proves the site is alive without a feed. **This is the exact shape swens.net copies — the Porch's viewport-wide `SWENS` wordmark + live Costa-Rica clock are the same instrument.**

**stephango.com (Steph Ango, CEO of Obsidian) — the ceiling for restraint / single confident screen.**
Observed live: name as a plain link, a three-item nav (`About · Now · Latest`), and one featured piece. **Only three elements compete for attention.** No oversized hero, no color, no pitch. *Why it wins:* it proves a credible founder needs almost nothing on screen — identity comes from *who obviously made this*, not from declaration. This is the discipline the Porch's "door mostly closed" demands, and the strongest argument that a near-empty screen reads as confidence, not as unfinished.

**rauno.me (Rauno Freiberg, Vercel) — the ceiling for "who I am in one sentence."**
Observed live: name, then a single identity line — *"Estonian interaction designer working with Vercel and Devouring Details"* — discipline + affiliation in one breath, monochrome, text-first, generous whitespace. **The move:** one sentence does the entire "who is this and are they real" job; credibility rides on naming real things (Vercel, Devouring Details) rather than adjectives. *Why it wins the key task:* a stranger grasps the person before they've decided whether to keep reading. For the Porch, the named things are the ventures (Crossroads, Selvatec, Maple, KillerBud) — but the *shape* (one line, real names, no adjectives) is Rauno's.

**levels.io (Pieter Levels) — the credibility-proof reference AND the clutter counter-example.**
Observed live: a "Pinned" strip of **five named shipped projects** (Nomads.com, Remote OK…) with thumbnails and live links — the clearest "what I built is real" proof in the class, because each item is a *live, clickable, named thing*. *Why the proof works:* real beats claimed — a link you can open outranks any "10 years experience" badge. **But it is also the counter-example the Porch must beat:** ~8 elements fight above the fold (nav, sort/filter bar, intro, subscriber CTA, pinned strip, blog feed, avatar, Telegram CTA), it leans on a subscriber count and a blog feed, and identity is buried under a "you've found my blog" framing. It proves the *proof mechanism* (named live things) while proving the *anti-pattern* (feed + counters + eight competing calls) the Porch's DIRECTION explicitly forbids.

**Out-of-industry elegance benchmark — linear.app.**
Observed live: one primary headline, one visually dominant CTA, monochrome black-on-white, generous vertical rhythm; every secondary link is de-emphasized so a single action owns the screen. *Why it's the benchmark:* it is the reference for **"one thing is clearly the most important element and nothing competes with it."** Translated to the Porch, the single dominant element is the wordmark/identity, and the single quiet affordance is *the door* (keyed entry) — everything else recedes. (Recon note: an automated fetch reported a "headline repeated three times" — that is a responsive duplicate-DOM artifact, not a real design move; not cited.)

---

## 2. The numbers (extracted from the references above — never invented)

The key task = *grasp who Josh is + that his work is real, in one screen.* Budgets are what the best real sites already achieve, so they are floors to beat, not aspirations.

| Budget | Target | Named reference (achieves it) | Counter-example (proves it's a choice) |
|---|---|---|---|
| **Scrolls to grasp identity + realness** | **0** (single unscrolled screen) | re-birth, stephango — identity lands in the first viewport | levels.io needs a scroll to see the full pinned-project proof |
| **Distinct elements competing above the fold** | **≤ 4** | stephango = 3 (name, 3-nav, one feature) | levels.io ≈ 8 (nav, filter bar, intro, sub-CTA, pins, feed, avatar, Telegram) |
| **Words to say who he is** | **≤ 1 sentence / ~12 words** | rauno.me = one line (discipline + real affiliations) | levels.io buries identity under a "found my blog" + filter framing |
| **Named real things visible as proof** | **≥ 3, shown on the one screen, 0 extra clicks** | levels.io pins 5 named live projects | a "skills/experience" claim with nothing clickable = fails the test |
| **Proof format** | **thin index links (DNA §4 hairline list), NOT a card/logo grid** | re-birth/stephango index restraint | levels.io thumbnail grid = too much merchandising for a Porch |
| **Public nav affordances** | **≤ 3, exactly one of which is "the door" (keyed entry)** | stephango = 3-item nav | rauno.me = 7 nav destinations (fine for a working portfolio, too open for a Porch) |
| **Display wordmark scale** | **viewport-width / ≥ 100px** (dominates by 3–4× the next element) | re-birth hero ~80–120px; DNA measured 160px | any screen where nav or subhead rivals the name = fails Linear's "one dominant element" |
| **Decorative hues** | **0** (monochrome; photography/1 signal color only) | re-birth, stephango, rauno, linear all monochrome | an AI-purple gradient hero = instant template tell |
| **Live "someone's here" signals** | **exactly 1** (the Costa-Rica clock) | re-birth's single GMT clock | levels.io's subscriber count + feed = liveness by noise, not by one quiet signal |
| **Things a stranger can look him up by** (inverse/privacy budget) | **0** — no real-name+age+location dossier, no résumé, no dollar figures, no named clients | DIRECTION.md rules | levels.io publishes revenue + full identity — the *opposite* of the Porch on purpose |
| **Time-to-grasp (glance)** | **≤ 5 s** | re-birth + rauno land it in one read | any screen requiring reading a paragraph to know who it is = over budget |
| **Clicks to reach "the full ride"** | **1** (the door → keyed layer) | — (Porch-specific; keeps the social layer one deliberate step away) | a public feed = 0 clicks but violates DIRECTION's "no feed" |

---

## 3. Clichés to avoid (reject on sight — these are the category's tells)

- **The "Hi, I'm Josh 👋" waving-emoji hero.** levels.io's "Hi. I'm @levelsio and you've found my blog!" is the friendly-portfolio default; the Porch is confident, not chatty — the wordmark introduces him, not a greeting.
- **A blog/social feed as the front door.** DIRECTION.md forbids a feed outright; levels.io is the warning — a feed makes the front page *about the latest post*, not about the person.
- **Résumé / CV timeline / "N years of experience" counters.** Achievement-shaped, never plot-shaped (DIRECTION). Proof is named live ventures, not a career ladder.
- **Dollar figures, MRR badges, subscriber counts.** levels.io's whole credibility engine — explicitly banned here. Proof by *realness of the thing*, never by *size of the number*.
- **Skills bars / tech-stack logo wall.** "Proof by tool logos" is the junior-portfolio tell; a stranger should believe the work is real because it's *named and clickable*, not because React's logo is on screen.
- **Stock-smiling headshot or AI-purple gradient hero.** DNA §9 bans bright stock; the whole class (re-birth, stephango, rauno, linear) is monochrome and image-restrained.
- **Hamburger menu on a ≤3-link screen.** Hiding three links behind an icon is friction theater; stephango shows the nav *is* the three words.
- **A long autobiographical scroll.** DIRECTION.md, verbatim: "the format pulls toward memoir; don't." A confident single screen beats a scroll.
- **A contact form / "let's work together" CTA above the fold.** The Porch is not for sale and not trying to grow (DIRECTION). One quiet mailto in the footer, never a lead-gen block.
- **Testimonial carousels or client-logo walls.** Named clients are banned on public pages; social proof by other people's logos is the opposite of a study with the door mostly closed.

---

*Every claim above traces to a site fetched live on 2026-08-28. Reference screenshots (re-birth hero, stephango first screen, rauno identity line, levels.io pinned strip as the counter, linear single-CTA hero) still need capturing into design/refs/ via /wow step 1 before the WOW gate can shuffle the build screenshot against them. Josh ratifies.*
