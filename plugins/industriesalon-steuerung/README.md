# Industriesalon Steuerung

Zentrale Pflege für wiederverwendbare Inhalte im WordPress-Admin.

## Dokumentation

- Redaktion: `README.de.md`
- Technik: `README.admin.md`

## Enthalten

- Besuchszeiten, Bürozeiten und Sondertage
- Adresse, Kontakt, Karte, Preise, FAQ, Barrierefreiheit, Kurztext
- Ausgabe per PHP, Shortcode und Gutenberg-Block
- JSON-Export und JSON-Import

## Wichtige Ausgabe

```php
iss_get_status('museum');
iss_get_hours('museum', 'compact');
iss_get_hours('office', 'full');
iss_get_hours_block([
    'variant' => 'compact',
    'show_status' => true,
    'show_museum_hours' => true,
    'show_office_hours' => true,
    'show_exceptions' => false,
]);
iss_get_exceptions('museum');
```

Block:

`industriesalon/visit-info`

## Styling

Theme stylesheet:

`themes/industriesalon/assets/css/visit-info.css`

