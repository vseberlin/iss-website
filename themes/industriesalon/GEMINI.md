# GEMINI.md




## Working style
- before start read handoff from last session handoff_CURRENT.md if not present create
- Prefer minimal, local changes.
- if changing unrelated files, formatting, or names ask permission.
- Keep explanations short.

## Environment quick reference
- Project root: `/home/vladimir/wp/themes/industriesalon/`
- Start stack: `docker compose up -d`
- Main services:
  - `wordpress` (site): `http://localhost:8082`
  - `phpmyadmin`: `http://localhost:8083`
  - `mailpit`: `http://localhost:8025`
- Source of truth for edits:
  - theme files: `themes/industriesalon` (bind-mounted)
  - plugin files: `plugins/*` (bind-mounted)
  - do not edit theme code under `wp/wp-content/themes`
- WP-CLI rule:
  - WP-CLI is available via Docker service `wpcli`
  - always use: `docker compose run --rm wpcli <command> --allow-root`
  - do not use: `docker compose exec wordpress wp ...` (wp binary not present there)
- DB connection (from docker-compose):
  - host: `db:3306`
  - database: `wordpress`
  - user: `wpuser`
  - password: `wp_pass`
- Active theme verification before edits:
  - `docker compose run --rm wpcli theme list --status=active --fields=name,status,version --format=table --allow-root`
  - `docker compose run --rm wpcli option get template --allow-root`
  - `docker compose run --rm wpcli eval 'echo get_theme_root().\"/\".get_option(\"template\");' --allow-root`
- Block template caveat:
  - `wp_template` DB entries can override disk templates (especially `front-page`)
  - when template edits look stale, verify DB template content first


## WordPress / Gutenberg rules
- Prioritize Gutenberg editor validity over frontend appearance when they disagree.
- If frontend renders but the editor rejects markup, diagnose the editor-side structural cause first.
- Preserve valid block markup. Avoid adding attributes or wrapper patterns that Gutenberg may strip or mark invalid.
- In block theme templates, keep markup as close as possible to expected Gutenberg block structure.
- Do not put inline `<script>` tags into block template HTML.
- If JavaScript is needed, load it separately through WordPress enqueue logic.
- For `templates/single-tour.html` and `templates/single-tour-on-demand.html`, do not use `layout":{"type":"constrained"}` on wrapper/group blocks; it reintroduces unwanted container offsets. Keep these templates without constrained layout attributes unless explicitly requested.
- Always check semantic structure first (`main`, `section`, `aside`, heading hierarchy). If intended structure is unclear, ask before implementing.
- For page sections, use an outer full-width wrapper section and put caged content in an inner `.iss-container` wrapper by default (unless explicitly requested otherwise).
- 

## CSS / UI work
- Compatibility first.
- Use existing classes where possible.
- New classes require clear need and should be minimal.
- Clever CSS tricks are restricted.
- Keep CSS tidy, local, and readable.
- Do not rewrite unrelated CSS.
- css edits schould be non destructive globaly before changing anything check an global impact
- Keep strict pattern contract: patterns own layout; global CSS should only provide design tokens/utilities.
- Patterns should not rely on global enforcement hacks; each pattern should define its own layout within `section > .iss-container`.
- Keep Gutenberg conflict fixes only in `themes/industriesalon/assets/css/overrides.css`, and only for concrete block/runtime clashes.
- Every override in `overrides.css` must be documented thoroughly in comments with:
  - Page/context
  - Block/class affected
  - Root cause (`why` this conflict happens)
  - Scope/intent (`where` and `what` is being fixed)


## Active code verification
- Before modifying a theme or plugin, verify:
  - active theme/plugin
  - active version
  - disk path of the active code
- If there is a mismatch, report it briefly before editing.

## Debugging workflow
- Diagnose first when the failure mode is unclear.
- If there were already multiple failed attempts, ignore prior hypotheses and use only the current file state as source of truth.
- When I say "reset", treat it as:
  - discard previous attempts and assumptions
  - use only the current files and current error
  - restate the task briefly
  - apply the smallest valid fix

  ## Exploratory mode

Use this mode when the task is unclear, multiple approaches exist, or architectural decisions are needed.

Priorities:
1. Do not implement immediately.
2. First analyze the problem, constraints, and existing codebase.
3. Identify risks, edge cases, and what can break.
4. Propose 2–3 viable approaches (not more), with trade-offs.
5. Prefer solutions that fit the current stack and avoid lock-in.
6. Highlight impact on:
   - existing markup / CSS
   - Gutenberg/editor usability
   - plugin vs theme responsibility
   - long-term maintenance
7. Keep proposals concrete (file locations, data flow, minimal structure), not abstract theory.
8. Avoid overengineering and new dependencies unless clearly justified.
9. Ask targeted clarification questions if a decision cannot be made safely.
10. Do not modify files until a direction is explicitly chosen.

Output format:
- Short problem framing
- Options (A/B/C) with pros/cons
- Recommended direction (if clear)
- Next step (what to implement once confirmed)

## Output format
- Return:
  - brief root cause
  - Do not pad the answer with generic advice.
  - Do not print intermediate reasoning or commands unless asked.
  - Do not print tool usage, shell commands, or traces.
