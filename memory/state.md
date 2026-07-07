# Current State — live status only. Facts, not canon. Never "decided / locked".

## Open loops (carry-forward — surfaced at session start, never blocks a close)
- ~~No git remote configured~~ — **RESOLVED 2026-07-07:** remote added (github.com/builtwithasmile/swens.net) and `main` pushed (`2412504`).
- **Live deploy — its own repo session (Josh 2026-07-07, deferred):** the main docroot `/home/swensnet/public_html` is SHARED with live apps `capp/` (capp.swens.net — BC cannabis compliance) and `me/` (me.swens.net), so the deploy MUST rsync-exclude them or it clobbers them. `.cpanel.yml` is a deliberate disabled placeholder; going live needs the real exclude-preserving tasks + a root-rewrite written first (the app wants `/public` as docroot, which a main domain can't point at — docs/architecture.md §10, not yet written), then verify capp.swens.net + me.swens.net still serve before AND after. Do NOT blind-deploy.
- DIRECTION.md still Josh's to write (Porch pivot).
