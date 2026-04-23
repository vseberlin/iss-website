# Handoff Current

## Status
- `ready_for_next_session`

## Date / Window
- Date: 2026-04-22
- Timezone: Europe/Berlin

## Branch / Commit
- Branch: `master`
- HEAD: `aa55a05`

## What Was Done Today
- Verified active runtime before edits:
  - active theme: `industriesalon` `v1.1.0`
  - active path: `/var/www/html/wp-content/themes/industriesalon`
- Installed and activated `iss-fuehrungen`:
  - source zip installed from `themes/industriesalon/assets/css/staging/iss-fuehrungen.zip`
  - status now: `active` `v1.0.0`
- Removed `calendar_tag` ownership from `iss-fuehrungen` plugin package and installed copy:
  - removed from admin UI/meta in:
    - `plugins/iss-fuehrungen/includes/admin-fuehrung.php`
    - `plugins/iss-fuehrungen/includes/meta-fuehrung.php`
- Removed obsolete `saas-api` manual mapping UI remnants:
  - deleted `plugins/saas-api/iss-calendar/includes/calendar-editor.php`
  - removed include from `plugins/saas-api/iss-calendar/iss-calendar.php`
  - `calendar_tag` / `calendar_saas_title` no longer registered as post meta
- Installed `classic-editor-and-classic-widgets` plugin from staging zip:
  - zip: `themes/industriesalon/assets/css/staging/classic-editor-and-classic-widgets.zip`
  - installed by unpacking into `plugins/classic-editor-and-classic-widgets`
  - status: `inactive` `v1.5.1`
- Wired Führungen filter helper into theme:
  - include added in `themes/industriesalon/functions.php`
  - helper file now loaded from:
    - `themes/industriesalon/assets/css/staging/industriesalon-fuehrungen-filters.php`
- Reworked Führungen filters to client-side tabs (no URL redirect/reload):
  - tabs are injected above query loop (`Alle`, `Gruppen`, `Individuell`, `Kinder/Familie`, `Besonders`, `Bus`, `Regular`)
  - card filtering now hides the Query Loop wrapper item (`li.wp-block-post`) to avoid grid gaps
  - legacy label/slug normalization added (`group/individual/special` -> `gruppen/individuell/besonders`)

## Validation Performed
- WP-CLI runtime checks:
  - confirmed active theme/plugin states
  - confirmed `iss/tour-calendar` and `iss/tour-dates` blocks registered
- Helper runtime checks:
  - helper function load check passed
  - `/fuehrungen/` and `/a-fuhrungen/` return `HTTP 200`
  - resolved fatal from `query_loop_block_query_vars` signature mismatch (`WP_Block` vs `array`)
- Linkage audit snapshot:
  - `fuehrung` posts had no active source mapping at audit time
  - source map had `ELEKTRO` entry without `source_post_id`
  - future `iss_calendar_item` entries existed but were mostly unlinked (`source_post_id=0`)

## Current Runtime Snapshot
- Active theme: `industriesalon` `1.1.0`
- Active plugins:
  - `iss-fuehrungen` `1.0.0`
  - `saas-api` `1.2.0`
  - `industriesalon-notices` `0.1.2`
  - `industriesalon-steuerung` `0.3.0`
- Installed but inactive:
  - `classic-editor-and-classic-widgets` `1.5.1`

## Uncommitted Changes (important)
- This session touched:
  - `plugins/saas-api/iss-calendar/iss-calendar.php`
  - `plugins/saas-api/iss-calendar/includes/calendar-editor.php` (deleted)
  - `themes/industriesalon/functions.php`
  - `themes/industriesalon/assets/css/staging/industriesalon-fuehrungen-filters.php`
  - `themes/industriesalon/assets/css/staging/iss-fuehrungen.zip`
  - `plugins/iss-fuehrungen/*` (installed from zip)
  - `plugins/classic-editor-and-classic-widgets/*` (installed)
- There are also unrelated working-tree changes from before this session.

## Notes / Open Items
- On `a-fuhrungen`, Query Loop cards currently render category terms (`group`, `individual`, `special`) in card markup; helper currently normalizes these for tabs.
- If taxonomy output is standardized to `fuehrung_typ`, simplify helper normalization map.
- Mapping/data linking between `fuehrung` and `iss_calendar_item` still needs content-level assignment (not a code error).

## Continuity Prompt
- Start next session with: `read /home/vladimir/wp/handoff_CURRENT.md`.
- If continuing Führungen UI work:
  - verify current `a-fuhrungen` section markup still includes class `iss-fuehrungen-query`
  - verify tab filter behavior after any template/content edits
  - optionally migrate card term output from `category` to `fuehrung_typ`.
