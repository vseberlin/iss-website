Session Summary (saas-api)
Date: 2026-04-09
Dir: plugins/saas-api

Changes Completed

- JS: removed dead code after parseDate (SuperSaaS URL)
- JS: slot click emits is:slotSelected and opens internal form
- JS: added escapeHtml and stopped raw title interpolation
- CSS: styles scoped to .is-tour-calendar
- Cache: slots cached 6 hours via set_transient

Pending (PHP)

- Remove bookingBase from inline config
- Add helpers: is_tours_get_cached_slots_by_tag, is_tours_get_next_slot
- Implement is_tours_create_booking with validation

Notes

- SuperSaaS URL logic removed from JS; internal booking only
- Event is:slotSelected dispatched on .is-tour-calendar element
- Payment: onsite enabled; Mollie disabled in UI for now
- CSS tokens: --wp--preset--color--accent and --site-color-border

Testing

- Page with shortcode: [is_tour_calendar tag=...]
- Listen: widget.addEventListener('is:slotSelected', e => console.log(e.detail))
- POST /wp-json/is-tours/v1/book { name, email, tickets, slot_id, payment }
