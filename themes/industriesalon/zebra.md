# Zebra / Section Surface Contract

This project does **not** use mechanical zebra striping.  
It uses a small, neutral set of section surfaces that patterns apply intentionally.

## Principle
- Default page flow is plain.
- Surface classes are applied only on `.section` wrappers.
- Patterns own when/where to apply a surface.
- Red stays an accent (kickers, links, controls), not a background wash.

## Surface Variants
- `.section--plain`: transparent default.
- `.section--tint`: light neutral gradient for soft grouping.
- `.section--soft`: subtle flat neutral background.
- `.section--inset`: framed inset panel inside full-width section.
- `.section--fade-right`: directional right fade for editorial/timeline rhythm.
- `.section--alt`: legacy alias of `.section--tint` (backward compatibility).

## Usage Rhythm
- Do not alternate every section (`plain/alt/plain/alt/...`).
- Prefer mostly `.section--plain`.
- Add one emphasized surface where grouping is needed.
- Use separators and spacing first; surface is secondary emphasis.

## Pattern Contract
- Use: `.section > .iss-container` as the structural default.
- Pattern CSS defines internal layout (grid/flex/etc.).
- Global CSS provides tokens/utilities and shared low-level primitives.
- Gutenberg conflict fixes belong in `assets/css/overrides.css` only, documented per override.

## Migration Notes
- Existing markup using `.section--alt` remains valid.
- New work should prefer named intent classes (`--tint`, `--soft`, `--inset`, `--fade-right`).
