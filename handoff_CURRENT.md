# Handoff Current

## Status
- `ready_for_next_session`

## Date / Window
- Date: 2026-04-24
- Timezone: Europe/Berlin

## Branch / Commit
- Branch: `master`
- HEAD at handoff write: `eda3876`

## What Was Done This Session
- Added section surface contract and usage docs:
  - `themes/industriesalon/style.css`
  - `themes/industriesalon/zebra.md`
  - committed as `9e71f7d`.
- Implemented tour booking mapping workflow in plugin:
  - reintroduced `calendar_tag` as structured tour meta,
  - added non-technical tag selection UI with source-map suggestions,
  - on tour save: sync map + attempt relink of matching calendar series,
  - committed as `eda3876`.
- Added admin warning in tour edit screen:
  - warns if booking mode expects calendar (`calendar|hybrid`) but no linked future dates are found.
  - file: `plugins/iss-fuehrungen/includes/admin-fuehrung.php`.

## Runtime Verification Snapshot
- Active theme verified previously in this environment: `industriesalon` (`1.1.0`).
- `iss_calendar_source_map` currently includes:
  - `ELEKTRO -> source_post_id 12183` (`fuehrung`).
- Frontend for `/fuehrungen/elektropolis-tour/` currently shows no linked future dates in booking panel (warning logic intentionally surfaces this state in admin).

## Open Item (Important)
- Calendar items are currently mostly `source_post_id=0` in DB snapshots; mapping exists but linkage is not consistently materialized.
- Next session should resolve linking persistence at source of truth (sync import path in `saas-api`), not only at tour save side effects.

## Suggested Next Step
1. Diagnose why sync writes `source_post_id=0` despite `iss_calendar_source_map` being populated for `ELEKTRO`.
2. Fix import/linking in `plugins/saas-api/iss-calendar/includes/calendar-sync.php`.
3. Run manual sync and verify:
   - linked rows for `tour:elektropolis`,
   - single page booking panel shows next date again.

## Continuity Prompt
- Start next session with: `read /home/vladimir/wp/handoff_CURRENT.md`.
