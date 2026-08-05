## Design gate (law on EVERY visual pass — not just /design-dna sessions)
design/DNA.md is law for every customer-facing surface, and design/BAR.md is its measuring
stick — the world-class bar (reference screenshots of the best real sites + numeric
click/choice budgets, created by /wow). Page-level workflow surfaces also carry a
design/BRIEF-<page>.md — the architecture layer: customer entry modes, cited domain/legal
truths, brand-as-architecture, interaction rules, numeric acceptance criteria, smallest
buildable slice (canon: D:\SwensBuildEnv\factory\knowledge\design-brief.md). Any change
touching templates/, public CSS, or JS
that emits markup runs this gate — a visual change that skips it is NOT done, same as a red
test. Canon: D:\SwensBuildEnv\factory\knowledge\design-dna.md (DNA spec, identity gate;
no restriction lists — the factory bans no visual element, Josh's ruling) +
D:\SwensBuildEnv\factory\knowledge\worldclass-bar.md (BAR format, WOW/EASE gates).
(No design/DNA.md yet and the repo has a visual surface? Stop and run /design-dna first.
No design/BAR.md? /wow step 1 creates it before major visual work. New page build or
IA/workflow change with no design/BRIEF-<page>.md? Write the brief first — critique included.)
1. **Before:** read design/DNA.md + design/BAR.md + the page's design/BRIEF-*.md. Layout/
   new-surface work also recons ≥3
   real sites in this industry LIVE (web, never model memory) — models converge on the same
   AI-template look unless forced onto real references (Josh's standing ruling).
2. **Subagents:** any subagent prompt that touches a visual file gets the DNA's identity
   block — type pairing, palette + each color's job, shape language, signature element, and
   the page-type's composition — AND the BAR's numeric budgets PASTED IN VERBATIM, plus the
   brief's interaction rules + numeric acceptance criteria when one exists. Reference
   images ATTACH as images (Read the image files in design/refs/ — ANY format, benchmarks
   labelled as benchmarks, keeper mockups as keepers; none yet → identity-gate fallback +
   one-line loop), and the implementer renders + screenshots its own output at least
   one self-correction cycle before handoff (eyes, not memory; method:
   D:\SwensBuildEnv\factory\knowledge\browser-verify.md). The paste
   is the positive spec, never a ban list. Subagents inherit no context — "see design/DNA.md"
   in a prompt reliably produces the generic AI look, and so does a paste of only floors and
   budgets with no positive spec.
3. **After:** run the gates FRESH-CONTEXT (the context that built a page always passes its
   own gate): identity (blinded comparison — "which of these had a human designer? default
   FAIL"), WOW
   (build screenshot shuffled among the BAR's reference shots: "which is the student project?
   default FAIL"), and EASE when the client journey changed (echo walks it LIVE and counts
   real clicks vs the BAR budgets — over budget = red test). New/changed IA also gets the
   fresh-context ARCHITECTURE critique (entry-state coverage, legal/domain exposure, dead
   ends, invented authority — D:\SwensBuildEnv\factory\knowledge\design-brief.md; attack the architecture, not the
   pixels; regenerate once). Then put ONE screenshot of the
   deployed result IN CHAT for Josh's keep/kill, with a cache-busted URL. Design Josh can't
   see does not exist. The moment Josh answers, RECORD it — same turn:
   `node D:/SwensBuildEnv/scripts/capture/verdict.mjs --repo . --shot <the evidence jpg>
   --verdict keep|kill|revise --words "<his exact words>"` (the shot must come from the
   factory eye/design-scan — the tool refuses unverifiable pixels). design/verdicts.md is
   the graded record canon changes answer to; a verdict left in chat is a verdict lost.
**Pixels come from `.claude/screenshot.js`** — the template-owned headless-Edge CDP driver, and
the source of truth for every screenshot this gate treats as evidence. It needs no node_modules
and no package.json: `node .claude/screenshot.js <url> <out.png> [w] [h] [--mobile]
[--scroll=y] [--eval=js] [--seed=key=value]` (repeat `--seed` to clear an age/splash/cookie gate
before first paint; `--nogate` skips all seeds to shoot the gate itself). The interactive Browser
pane is a convenience for reading the DOM, not a rasterizer to depend on — when its screenshot
action stalls, the gate still runs.
Non-visual repos (CLI, API-only, the factory itself) skip this gate entirely.
