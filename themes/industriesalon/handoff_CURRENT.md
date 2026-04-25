# Handoff - April 24, 2026

## Overview
Successfully transitioned the theme toward a "Passive Stability" model by moving away from Gutenberg's `constrained` layout in favor of explicit `layout: default` containers. This allowed for significant CSS simplification and improved the visual consistency of the programme calendar.

## Key Changes

### 1. Theme Architecture & Stability
*   **Front-Page Test:** Created `templates/front-page-test.html` using `layout: default`. Registered as "Front Page Test (Default Layout)" in `theme.json`.
*   **CSS Simplification:** Removed over 20 redundant `!important` flags in `style.css` and `patterns.css` that were previously "fighting" Gutenberg's auto-margins.
*   **Global Standards:** Consolidated section vertical rhythm and heading measure tokens into the end of `style.css`.
*   **Gutenberg Core Fixes:** Centralized the Query Loop baseline reset in `style.css` to ensure perfectly flat card rows across all templates.

### 2. Single Tour Templates
*   **Vertical Balance:** Moved "Further Tours" (Query Loop) from the bottom into the main left column (70% width) to eliminate white space voids next to the calendar.
*   **Editorial Polish:** Added `.iss-hero-anchor` (red rail) to hero text and `.is-sticky-aside` to the booking column.
*   **Grid Optimization:** Switched discovery cards to a 2-column `.iss-card--flat` grid for better density within the narrowed content column.

### 3. ISS Programm Plugin (Calendar & Modal)
*   **High Visibility:** Switched to a "Green Theme" (`#579e7d`) with high-contrast borders (`#d0d0d0`) and solid inactive date backgrounds (`#e8e8e8`).
*   **Flatpickr Alignment:** Forced perfect vertical column sync between weekdays and dates using CSS Grid and direct targeting of internal classes.
*   **Compact Style:** Reduced day-cell height to `3rem` for a more technical, elegant editorial look.
*   **Cache Busting:** Updated `includes/assets.php` to use `filemtime()` for CSS/JS versioning, ensuring changes reflect immediately on hard refresh.
*   **Accented Modal:** Updated the booking popup with a matching green rail, bold typography, and improved focus states.

## Known Exceptions
*   `overrides.css` is now minimal, containing only a single template-specific visual adjustment for tour main padding.

## Next Steps
*   If the test front-page template is approved, it can be renamed to `front-page.html` to replace the old version.
*   The `.home` scopes remaining in `patterns.css` can be further pruned now that alignment is handled by `layout: default`.
