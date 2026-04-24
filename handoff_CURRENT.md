# Handoff Current

## Status
- `ready_for_next_session`

## Date / Window
- Date: 2026-04-24
- Timezone: Europe/Berlin

## Branch / Commit
- Branch: `master`
- HEAD at handoff write: `0407f4f`

## What Was Done This Session
- Continued plugin-stack refactor and moved runtime calendar/timeline rendering to `plugins/iss-programm`.
- Removed stale legacy dynamic block runtime in `plugins/saas-api` and aligned active dynamic blocks in `iss-programm`.
- Stabilized CPT-first workflow for booking and timeline:
  - `saas-api` REST now serves slots from local CPT flow.
  - sync horizon remains extended (future months) and sync preserves editorial/link mappings.
- Improved mapping model and editorial UX:
  - entry→content dropdown mapping in timeline editor,
  - hides manual public fields when content mapping exists,
  - mapping save now propagates to series and updates source/series map consistently.
- Fixed linked-content behavior:
  - calendar/timeline prefer linked content title when `source_post_id` exists,
  - slot payload includes `content_url`,
  - calendar date labels can link directly to mapped content.
- Removed temporary redirect-based behavior; system now relies on persisted CPT mapping only.
- Refreshed active front-page template (`wp_template` / front-page) to current disk template and validated timeline block renders from CPT data.

## Runtime Verification Snapshot
- Active theme: `industriesalon` (`1.1.0`).
- Active plugins include: `saas-api`, `iss-programm`, `iss-fuehrungen`, `industriesalon-steuerung`.
- `is-tours/v1/slots?post_id=12183` returns CPT-backed slots with linked content URL.
- Front page timeline block renders upcoming CPT items.

## Open Item (Important)
- Existing rows that were mapped before latest save-hook propagation may need one manual re-save of the mapping on affected entries to trigger series relink with current logic.

## Suggested Next Step
1. In timeline/editor mapping UI, re-save affected linked entries once.
2. Trigger sync and verify both single tour page calendar + front page timeline reflect identical linked CPT source.
3. Optional: add a small admin action for explicit "relink this series now" to avoid manual re-save.

## Continuity Prompt
- Start next session with: `read /home/vladimir/wp/handoff_CURRENT.md`.
