The Problem: The Header and Hero are in different DOM branches (.wp-site-blocks vs deep inside .entry-content). Because they are not siblings in a shared container, you are forced to use position: absolute on the header, which decouples it from the page flow.
The Result: You have to manually adjust the header's top position and the hero's height/padding to ensure they look correct together.
---
Proposed Solution: Modern CSS Grid/Stacking
Instead of absolute positioning, use a CSS Grid "Stacking" approach or the View Transitions API for smoother entry. This allows the header to remain in the document flow while sharing the same visual space as the hero.

1. Restructure the DOM (Template Level)
Move the Header and the Hero section into a shared parent container (e.g., a "Site Header" template part that includes both).


<!-- Proposed Structure -->
<div class="site-hero-stack">
  <header class="site-header">...</header>
  <section class="wp-block-cover hero-cta">...</section>
</div>


Apply CSS Grid Stacking (No Overrides Needed)
By placing both in the same grid cell, they overlap naturally without needing top: 32px or absolute coordinates.

.site-hero-stack {
  display: grid;
  grid-template-areas: "stack";
  align-items: start; /* Header at the top */
}

.site-hero-stack > * {
  grid-area: stack;
}

.site-header {
  z-index: 2;
  /* No more position: absolute! */
  /* No more top: 32px overrides! */
}

.hero-cta {
  z-index: 1;
}

Benefits of this Approach:
Eliminates Admin Bar Overrides: Since the header is in the normal flow, the WordPress admin bar will naturally push the entire .site-hero-stack down. You can delete the body.admin-bar .site-header-overlay { top: 32px; } rule entirely.
Reduces Header Overrides: You no longer need left: 0; width: 100%; because the grid container handles the sizing.
Better Accessibility: Screen readers and keyboard navigation follow the DOM order more naturally when elements aren't absolutely positioned.
Flexibility: If you want a "Sticky" header later, you just change position: sticky on the header, and it won't break the hero's layout.

Try wrapping the Header and Hero in a Group Block in the Site Editor, set that Group to display: grid (via a custom CSS class), and remove the position: absolute from the .site-header-overlay class.