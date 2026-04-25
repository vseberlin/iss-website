saas-api
Data adapter only. Owns SuperSaaS settings, fetch, sync, CPT storage, normalized slot API. No frontend calendar UI, no timeline rendering, no booking form, no design CSS.

iss-fuehrungen
Domain plugin. Owns Führung CPT, booking mode, “next date”, archive cards, single template, calendar block rendering, booking handoff/payment. It should consume saas-api via functions/REST, not let SaaS render the UI.
iss-timeline should not live inside saas-api. Timeline is content/archive presentation. Either move it into its own plugin iss-timeline, or into the theme if it is purely visual.

CSS direction: your theme stylesheet already defines itself as the global authority for tokens, layout primitives, sections, kickers, headings and plugin-compatible card alignment. That supports moving plugin visual CSS into the theme/design layer instead of each plugin inventing its own system.