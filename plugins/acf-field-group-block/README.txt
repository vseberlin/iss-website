ACF Field Group Block
=====================

What changed in this package:
- Added Gutenberg style controls: Variant, Columns, Accent.
- Added single vs multi field selection mode.
- Added optional ACF group key/title override settings.
- Added matching block attributes in block.json.
- Added frontend wrapper classes in PHP render callback.
- Added CSS for default/card/minimal variants and 1/2 column layouts.
  - Highlight accent now adds a subtle background.

Install:
1. Upload the folder or ZIP to /wp-content/plugins/
2. Activate “ACF Field Group Block”
3. In the block sidebar, use the new “Style” panel

New style options:
- Variant: Default, Card, Minimal
- Columns: 1 or 2
- Accent: None or Highlight

New field options:
- Selection mode: Single or Multiple fields
- Optional group key/title override for targeting a different ACF group
  - Field list updates dynamically when group key/title changes
- Group dropdown lets you pick an ACF field group (no default hard-coded group)
