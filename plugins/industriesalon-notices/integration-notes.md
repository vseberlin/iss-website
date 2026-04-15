# Integration notes for the current Industriesalon theme

Current theme is a block theme with HTML template parts only.

That means the plugin can already store and resolve notices, but output placement needs one of these paths:

## Path A – shortcode block on front page

Fastest route.
Insert a shortcode block before or after the hero in `front-page.html` and use:

`[iss_notice area="front_page_banner"]`

This is simple but not ideal for overlap styling unless the surrounding markup is adjusted.

## Path B – small PHP render bridge in theme later

If the theme gets a PHP template part or render callback, use:

```php
<?php echo iss_render_notice('front_page_banner'); ?>
```

This is the cleaner route for a hero-overlap banner.

## Path C – dynamic block later

A custom dynamic block could be added later if needed, but that is not necessary for v1.

## Recommended sequence

1. install plugin
2. create one notice
3. test admin notice rendering
4. place front page output in a temporary shortcode block
5. after layout is settled, move to cleaner theme integration
