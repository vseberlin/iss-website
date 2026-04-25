# Visit Info Implementation Note

Source of truth: current `industriesalon-steuerung` plugin.

Confirmed rules:

- `industriesalon/visit-info` renders stacked parts.
- The block does not render a section heading.
- The page or theme owns any heading/title above the block.
- Empty sections stay hidden.

What still needs to be defined clearly:

- exact visitor card fields
- exact field labels shown to editors
- exact stacked order for the visible parts
- exact hide/show rules for optional rows
- exact class names used by the theme CSS
- exact mobile behavior for the stacked layout

Implementation constraint:

- keep the current plugin data model and current admin source of truth
- do not introduce a new settings system
- keep user-facing wording short and plain
