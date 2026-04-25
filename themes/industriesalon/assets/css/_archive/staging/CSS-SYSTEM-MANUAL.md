# Industriesalon CSS system notes

This set keeps the existing selectors and visual logic but documents the system more clearly.

## File responsibility

- `style-organized.css`
  Global design tokens, layout primitives, alignment helpers, kicker and heading systems.

- `cards-organized.css`
  Reusable card shells, card media helpers, and card-heavy reusable pattern internals.

- `patterns-organized.css`
  Template-specific composition, hero shells, footer/header layout, page patterns, and plugin theme bridges.

## Recommended editing order

1. Change `theme.json` if the WordPress editor palette, font sizes, spacing scale, or global block defaults must change.
2. Change `style-organized.css` if the adjustment should affect the whole site.
3. Change `cards-organized.css` if the issue is about reusable card surfaces.
4. Change `patterns-organized.css` if the issue belongs to one page family or template structure.

## Safe maintenance rules

- Do not add page-specific hero fixes into `style.css`.
- Do not duplicate card styling into template blocks unless a local override is truly necessary.
- Prefer custom properties already exposed in a group before adding more selector depth.
- Keep BEM family names stable. Patterns and plugin output already rely on them.
- If markup must change, keep class family names intact and change only the wrapper structure around them.

## Notes

These files are documentation- and organization-focused. The selector names and general CSS behavior are intentionally preserved so the next admin can work from a clearer system without needing to relearn the whole theme.
