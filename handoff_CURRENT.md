# Handoff Current

## Status
- `ready_for_next_session`

## Date / Window
- Date: 2026-04-21
- Timezone: Europe/Berlin

## Branch / Commit
- Branch: `master`
- HEAD: `7c979e2`

## What Was Done Today
- Verified active runtime before edits:
  - active theme: `industriesalon` `v1.1.0`
  - active path: `/var/www/html/wp-content/themes/industriesalon`
- Committed current `themes/industriesalon` changes:
  - commit `7c979e2` with message `Update industriesalon theme`
- Updated root `AGENTS.md` with environment quick reference:
  - Docker services/URLs
  - WP-CLI via `docker compose run --rm wpcli ... --allow-root`
  - active theme verification commands
  - DB template override caveat (`wp_template`)
- Front-page hero banner structure update:
  - moved `iss-front-banner-slot` to direct child of `.wp-block-cover.iss-front-hero`
  - files:
    - `themes/industriesalon/templates/front-page.html`
    - `themes/industriesalon/assets/css/front-page.css`
- Purged DB `wp_template` override for front page:
  - deleted template post `ID 12602` (`post_name: front-page`)
  - flushed WP cache
- Banner renderer change (plugin output markup):
  - confirmed active plugin source is `industriesalon-notices` (not `industriesalon-steuerung`) for block `industriesalon/notice-banner`
  - changed rendered markup to:
    - `<aside class="iss-hero-note" role="note">`
    - `iss-hero-note__eyebrow`, `iss-hero-note__title`, `iss-hero-note__text`, `iss-hero-note__cta`
  - plugin text now drives snippet content (with fallbacks if fields are empty)
  - files:
    - `plugins/industriesalon-notices/industriesalon-notices.php`
    - `themes/industriesalon/assets/css/front-page.css` (selector updated from `.iss-notice__inner` to `.iss-hero-note`)

## Commits Created This Session
- `7c979e2` — `Update industriesalon theme`

## Validation Performed
- Active theme/version/path checks via WP-CLI.
- Active plugin checks via WP-CLI:
  - `industriesalon-steuerung` `0.3.0`
  - `industriesalon-notices` `0.1.2`
- PHP lint passed:
  - `plugins/industriesalon-notices/industriesalon-notices.php`
- Cache flush completed after template/plugin changes.

## Notes / Open Items
- Important: `front-page` DB override was deleted, so disk template currently drives front page.
- Uncommitted changes currently present:
  - `AGENTS.md`
  - `themes/industriesalon/templates/front-page.html`
  - `themes/industriesalon/assets/css/front-page.css`
  - `plugins/industriesalon-notices/industriesalon-notices.php`
- Some unrelated non-theme working tree changes still exist.

## Continuity Prompt
- Start next session with: `read /home/vladimir/wp/handoff_CURRENT.md`.
- Before editing front-page banner again:
  - verify whether a new `wp_template` `front-page` override was recreated
  - verify banner markup source in `industriesalon-notices` render callback.
