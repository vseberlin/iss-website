# Handoff Current

## Status
- `in_progress`

## Date / Window
- Date: 2026-04-14
- Timezone: Europe/Berlin
- Context window note: Prepared to survive context reset; this file is the continuity anchor.

## Auto-Handoff Policy
- If context usage goes above 80%, update this file immediately.
- At end of each task, update this file again.
- On new/reset session, first instruction is: read `handoff_CURRENT.md`.

## Branch / Commit
- Branch: `master`
- HEAD: `39d47c6`

## Objective
- Primary goal: Stabilize and improve `industriesalon-steuerung` UX for non-technical admins.
- Scope limits: Do not touch unrelated `plugins/saas-api/*` staged work.

## Decisions (with rationale)
- Source of truth is plugin code, not drafts.
  - Why: Draft ZIP/readme may lag implementation.
- German-first admin wording and 24h/date locale behavior.
  - Why: Admin users are German-speaking and non-technical.
- Duplicate save action top + bottom.
  - Why: Users forget to save.
- Add both visible quick help and WP contextual help tabs.
  - Why: Discoverable day-to-day help + deeper on-demand help.

## Completed Changes
- File: `plugins/industriesalon-steuerung/industriesalon-steuerung.php`
  - Added admin help tabs via `load-$hook` + `register_help_tabs()`.
  - Added visible `Schnellhilfe` panel in admin screen.
  - Version bumped to `0.2.2`.
- File: `plugins/industriesalon-steuerung/assets/admin.css`
  - Added styling for `.iss-help-panel`.
- File: `plugins/industriesalon-steuerung/CHANGELOG.md`
  - Added `0.2.2` release entry.
- Artifact: `plugins/industriesalon-steuerung-v0.2.2.zip`
  - Fresh packaged plugin ZIP.
- Artifact refreshed: `plugins/industriesalon-steuerung-v0.2.2.zip`
  - Rebuilt so ZIP now includes `CHANGELOG.md` and matches live plugin folder.
- Deploy step completed:
  - Synced `plugins/industriesalon-steuerung` to `wp_app` and reactivated plugin.
- New isolated worktree copy for SaaS plugin:
  - `plugins/v1/saas-api` created from current `plugins/saas-api` (original left untouched).
- `plugins/v1/saas-api` v1 behavior changes:
  - Removed runtime shortcode fallback for calendar (`is_tour_calendar`) from `saas-api.php`.
  - Disabled timeline shortcode loader by removing `includes/shortcodes.php` include in `iss-timeline.php`.
  - Sync is now SaaS-first: imports entries even without existing source map/content page.
  - Auto-upserts source-map entries during sync (with `supersaas_title`, fallback URL).
  - Imported SaaS entries set `is_visible=1` and `calendar_tag` so they appear in timeline.
  - Added admin warnings for upcoming SaaS entries missing `source_post_id`.
- Additional v1 explicit-mapping implementation (per latest direction):
  - Source-of-truth model changed to explicit editor fields:
    - Source post fields: `calendar_tag`, `calendar_saas_title`
    - Calendar item field: explicit `source_post_id` selector in item editor
  - Removed hidden render-time mapping writes from block render path.
  - Sync now resolves `source_post_id` from explicit source-post fields first (`calendar_tag`, then `calendar_saas_title`), not from front-end usage.
  - SaaS entries still import without source content and are marked visible for timeline (`is_visible=1`), with admin warnings for unmapped entries.
  - Added conflict counting/warnings for ambiguous mapping candidates.
- Latest v1 UX hardening for non-technical staff:
  - Bulk linking no longer uses numeric ID input; now uses relationship dropdown (`Verknüpfter Inhalt`) with “Nicht ändern” / “Verknüpfung entfernen”.
  - Technical wording replaced with plain German across settings + linking/sync UI:
    - `Schedule ID`, `Schedule Path`, `Source Post ID/Type`, `Apply same series` replaced by German labels and one-line helper texts.
  - Sync screen/table labels translated (`SaaS-Kalenderabgleich`, `Zuordnungstabelle`, `Verknüpfter Inhalt`, etc.).
  - Calendar item/source metabox texts translated and simplified.
  - New ZIP rebuilt after these changes: `plugins/v1/saas-api-v1.zip`.
- DB maintenance action completed:
  - Removed all `wp_template_part` entries with `post_name='header'` from `wp_posts` and matching `wp_postmeta`.
  - Deleted IDs: `11540`, `11872`, `11956`, `12172`.
  - Verification query result: `remaining = 0`.

## Validation
- Command: `docker exec wp_app php -l /var/www/html/wp-content/plugins/industriesalon-steuerung/industriesalon-steuerung.php`
  - Result: No syntax errors.
- Command: `docker compose run --rm wpcli plugin deactivate industriesalon-steuerung && docker compose run --rm wpcli plugin activate industriesalon-steuerung`
  - Result: Deactivate/activate successful.

## Known Risks / Notes
- Repo currently has unrelated staged changes in `plugins/saas-api/*` and `themes`; do not include them in plugin-focused commits unless explicitly requested.
- `plugins/*` is git-ignored, so plugin commits require `git add -f`.

## Pending Tasks (ordered)
1. If requested, align German/English READMEs with final `0.2.2` UI labels exactly.
2. If requested, wire a dedicated “Hilfe” admin subpage with expanded docs.
3. If requested, tag/release workflow for ZIP artifacts.
4. Keep this handoff updated automatically by policy.
5. If requested: activate/test `plugins/v1/saas-api` in container.
6. If requested: refresh `plugins/v1/saas-api-v1.zip` after latest explicit-mapping changes.
7. If requested: install `plugins/v1/saas-api-v1.zip` and run end-to-end admin UX check.

## Next Step (first command)
- `cd /home/vladimir/wp && sed -n '1,220p' handoff_CURRENT.md`

## Prompt for Next Agent
- Read `/home/vladimir/wp/handoff_CURRENT.md`, treat plugin code as source of truth, preserve unrelated staged work, then continue with pending tasks.
