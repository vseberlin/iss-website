Core Logic Layer
1. Resolver

Function:
resolve_day(schedule, date)

Order:

check matching exception
if found → return override
else → resolve from schedule (period or weekly)
return structured result:
status
time ranges
label
source (regular / exception)
2. Formatter

Functions:

format_day_range(days[])
→ “Do–So”
format_time_ranges(ranges[])
→ “14–18 Uhr”
format_period(label, days, times)
→ “April–September: Do–So, 14–18 Uhr”
Output Layer
Required outputs
1. Status (front page)
heute geöffnet / geschlossen / Sonderöffnung
optional: “bis … Uhr”
2. Compact summary
“Museum: Do–So, 14–18 Uhr”
“Büro: Di–Do, 10–16 Uhr; Fr, 10–14 Uhr”
3. Full block
grouped by:
Museum
Büro
includes:
periods or weekly grouped rows
upcoming exceptions (optional)
4. Exceptions list
only future / relevant entries
Interfaces
1. Dynamic Block (primary)

Block: industriesalon/visit-info

Controls:

show_status
show_museum_hours
show_office_hours
show_exceptions
variant: compact | full | inline

Server-rendered:
render_callback

2. Shortcodes (secondary)
[iss_status type="museum"]
[iss_hours type="museum" variant="compact"]
[iss_hours type="office"]
[iss_exceptions]
3. PHP API
iss_get_status('museum')
iss_get_hours('museum', 'compact')
iss_get_hours_block()
iss_get_exceptions()
Admin UI

Menu: Industriesalon Steuerung

Tabs:

Museum Öffnungszeiten
Bürozeiten
Ausnahmen / Feiertage
Allgemeine Angaben (Adresse, Preise, Labels)

Constraints:

no free-text primary fields
structured inputs only
optional note fields
Rendering Rules
never output long prose by default
always structured:
headings
short lines
group days into ranges (Do–So)
collapse identical rows
show exceptions only if relevant
Non-Functional
no dependency on ACF
all data in options or custom tables
cache resolved output (transients)
timezone-aware (Europe/Berlin)
fail-safe fallback (no fatal if empty)
Minimal Scope (MVP)
weekly schedule
exceptions
status output
compact + full render
one dynamic block