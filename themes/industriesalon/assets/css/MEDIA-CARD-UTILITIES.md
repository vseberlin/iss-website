# ISS media card utilities

This utility layer gives you one reusable wrapper for image surfaces without touching `style.css`.

## What it solves

Before this, each pattern handled image borders, radius, overflow, shadow, rail color, and scaling on its own. That leads to drift. One image block gets rounded corners, another gets no border, another breaks because the figure is not full height.

The utility file moves that logic into one class family:

- `.iss-media-card` for the base image card shell
- modifier classes for rail color
- modifier classes for scale mode
- optional surface and radius variants

The pattern CSS then only handles layout.

## File order

Load in this order:

1. `style.css`
2. `iss-media-card-utilities.css`
3. pattern stylesheet such as `asymmetric-feature-v2.css`

That order matters because:

- `style.css` defines tokens like `--iss-red`, `--iss-radius-md`, `--iss-border`
- the utility file consumes those tokens
- the pattern file places the utility inside a specific layout

## Base usage

Use the base wrapper on the media container, not on the image itself.

```html
<div class="wp-block-group iss-media-card iss-media-card--red iss-media-card--cover">
  <figure class="wp-block-image size-full">
    <img src="..." alt="">
  </figure>
</div>
```

That gives you:

- border
- radius
- overflow clipping
- soft shadow
- left accent rail
- full image fill

## Color variants

Available rail colors:

- `.iss-media-card--red`
- `.iss-media-card--green`
- `.iss-media-card--blue`
- `.iss-media-card--yellow`
- `.iss-media-card--brown`
- `.iss-media-card--grey`

Use them only for the rail, not as large background fills. That stays closer to the current brand system: strong neutral base with restrained thematic accents.

Examples:

```html
<div class="iss-media-card iss-media-card--red iss-media-card--cover">...</div>
<div class="iss-media-card iss-media-card--blue iss-media-card--cover">...</div>
<div class="iss-media-card iss-media-card--brown iss-media-card--framed iss-media-card--contain">...</div>
```

## Scale variants

These define how the image behaves inside the card.

### `.iss-media-card--cover`

Best default for editorial sections, hero-adjacent blocks, cards with enforced height.

The image fills the card and crops when needed.

```html
<div class="iss-media-card iss-media-card--red iss-media-card--cover">...</div>
```

Use for:

- asymmetric feature
- lead cards
- query cards with locked heights
- image-heavy landing sections

### `.iss-media-card--contain`

Keeps the whole image visible inside the card. Useful when cropping would damage the content.

```html
<div class="iss-media-card iss-media-card--grey iss-media-card--contain">...</div>
```

Use for:

- scans
- posters
- brochures
- logos
- diagrams

### `.iss-media-card--natural`

Lets the image keep intrinsic height. Use when the wrapper should look like a card, but the image should not be forced into a full-height shell.

```html
<div class="iss-media-card iss-media-card--green iss-media-card--natural">...</div>
```

Use for:

- article images in flowing text
- single editorial images
- cases where the layout does not impose equal column height

## Other useful modifiers

### Surface strength

- `.iss-media-card--flat` removes shadow
- `.iss-media-card--soft` gives lighter shadow
- `.iss-media-card--strong` gives stronger shadow

### Rail width

- `.iss-media-card--rail-thin`
- `.iss-media-card--rail-wide`

### Radius

- `.iss-media-card--radius-sm`
- `.iss-media-card--radius-lg`

### Frame mode

- `.iss-media-card--framed`

This adds inner padding, so the image sits inside a white card frame instead of bleeding edge to edge.

## Height helpers

These are optional and mainly useful outside dedicated pattern layouts:

- `.iss-media-card--vh-40`
- `.iss-media-card--vh-50`
- `.iss-media-card--vh-60`

Example:

```html
<div class="iss-media-card iss-media-card--red iss-media-card--cover iss-media-card--vh-50">
  <figure class="wp-block-image size-full">
    <img src="..." alt="">
  </figure>
</div>
```

In a dedicated pattern like `iss-asymmetric-feature`, prefer the pattern layout height instead of the utility height helpers.

## Clean wiring in patterns

Do not mix utility responsibility and pattern responsibility.

### Utility file should handle

- border
- radius
- overflow
- shadow
- accent rail
- image fit behavior

### Pattern file should handle

- grid or columns
- min-height of the section
- content spacing
- desktop/mobile stacking
- decorative extras specific to that pattern

That separation is the clean part.

## Updated asymmetric feature wiring

In the updated pattern, the media wrapper is:

```html
<div class="wp-block-group iss-asymmetric-feature__media iss-media-card iss-media-card--red iss-media-card--cover">
```

This means:

- `iss-asymmetric-feature__media` = pattern placement
- `iss-media-card` = generic media shell
- `iss-media-card--red` = accent choice
- `iss-media-card--cover` = scale choice

So if later you want a quieter version, you change only the modifiers:

```html
<div class="wp-block-group iss-asymmetric-feature__media iss-media-card iss-media-card--grey iss-media-card--soft iss-media-card--cover">
```

Or for a poster/brochure block:

```html
<div class="wp-block-group iss-asymmetric-feature__media iss-media-card iss-media-card--brown iss-media-card--framed iss-media-card--contain">
```

The pattern CSS does not need to change.

## Gutenberg examples

### 1. Asymmetric opener

```html
<!-- wp:group {"className":"iss-asymmetric-feature__media iss-media-card iss-media-card--red iss-media-card--cover"} -->
<div class="wp-block-group iss-asymmetric-feature__media iss-media-card iss-media-card--red iss-media-card--cover">
  <!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"iss-asymmetric-feature__image"} -->
  <figure class="wp-block-image size-full iss-asymmetric-feature__image"><img src="..." alt=""></figure>
  <!-- /wp:image -->
</div>
<!-- /wp:group -->
```

### 2. Brochure / publication card

```html
<!-- wp:group {"className":"iss-media-card iss-media-card--brown iss-media-card--framed iss-media-card--contain"} -->
<div class="wp-block-group iss-media-card iss-media-card--brown iss-media-card--framed iss-media-card--contain">
  <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
  <figure class="wp-block-image size-large"><img src="..." alt=""></figure>
  <!-- /wp:image -->
</div>
<!-- /wp:group -->
```

### 3. Quiet archive image

```html
<!-- wp:group {"className":"iss-media-card iss-media-card--grey iss-media-card--flat iss-media-card--natural"} -->
<div class="wp-block-group iss-media-card iss-media-card--grey iss-media-card--flat iss-media-card--natural">
  <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
  <figure class="wp-block-image size-large"><img src="..." alt=""></figure>
  <!-- /wp:image -->
</div>
<!-- /wp:group -->
```

## Recommendation

For now, keep `style.css` untouched.

Add this utility file as a separate stylesheet and use the class family only in new patterns first. After you reuse it in two or three places successfully, it can become the standard image-surface layer across the theme.
