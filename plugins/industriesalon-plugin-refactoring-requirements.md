# Industriesalon Plugin Refactoring Requirements

## 1. Goal

The plugin stack shall be refactored into clear layers with stable responsibilities.

The current problem is that the SaaS integration, tour rendering, calendar/timeline output, notices, and potential sales/payment logic are mixed across plugins. The refactor shall separate low-level data import, dated programme rendering, booking logic, publication sales, checkout/payment, and theme styling.

The priority is stable SuperSaaS slot mapping to local content, automatic where possible, with a simple manual correction workflow for non-technical admins.

Project context: this is an early development system. The refactor should prioritize a clean target architecture and stability over backward-compatibility layers. No production rollout strategy is required.

## 2. Target plugin stack

### `saas-api`

Low-level SuperSaaS data adapter only.

Responsibilities:

- Store SuperSaaS API/settings.
- Fetch SuperSaaS slots/events.
- Normalize imported data.
- Store imported data locally as CPT/cache records.
- Maintain mapping between imported SaaS slot series and local content.
- Provide admin mapping correction screen.
- Provide REST/helper functions for other plugins.
- Provide sync status and error reporting.

Must not own:

- Frontend calendar rendering.
- Timeline rendering.
- Tour templates.
- Booking forms.
- Payment logic.
- Public design CSS.

### `iss-programm`

Shared dated-content layer.

Responsibilities:

- Own or coordinate the shared programme content model.
- Represent dated public content such as Führungen, Ausstellungen, Veranstaltungen.
- Render shared calendar, timeline, list and card views.
- Consume mapped SaaS data from `saas-api`.
- Present different content types with different display variants.

Content types:

- `fuehrung`
- `ausstellung`
- `veranstaltung`

These may be implemented as one CPT with a type taxonomy, or as separate CPTs with a shared programme interface. Preferred for admin simplicity: one programme concept with clear type selection.

### `iss-fuehrungen`

Tour-specific behavior only.

Responsibilities:

- Add booking/inquiry/payment behavior for programme items of type `fuehrung`.
- Render Führung-specific booking panels.
- Handle capacity/availability wording.
- Handle group/individual/on-demand booking logic.
- Use `iss-checkout` for paid bookings if payment is enabled.

Must not become the owner of Ausstellungen, Veranstaltungen, publications, timeline or general checkout.

### `iss-publications`

Publication content and sale trigger layer.

Responsibilities:

- Own publication CPT or publication content model.
- Render publication cards/details.
- Store price, availability, description, image and sale options.
- Provide “buy/order” modal trigger.
- Offer online/onsite purchase choice.
- Use `iss-checkout` for online payment.

Must not own Mollie webhook logic directly.

### `iss-checkout`

Shared lightweight order and payment layer.

Responsibilities:

- Own order CPT/storage.
- Create pending orders.
- Create Mollie payment requests.
- Redirect users to Mollie checkout.
- Receive Mollie webhooks.
- Update payment/order status.
- Send confirmation emails.
- Expose helper functions for bookings and publications.

Used by:

- `iss-fuehrungen` for paid bookings.
- `iss-publications` for online publication orders.

### `industriesalon-notices`

Notice content and scheduling only.

Responsibilities:

- Own notice CPT/content.
- Handle scheduling and visibility/scope.
- Provide active notice lookup.
- Output simple semantic markup if needed.

Styling should live in the theme/pattern CSS, not scattered inside the plugin.

### `iss-timeline`

Optional separate plugin only if timeline remains plugin-rendered.

Preferred rule:

- Timeline data/query/rendering belongs to `iss-programm` if it mainly shows programme content.
- Historical/archive timeline can be a separate `iss-timeline` plugin.
- Timeline must not live inside `saas-api`.

### Theme `industriesalon`

Visual system only.

Responsibilities:

- Global design tokens.
- Layout primitives.
- Cards.
- Pattern CSS.
- Plugin bridge styling.
- Frontend visual consistency.

Must not own business logic.

## 3. Dependency graph

Preferred direction:

```text
theme
  styles output from all plugins

saas-api
  imports and stores SaaS data

iss-programm
  reads saas-api data
  renders calendar/timeline/list/card views

iss-fuehrungen
  extends programme items of type fuehrung
  uses iss-checkout for payment when needed

iss-publications
  owns publications
  uses iss-checkout for online sales

iss-checkout
  owns orders and Mollie payment lifecycle

industriesalon-notices
  owns notices only
```

Forbidden direction:

```text
saas-api -> public calendar rendering
saas-api -> timeline rendering
saas-api -> booking UI
iss-fuehrungen -> general event rendering
iss-fuehrungen -> publication sales
iss-publications -> Mollie webhook ownership
```

## 4. SaaS slot mapping requirements

### Goal

`saas-api` must reliably map imported SuperSaaS slot series to local content. Mapping should be automatic where safe and manually correctable where uncertain.

Manual correction must be simple enough for non-technical admins.

### Imported slot fields

Every imported slot must store:

- SuperSaaS slot ID.
- SuperSaaS title.
- Cleaned title.
- Canonical series UID (`{calendar_id}:{series_key}`).
- Generated series key.
- Start date/time.
- End date/time.
- Capacity.
- Available places.
- Booking URL.
- Source calendar.
- Sync status.
- Last synced timestamp.
- Mapped content post ID.
- Mapped content post type.
- Mapping status.
- Mapping method.
- Mapping confidence.
- Mapping note.

### Mapping statuses

Required statuses:

- `auto_mapped`
- `manual_mapped`
- `unmapped`
- `ambiguous`
- `ignored`

### Mapping methods

Required methods:

- `manual`
- `calendar_tag`
- `series_key`
- `title_match`
- `existing_slot_history`
- `none`

Manual mapping always overrides automatic mapping.

### Mapping invariants

- Mapping is keyed by canonical `series_uid`.
- One `series_uid` maps to max one local content item at a time.
- `manual_mapped` and `ignored` cannot be overridden by automatic sync.
- Mapping writes must be idempotent (same input, same stored result).

## 5. Automatic mapping rules

The system should attempt mapping in this order:

1. Existing manual mapping for the same series key.
2. Exact `calendar_tag` match on local programme content.
3. Previous slot mapping with the same SuperSaaS series key.
4. Normalized title match.
5. High-confidence fuzzy title match.
6. Otherwise mark as `unmapped` or `ambiguous`.

Rules:

- Automatic mapping must never overwrite manual mapping.
- If several local content items match one series, mark as `ambiguous`.
- If no confident match exists, mark as `unmapped`.
- Ignored series must stay ignored across syncs unless manually reset.
- Mapping must be stored at series level and applied to future slots in that series.

### Mapping status transitions

Allowed transitions:

- `unmapped` -> `auto_mapped`, `manual_mapped`, `ignored`, `ambiguous`
- `ambiguous` -> `manual_mapped`, `ignored`, `unmapped`
- `auto_mapped` -> `manual_mapped`, `ignored`, `unmapped`
- `manual_mapped` -> `unmapped` (only via explicit manual clear), `ignored`
- `ignored` -> `unmapped` (only via explicit manual reset)

Disallowed transition rule:

- Automatic sync must never move `manual_mapped` or `ignored` to another status.

## 6. Admin UI: SaaS Calendar Sync

The admin page “SaaS Calendar Sync” shall be the main correction interface.

It should be grouped by imported SuperSaaS series, not by every individual slot.

Each row should show:

- SuperSaaS title.
- Cleaned title.
- Next slot date.
- Number of future slots.
- Current mapped content item.
- Content type.
- Mapping status.
- Confidence indicator.
- Last synced timestamp.
- Actions.

Actions:

- Select local content from searchable dropdown.
- Clear mapping.
- Mark series as ignored.
- Apply mapping to all future slots in this series.
- Optionally also update past slots.
- Open mapped content edit page.
- Open imported slot list.
- Run sync now.

Admin labels should avoid technical wording.

Use labels like:

- “Gefundene Terminreihe”
- “Zugeordnete Seite / Führung / Veranstaltung”
- “Zuordnung prüfen”
- “Manuell zuordnen”
- “Diese Zuordnung für kommende Termine verwenden”
- “Keine passende Zuordnung”
- “Ignorieren”

Avoid labels like:

- `post_id`
- `meta_key`
- `series_key`
- `mapping confidence`

## 7. Manual correction behavior

When an admin manually maps a series to local content:

- All existing future slots with the same series key receive the selected content ID.
- The series map is updated.
- The mapping method becomes `manual`.
- The mapping status becomes `manual_mapped`.
- Manual mapping survives future syncs.
- Past slots remain unchanged unless explicitly selected.
- The mapped content edit screen shows the active SaaS mapping.

Optional behavior:

- Updating the content item’s `calendar_tag` may be offered as a checkbox.
- It must not happen silently.

## 8. Programme content model

Führungen, Ausstellungen and Veranstaltungen are all dated programme content.

They share:

- Title.
- Description.
- Image.
- Date/time visibility.
- Optional mapped SaaS slot series.
- Calendar/list/timeline visibility.
- Archive/detail URL.

They differ in rendering:

### Führung

Needs:

- Booking status.
- Available dates.
- Booking button.
- Inquiry fallback.
- Optional payment flow.

### Ausstellung

Needs:

- Exhibition period.
- Venue.
- Curator/partner if relevant.
- Opening event link if relevant.
- No booking logic by default.

### Veranstaltung

Needs:

- Event date/time.
- Event location.
- Optional speaker/organizer.
- Optional external registration link.
- No tour booking logic by default.

Requirement wording:

“The system shall treat Führungen, Ausstellungen and Veranstaltungen as dated programme content. All programme items may be mapped to imported SaaS slots and shown in calendar/timeline views. Only items of type Führung require booking/payment logic. Shared rendering must live in a programme layer, while type-specific behavior is added by dedicated modules.”

## 9. Frontend rendering ownership

### `iss-programm` owns:

- Public programme calendar.
- Public programme timeline.
- Programme cards/lists.
- Shared filters by content type.
- Shared date grouping.
- Shared empty states.

### `iss-fuehrungen` owns:

- Tour booking panel.
- Tour-specific CTA.
- Tour availability text.
- Group/individual/on-demand logic.

### `iss-publications` owns:

- Publication cards.
- Publication detail render.
- Sale modal trigger.

### Theme owns:

- Final CSS.
- Layout primitives.
- Card look.
- Plugin visual bridge selectors.

## 10. Public helper functions

`saas-api` should expose stable helpers:

```php
iss_saas_get_slots_for_post($post_id, $args = [])
iss_saas_get_next_slot_for_post($post_id)
iss_saas_get_series_map()
iss_saas_get_unmapped_series()
iss_saas_set_manual_mapping($series_key, $post_id)
iss_saas_clear_manual_mapping($series_key)
iss_saas_sync_now()
```

`iss-checkout` should expose stable helpers:

```php
iss_checkout_create_order($args)
iss_checkout_create_mollie_payment($order_id)
iss_checkout_get_order($order_id)
iss_checkout_mark_paid($order_id, $payment_data)
iss_checkout_mark_failed($order_id, $payment_data)
iss_checkout_get_payment_redirect_url($order_id)
```

`iss-programm` may expose:

```php
iss_programm_get_items($args = [])
iss_programm_get_item_dates($post_id)
iss_programm_render_card($post_id, $context = 'default')
iss_programm_render_calendar($args = [])
iss_programm_render_timeline($args = [])
```

## 11. SaaS fallback behavior

If SuperSaaS API fails:

- Keep existing local slots.
- Mark sync status as stale.
- Show admin warning.
- Do not delete future slots immediately.
- Frontend continues from local CPT/cache data.
- Public pages must never require live SaaS calls during page load.

Sync durability rules:

- Sync runs under a lock to prevent concurrent writes.
- Imported slots are upserted by SuperSaaS slot ID.
- Keep `last_seen_at` per slot and series.
- Missing slots are first marked stale; do not hard-delete on first miss.
- Do not clear valid mapping because of temporary title drift or transient API gaps.
- Persist `last_successful_sync_at` and `last_sync_error` for admin diagnostics.

If no mapping exists:

- Programme views can still show unmapped imported items only if configured.
- Tour pages should show inquiry/fallback booking behavior from `iss-fuehrungen`.
- Admin should see unmapped series in SaaS Calendar Sync.

## 12. Publication sales flow

Publication sales are similar to tour booking but must not live inside `iss-fuehrungen`.

### Flow

1. User clicks “Publikation kaufen” or similar CTA.
2. Modal opens.
3. User chooses:
   - “Vor Ort kaufen”
   - “Online bestellen”
4. If onsite:
   - Show pickup/contact note or simple reservation/inquiry message.
5. If online:
   - Create pending order through `iss-checkout`.
   - Redirect to Mollie checkout.
   - Mollie webhook updates order status.
   - Confirmation email is sent.
   - Admin can see paid order.

### Publication fields

`iss_publication` should store:

- Title.
- Description.
- Image.
- Price.
- Availability/status.
- Pickup note.
- Shipping note if needed.
- Online sale enabled/disabled.
- Optional stock quantity.

## 13. Shared checkout requirements

The checkout layer exists to avoid duplicating payment logic.

It must own:

- Order storage.
- Payment status.
- Mollie payment creation.
- Webhook route.
- Webhook validation.
- Confirmation emails.
- Admin order view.

It must not own:

- Tour-specific wording.
- Publication-specific render.
- Calendar logic.
- Programme logic.

### Order data model

`iss_order` should store:

- Order type: `publication`, `tour`, possibly future types.
- Item ID.
- Quantity.
- Customer name.
- Customer email.
- Amount.
- Currency.
- Payment method.
- Payment status.
- Mollie payment ID.
- Created timestamp.
- Paid timestamp.
- Order notes.

### Payment statuses

Required statuses:

- `pending`
- `paid`
- `failed`
- `cancelled`
- `expired`
- `refunded` optional

Requirement wording:

“The system shall provide a shared lightweight checkout layer for simple paid actions. Publications and paid Führung bookings may create orders through this layer. The checkout layer owns order storage, Mollie payment creation, webhook processing, payment status updates and confirmation emails. Product-specific plugins remain responsible only for their own content and frontend trigger UI.”

## 14. Notices requirements

`industriesalon-notices` should remain separate.

It should own:

- Notice CPT.
- Notice title/text/link.
- Start/end scheduling.
- Placement/scope.
- Active notice query.

It should avoid:

- Large frontend layout CSS.
- Hero-specific positioning.
- Business logic unrelated to notices.

The theme should style notice output through stable classes:

- `.iss-hero-note`
- `.iss-hero-note__kicker`
- `.iss-hero-note__title`
- `.iss-hero-note__text`
- `.iss-hero-note__link`

## 15. CSS and theme boundary

Plugin output should use stable semantic classes.

Plugins should output markup, not own the full visual language.

The theme should own:

- Colors.
- Typography.
- Card styling.
- Section spacing.
- Calendar/timeline visual bridge.
- Notice visual bridge.

The current theme already has a global authority stylesheet and separates global tokens, card internals and pattern-specific layout. New plugin markup should align with this system instead of adding isolated CSS islands.

## 16. Admin usability requirements

Admin screens must be designed for non-technical users.

Required principles:

- Show content titles, not IDs.
- Use searchable dropdowns.
- Group repeated SaaS slots into series.
- Clearly show “needs attention”.
- Never silently overwrite manual corrections.
- Provide safe preview/open links.
- Use German UI labels.
- Keep advanced diagnostics collapsible.

Suggested admin dashboard areas:

- Sync status.
- Unmapped series.
- Ambiguous mappings.
- Recently changed SaaS titles.
- Manual mappings.
- Ignored series.
- Stale data warning.

## 17. Refactor implementation order (early-dev big-bang)

This is a direct architecture refactor in one development stream. No rollout strategy, dual-run mode, or long-lived compatibility layer is required.

### Step 1: Baseline snapshot

- Export current plugin files and responsibilities.
- Export current slot CPT + series mapping state for recovery.
- Document current public renders (single tour, calendar, timeline).

### Step 2: Clean `saas-api` to data adapter

Keep only:

- Settings.
- SuperSaaS client.
- Sync.
- CPT/cache storage.
- Mapping logic.
- Admin mapping page.
- REST/helper API.

Remove or move:

- Calendar render.
- Timeline render.
- Frontend JS/CSS not required for admin.
- Booking UI.

### Step 3: Create `iss-programm`

- Add shared programme model.
- Add calendar/list/timeline rendering.
- Add type filters.
- Consume mapped SaaS data.

### Step 4: Reduce `iss-fuehrungen`

- Keep only Führung-specific fields and behavior.
- Use `iss-programm` for programme display.
- Use `iss-checkout` for paid booking flow.

### Step 5: Add `iss-checkout`

- Add order CPT.
- Add Mollie payment creation.
- Add webhook endpoint.
- Add status updates.
- Add confirmation emails.

### Step 6: Add `iss-publications`

- Add publication CPT.
- Add sale modal.
- Use `iss-checkout` for online purchase.

### Step 7: Clean notices and CSS

- Keep notices plugin semantic.
- Move visual styling into theme/pattern CSS.
- Remove scattered plugin CSS where possible.

## 18. Acceptance criteria

The refactor is successful when:

- A non-technical admin can fix SaaS-to-content mapping without editing IDs.
- Manual SaaS mapping survives future syncs.
- Public pages never depend on live SuperSaaS API calls.
- `saas-api` can be understood as data import/cache only.
- `iss-programm` is the only owner of shared calendar/timeline/list rendering.
- `iss-fuehrungen` is the only owner of tour booking behavior.
- `iss-publications` is the only owner of publication sales UI.
- `iss-checkout` is the only owner of Mollie/order/webhook logic.
- Notice styling is no longer scattered across plugin CSS.
- Theme remains the visual authority.
- Repeated manual sync runs keep mapped future slot counts stable for the same tour.
- Refreshing a tour page does not intermittently remove already imported available slots.
- If SaaS sync fails, previously imported future slots remain visible from CPT/cache.

## 19. Short architecture statement

SuperSaaS is a data source, not the frontend owner.

Programme is the public dated-content layer.

Führungen are programme items with booking behavior.

Ausstellungen and Veranstaltungen are programme items without booking behavior.

Publications are sellable content, not programme items by default.

Checkout is shared payment infrastructure.

The theme owns visual presentation.
