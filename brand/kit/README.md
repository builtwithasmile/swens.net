# Swens brand kit

The kit lives at `brand/kit/` in every repository.

Reused public/assets/swens-mark.svg at f8320cf3854877c08286ba0ce6935fc504bd6274.

- `favicon.svg` is the browser symbol; `favicon.ico` includes 16, 32, 48, 64, 128 and 256 px.
- `favicon-16.png`, `favicon-32.png` and `favicon-48.png` are browser fallbacks.
- `apple-touch-icon.png` is 180 px. App icons are 192, 512 and 1024 px.
- `icon-512-maskable.png` has an opaque background and safe padding for phone masks.
- `logo.svg` and `logo.png` pair the symbol with the name. `logo-light` is for dark backgrounds. SVG lettering is outlined, so no font download is needed.
- `palette.css`, `site.webmanifest` and `head.html` are ready to reuse. Set the paths in `head.html` for the page using it.
- `brand-kit.zip` contains this kit for download or sharing.

Keep existing customer logos and per-install brand settings. For an app with a separate public folder, use the served copies listed in `kit.json`. For a native app, use `favicon.ico` as its Windows icon.

The web manifest supplies icon assets only. Keep the application's existing manifest for install identity, scope and start URL.

## App use

Existing icon links stay in place; the root ICO covers pages without an icon link.

Served copies: `public/favicon.ico`. Regenerate these copies from this kit after changing its artwork.

## App use

Existing icon links stay in place; the root ICO covers pages without an icon link.

Served copies: `public/favicon.ico`. Regenerate these copies from this kit after changing its artwork.

The existing site layouts and manifest request `/assets/favicon.svg` and `/assets/favicon-32.png`. Those served copies now exist and use the existing Swens mark.
