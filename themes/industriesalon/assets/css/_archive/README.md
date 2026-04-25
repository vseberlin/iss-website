# _archive — Development History Only

These files are NOT loaded in production. Nothing in this directory is enqueued or referenced by the live theme.

## Contents

- `staged/` — Per-pattern CSS experiments from earlier development iterations.
- `staging/` — Larger staging files and HTML test pages. Contains the original location of `industriesalon-fuehrungen-filters.php`, which has been moved to `themes/industriesalon/assets/php/` and is the live version.
- `unused.css` — Deprecated styles archived from an earlier version of the design system.
- `style.css-orig` — Backup of style.css before a major refactor.
- `front-page-orig.html` / `front-page-test.html` — Backup template snapshots.

## Production CSS load order

```
theme.json → style.css → assets/css/cards.css → assets/css/patterns.css → assets/css/overrides.css
```

To edit live styles, only touch files in `assets/css/` (not this `_archive/` subdirectory).
