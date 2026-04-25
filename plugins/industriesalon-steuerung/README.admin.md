# Industriesalon Steuerung - Technical Guide

This guide is for developers and administrators.

## Purpose

The plugin provides a central source for shared site data:

- address
- contact
- visit hours
- office hours
- special days
- prices
- accessibility
- FAQ
- short house text

Outputs read from the same stored data and should not duplicate content.

## Active code path

Plugin directory:

`plugins/industriesalon-steuerung/`

Theme directory:

`themes/industriesalon/`

## Data model

Stored options:

- `iss_control_general`
- `iss_control_contact`
- `iss_control_maps`
- `iss_control_hours`
- `iss_control_accessibility`
- `iss_control_prices`
- `iss_control_faq`
- `iss_control_mission_statement`

Visit hours data:

- `public` = visit hours
- `office` = office hours
- `exceptions` = special days

## Rendering model

The preferred output path is the new visit-info flow:

- status
- compact hours
- full hours
- exceptions

Legacy `iss_hours` remains for compatibility.

## PHP helpers

```php
iss_get_status('museum');
iss_get_hours('museum', 'compact');
iss_get_hours('office', 'full');
iss_get_hours_block([
    'variant' => 'compact',
    'show_status' => true,
    'show_museum_hours' => true,
    'show_office_hours' => true,
    'show_exceptions' => false,
]);
iss_get_exceptions('museum');
```

Existing helpers still work:

```php
iss_control_get('contact.phone');
iss_control_get_section('hours');
```

## Shortcodes

```text
[iss_status type="museum"]
[iss_hours type="museum" variant="compact"]
[iss_hours type="office" variant="full"]
[iss_exceptions type="museum"]
```

Legacy grouped outputs remain available for compatibility.

## Gutenberg blocks

- `industriesalon/visit-info`
- `industriesalon/field`
- `industriesalon/hours`
- `industriesalon/contact`
- `industriesalon/prices`
- `industriesalon/faq`
- `industriesalon/mission-statement`

All are server-rendered.

## Admin UX

The visit-hours section uses plain labels:

- Besuchszeiten
- Bürozeiten
- Sondertage

Keep user-facing wording short and avoid technical terms in admin copy.

## Styling hooks

Theme stylesheet:

`themes/industriesalon/assets/css/visit-info.css`

Loaded from:

`themes/industriesalon/functions.php`

CSS custom properties:

- `--iss-visit-surface`
- `--iss-visit-text`
- `--iss-visit-muted`
- `--iss-visit-border`
- `--iss-visit-accent`
- `--iss-visit-radius`
- `--iss-visit-pad`
- `--iss-visit-gap`

These tokens control the visual layer without changing plugin markup.

## Cache

Resolved visit output is cached in transients and invalidated when hours are saved or imported.

## Validation

- timezone uses `wp_timezone()`
- invalid dates are dropped
- invalid times are dropped
- `open > close` is cleared during sanitizing

## Install

Copy the plugin folder into:

`wp-content/plugins/industriesalon-steuerung/`

Then activate it in WordPress.

## Operational notes

1. Export before larger changes.
2. Edit data in the plugin, not in page content.
3. Save.
4. Check one frontend page.
5. Clear page cache if needed.
