# Handoff Current

## Status
- `in_progress`

## Date / Window
- Date: 2026-04-14
- Timezone: Europe/Berlin
- Context window note: Prepared to survive context reset; this file is the continuity anchor.

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

## Next Step (first command)
- `cd /home/vladimir/wp && sed -n '1,220p' handoff_CURRENT.md`

## Prompt for Next Agent
- Read `/home/vladimir/wp/handoff_CURRENT.md`, treat plugin code as source of truth, preserve unrelated staged work, then continue with pending tasks.
