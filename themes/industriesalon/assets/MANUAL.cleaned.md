# Industriesalon CSS manual

## Principle

Keep these layers separate.

- `theme.json` = tokens, editor defaults, typography scale, palette, spacing presets.
- `style.css` = site-wide base rules, reusable components, utility layout, plugin theme layer.
- `front-page.css` = only front-page layout compensation and hero-specific positioning.
- `header.css`, `kicker.css`, other files = optional component files, but they must not take over page layout responsibilities.

The main failure in the current setup was mixing these layers. The same class families were doing four jobs at once: width control, component appearance, Gutenberg correction, and page layout.

## What changed

The cleaned files keep the current naming but reduce cross-coupling.

- `.iss-heading` is back to being a heading component only. It no longer controls site-wide container alignment.
- `.iss-card-grid` owns grid layout. `.iss-card` owns card appearance. They no longer try to solve each other’s spacing through very specific Gutenberg selectors.
- front page hero rules stay in `front-page.css` only.
- mission statement offset stays page-local.
- plugin visit info styles stay theme-owned but remain presentation-only.

## How to use classes

### Container and section

Use `.iss-container` for width and side padding.

Use `.section` for vertical rhythm.

Do not put width hacks into `.iss-heading`, `.iss-kicker`, `.iss-card`, or plugin classes.

### Kicker and heading

Use `.iss-kicker` only for the small label line.

Use `.iss-heading` for heading blocks.

Example structure:

```html
<div class="iss-heading">
  <p class="iss-kicker">Aktuelle Kalender</p>
  <h2 class="iss-heading__title">Veranstaltungen</h2>
  <p class="iss-heading__text">Kurzer Einleitungstext.</p>
</div>
```

Do not use `.iss-heading` to push blocks into grids or compensate container offsets.

### Cards

Use `.iss-card-grid` on the wrapper.

Use `.iss-card` on each item.

Example structure:

```html
<div class="iss-card-grid">
  <article class="iss-card">
    <figure class="iss-card__media"><img src="" alt=""></figure>
    <div class="iss-card__body">
      <p class="iss-kicker iss-kicker--compact">Führung</p>
      <h3 class="iss-card__title">Titel</h3>
      <p class="iss-card__text">Text</p>
      <div class="iss-card__footer">
        <a class="iss-card__link" href="#">Mehr</a>
      </div>
    </div>
  </article>
</div>
```

Do not place page-specific width rules inside `.iss-card-grid`.

### Indent block

Use `.iss-indent` only when you actually want a visual left rail.

Do not use `.iss-indent` as a generic wrapper around blocks that later need to become full width.

That was one of the reasons content became caged and then needed uncaging.

## Front page rules

`front-page.css` is allowed to do these things:

- hero cover inner container compensation
- overlapping banner positioning
- front-page mission statement offset
- front-page-only tour teaser corrections

`front-page.css` should not redefine general card styling, kicker styling, or site-wide typography.

## Gutenberg rule of thumb

When Gutenberg adds wrappers like:

- `.has-global-padding`
- `.is-layout-constrained`
- `.wp-block-group-is-layout-constrained`

compensate them only where the problem appears.

Do not copy those selectors into global component styles unless the component itself is broken everywhere.

## Practical editing rule

Before adding CSS, ask which job it belongs to.

- token/editor default → `theme.json`
- reusable component visual style → `style.css`
- one template or one section layout fix → page CSS file
- block markup problem → fix markup first, CSS second

## Migration note

These cleaned files are intended to replace the current versions of:

- `theme.json`
- `style.css`
- `front-page.css`

The remaining files can stay as they are for now, but they should gradually be checked against the same split.
