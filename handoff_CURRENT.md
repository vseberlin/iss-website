# Handoff Current

## Status
- `ready_for_next_session`

## Date / Window
- Date: 2026-04-14
- Timezone: Europe/Berlin

## Branch / Commit
- Branch: `master`
- HEAD: `233cff7`

## What Was Done Today (final)
- Stabilized theme header/off-canvas behavior and CSS/JS integration.
- Resolved repeated Gutenberg "Attempt recovery" loop on `front-page` by syncing template with actual Gutenberg-saved DB content.
- Confirmed `front-page` DB override can diverge from file template and cause persistent editor mismatch.
- Removed unsupported custom block reference from saved template flow (`industriesalon-banner`/notice custom block issue).
- Final stable state now uses shortcode in banner area and editor accepts recovered/saved version.

## Files Changed (final commit)
- `themes/industriesalon/templates/front-page.html`
- `themes/industriesalon/front-page.php` (deleted)
- `themes/industriesalon/scripts/flush-db-template-overrides.sh` (new, executable)

## Commit
- `233cff7` — `theme: stabilize front-page template and add override flush script`

## Important Operational Note
- For block themes, Gutenberg DB templates (`wp_template`, `wp_template_part`) are often source-of-truth at runtime.
- Editing only file templates can be overridden by DB-saved copies.

## New Utility Script
- Path: `themes/industriesalon/scripts/flush-db-template-overrides.sh`
- Purpose: delete DB template overrides for active theme so file templates become authoritative.

### Usage
- Flush all DB overrides for active theme:
  - `./themes/industriesalon/scripts/flush-db-template-overrides.sh`
- Flush only selected template names:
  - `./themes/industriesalon/scripts/flush-db-template-overrides.sh front-page,header`

## Current Working Tree Notes
- Untracked artifacts intentionally left in place:
  - `themes/industriesalon/industriesalon-header-fix.zip`
  - `themes/industriesalon/industriesalon-header-fix-v2.zip`
  - `themes/industriesalon/view-source_192.168.2.31_8082.html`

## Tomorrow: Recommended First Steps
1. Open Site Editor and verify `Front Page` and `Header` still load without recovery warning.
2. If mismatch reappears, run flush script, reopen editor, and save once.
3. Only then continue theme/UI edits.

## Continuity Prompt
- Start next session with: `read /home/vladimir/wp/handoff_CURRENT.md`.
