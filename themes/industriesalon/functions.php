<?php
if (!defined('ABSPATH')) {
    exit;
}

$industriesalon_fuehrungen_filters_helper = get_stylesheet_directory() . '/assets/php/industriesalon-fuehrungen-filters.php';
if (file_exists($industriesalon_fuehrungen_filters_helper)) {
    require_once $industriesalon_fuehrungen_filters_helper;
}

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('editor-styles');
    add_editor_style(
        array(
            'style.css',
            'assets/css/cards.css',
            'assets/css/patterns.css',
            'assets/css/overrides.css',
        )
    );
});

/**
 * Register local block patterns from theme files.
 */
function industriesalon_register_block_patterns(): void
{
    if (!function_exists('register_block_pattern')) {
        return;
    }

    if (function_exists('register_block_pattern_category')) {
        register_block_pattern_category(
            'industriesalon',
            array(
                'label' => 'Industriesalon',
            )
        );
    }

    $theme_dir = get_stylesheet_directory();
    $registry = class_exists('WP_Block_Patterns_Registry')
        ? WP_Block_Patterns_Registry::get_instance()
        : null;

    $patterns = array(
        array(
            'name' => 'industriesalon/info-panel-anmeldung',
            'title' => 'Kontakt & Anmeldung',
            'description' => 'Kontaktdaten und Anmelde-Infos für Führungen und Gruppen.',
            'categories' => array('industriesalon', 'text'),
            'file' => '/patterns/pattern-info-panel-anmeldung.html',
        ),
        array(
            'name' => 'industriesalon/info-panel-besuch',
            'title' => 'Besuch planen',
            'description' => 'Öffnungszeiten, Adresse und Hinweise für Besucher.',
            'categories' => array('industriesalon', 'text'),
            'file' => '/patterns/pattern-info-panel-besuch.html',
        ),
        array(
            'name' => 'industriesalon/info-panel-vermietung',
            'title' => 'Raum mieten',
            'description' => 'Kontakt-Block für Raumvermietungs-Anfragen.',
            'categories' => array('industriesalon', 'text'),
            'file' => '/patterns/pattern-info-panel-vermietung.html',
        ),
        array(
            'name' => 'industriesalon/feature-split',
            'title' => 'Text + Bild (geteilt)',
            'description' => 'Großes Bild auf einer Seite, langer Text auf der anderen. Für Hauptthemen.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-section-feature-split.html',
        ),
        array(
            'name' => 'industriesalon/1to4-grid',
            'title' => 'Leitkarte + Raster',
            'description' => 'Eine große Hauptkarte links, vier kleinere Karten rechts im Raster.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-1to4-grid.html',
        ),
        array(
            'name' => 'industriesalon/50-50-media-text',
            'title' => '50/50 Bild-Text',
            'description' => 'Gleich breites Bild und Text nebeneinander. Klar und ausgewogen.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-50-50-media-text.html',
        ),
        array(
            'name' => 'industriesalon/asymmetric-feature',
            'title' => 'Versetztes Feature',
            'description' => 'Bild und Text versetzt angeordnet — dynamisch und visuell interessant.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-asymmetric-feature.html',
        ),
        array(
            'name' => 'industriesalon/4-card-row',
            'title' => '4 Kompakt-Karten',
            'description' => 'Vier kleinere Karten mit Bild — ideal für Partner, Kategorien oder kurze Themen.',
            'categories' => array('industriesalon', 'media', 'cards'),
            'file' => '/patterns/iss-4-card-row.html',
        ),
        array(
            'name' => 'industriesalon/3-card-row',
            'title' => '3 Info-Karten',
            'description' => 'Drei gleichwertige Inhaltsblöcke nebeneinander — für Angebote, Themen oder Team.',
            'categories' => array('industriesalon', 'media', 'cards'),
            'file' => '/patterns/iss-3-card-row.html',
        ),
        array(
            'name' => 'industriesalon/newsletter-funders',
            'title' => 'Newsletter + Förderung',
            'description' => 'Newsletter-Anmeldung mit Förderer-Logos darunter.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-newsletter-funders.html',
        ),
        array(
            'name' => 'industriesalon/archive-landing',
            'title' => 'Archiv-Startseite',
            'description' => 'Startseite für Archiv-Bereiche mit Filterleiste und Kartenraster.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/archive-landing.html',
        ),
        array(
            'name' => 'industriesalon/mission-support-strip',
            'title' => 'Mission + Fakten',
            'description' => 'Missions-Aussage mit drei unterstützenden Kennzahlen/Fakten darunter.',
            'categories' => array('industriesalon', 'text'),
            'file' => '/patterns/iss-section-mission-support-strip.html',
        ),
        array(
            'name' => 'industriesalon/landing-hero-with-note',
            'title' => 'Hero mit Hinweis',
            'description' => 'Großes Headerbild mit einer Hinweis-Box rechts daneben.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-landing-hero-with-note.html',
        ),
    );

    foreach ($patterns as $pattern) {
        if ($registry && $registry->is_registered($pattern['name'])) {
            continue;
        }

        $file_path = $theme_dir . $pattern['file'];
        if (!file_exists($file_path)) {
            continue;
        }

        $content = file_get_contents($file_path);
        if ($content === false) {
            continue;
        }

        $content = preg_replace('/^<!--[\s\S]*?-->\s*/', '', $content, 1);

        register_block_pattern(
            $pattern['name'],
            array(
                'title' => $pattern['title'],
                'description' => $pattern['description'],
                'categories' => $pattern['categories'],
                'inserter' => true,
                'content' => $content,
            )
        );
    }
}
add_action('init', 'industriesalon_register_block_patterns');

/**
 * Force zero margin in editor canvas to match frontend gap-less layout.
 */
add_action('admin_head', function() {
    echo '<style>
        .interface-interface-skeleton__content { background-color: #fff; }
        .is-root-container.block-editor-block-list__block { margin-top: 0 !important; padding-top: 0 !important; }
    </style>';
});

/**
 * Enqueue theme assets.
 */
function industriesalon_enqueue_assets(): void
{
    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();
    $theme = wp_get_theme();
    $version = $theme->get('Version');

    wp_enqueue_style(
        'industriesalon-base',
        get_stylesheet_uri(),
        array(),
        $version
    );

    $cards_rel = '/assets/css/cards.css';
    $cards_abs = $theme_dir . $cards_rel;
    if (file_exists($cards_abs)) {
        wp_enqueue_style(
            'industriesalon-cards',
            $theme_uri . $cards_rel,
            array('industriesalon-base'),
            (string) filemtime($cards_abs)
        );
    }

    $patterns_rel = '/assets/css/patterns.css';
    $patterns_abs = $theme_dir . $patterns_rel;
    if (file_exists($patterns_abs)) {
        $patterns_dependencies = file_exists($cards_abs)
            ? array('industriesalon-cards')
            : array('industriesalon-base');

        wp_enqueue_style(
            'industriesalon-patterns',
            $theme_uri . $patterns_rel,
            $patterns_dependencies,
            (string) filemtime($patterns_abs)
        );
    }

    $overrides_rel = '/assets/css/overrides.css';
    $overrides_abs = $theme_dir . $overrides_rel;
    if (file_exists($overrides_abs)) {
        $overrides_dependencies = file_exists($patterns_abs)
            ? array('industriesalon-patterns')
            : (file_exists($cards_abs)
                ? array('industriesalon-cards')
                : array('industriesalon-base'));

        wp_enqueue_style(
            'industriesalon-overrides',
            $theme_uri . $overrides_rel,
            $overrides_dependencies,
            (string) filemtime($overrides_abs)
        );
    }

    // Header JS
    $script_rel_path = '/assets/js/header.js';
    $script_abs_path = get_stylesheet_directory() . $script_rel_path;
    if (file_exists($script_abs_path)) {
        wp_enqueue_script(
            'industriesalon-header',
            get_stylesheet_directory_uri() . $script_rel_path,
            array(),
            $version,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'industriesalon_enqueue_assets');

// =============================================================================
// CUSTOMIZER — Design-Einstellungen für nicht-technische Nutzer
// Appearance → Anpassen → Industriesalon Design
// =============================================================================

function industriesalon_customizer_register(WP_Customize_Manager $wp_customize): void
{
    $wp_customize->add_panel('iss_design', array(
        'title'       => 'Industriesalon Design',
        'description' => 'Farben und Layout der Website anpassen — ohne Code-Kenntnisse.',
        'priority'    => 30,
    ));

    // ── Section: Farben ──────────────────────────────────────────────────────
    $wp_customize->add_section('iss_colors', array(
        'title'       => 'Farben',
        'panel'       => 'iss_design',
        'description' => 'Hauptfarben der Website. Änderungen wirken sich auf Karten, Buttons und Trennlinien aus.',
    ));

    $wp_customize->add_setting('iss_color_primary', array(
        'default'           => '#e81d25',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'iss_color_primary', array(
        'label'       => 'Akzentfarbe (Rot)',
        'description' => 'Karten-Rand, Buttons, Links und Trennlinien.',
        'section'     => 'iss_colors',
    )));

    $wp_customize->add_setting('iss_color_green', array(
        'default'           => '#579e7d',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'iss_color_green', array(
        'label'       => 'Highlight-Grün',
        'description' => 'Kalender-Akzente und Touren-Seiten.',
        'section'     => 'iss_colors',
    )));

    $wp_customize->add_setting('iss_color_yellow', array(
        'default'           => '#ebbc1e',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'iss_color_yellow', array(
        'label'       => 'Gelb',
        'description' => 'Warnhinweise und Badges.',
        'section'     => 'iss_colors',
    )));

    // ── Section: Layout ──────────────────────────────────────────────────────
    $wp_customize->add_section('iss_layout', array(
        'title'       => 'Layout',
        'panel'       => 'iss_design',
        'description' => 'Breite und Abstände der Inhaltsbereiche.',
    ));

    $wp_customize->add_setting('iss_content_width', array(
        'default'           => '1720px',
        'sanitize_callback' => 'industriesalon_sanitize_content_width',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('iss_content_width', array(
        'label'       => 'Inhaltsbreite',
        'description' => 'Wie breit ist der Seiteninhalt? "Normal" empfohlen.',
        'section'     => 'iss_layout',
        'type'        => 'select',
        'choices'     => array(
            '1400px' => 'Schmal (1400px)',
            '1720px' => 'Normal (1720px) – empfohlen',
            '1920px' => 'Breit (1920px)',
        ),
    ));

    $wp_customize->add_setting('iss_section_spacing', array(
        'default'           => 'normal',
        'sanitize_callback' => 'industriesalon_sanitize_section_spacing',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('iss_section_spacing', array(
        'label'       => 'Abstand zwischen Abschnitten',
        'description' => 'Luft zwischen den Inhaltsbereichen einer Seite.',
        'section'     => 'iss_layout',
        'type'        => 'select',
        'choices'     => array(
            'compact'    => 'Kompakt',
            'normal'     => 'Normal – empfohlen',
            'spacious'   => 'Großzügig',
        ),
    ));
}
add_action('customize_register', 'industriesalon_customizer_register');

function industriesalon_sanitize_content_width(string $value): string
{
    return in_array($value, array('1400px', '1720px', '1920px'), true) ? $value : '1720px';
}

function industriesalon_sanitize_section_spacing(string $value): string
{
    return in_array($value, array('compact', 'normal', 'spacious'), true) ? $value : 'normal';
}

function industriesalon_customizer_css_output(): void
{
    $primary  = sanitize_hex_color((string) get_theme_mod('iss_color_primary', '#e81d25'));
    $green    = sanitize_hex_color((string) get_theme_mod('iss_color_green', '#579e7d'));
    $yellow   = sanitize_hex_color((string) get_theme_mod('iss_color_yellow', '#ebbc1e'));
    $width    = industriesalon_sanitize_content_width((string) get_theme_mod('iss_content_width', '1720px'));
    $spacing  = industriesalon_sanitize_section_spacing((string) get_theme_mod('iss_section_spacing', 'normal'));

    $padding_map = array(
        'compact'  => array('3rem', '2rem'),
        'normal'   => array('5rem', '3rem'),
        'spacious' => array('8rem', '5rem'),
    );
    [$pad_lg, $pad_sm] = $padding_map[$spacing] ?? $padding_map['normal'];

    $css  = ":root {\n";
    $css .= "  --iss-red: {$primary};\n";
    $css .= "  --iss-green: {$green};\n";
    $css .= "  --iss-yellow: {$yellow};\n";
    $css .= "  --iss-content-width: {$width};\n";
    $css .= "}\n";
    $css .= ".section { padding-top: {$pad_sm}; padding-bottom: {$pad_sm}; }\n";
    $css .= "@media (min-width: 782px) { .section { padding-top: {$pad_lg}; padding-bottom: {$pad_lg}; } }\n";

    wp_add_inline_style('industriesalon-base', $css);
}
add_action('wp_enqueue_scripts', 'industriesalon_customizer_css_output', 20);

function industriesalon_customizer_preview_js(): void
{
    if (!is_customize_preview()) {
        return;
    }
    $script = <<<'JS'
(function() {
  function setCSSVar(varName, value) {
    document.documentElement.style.setProperty(varName, value);
  }
  wp.customize('iss_color_primary', function(v) { v.bind(function(val) { setCSSVar('--iss-red', val); }); });
  wp.customize('iss_color_green',   function(v) { v.bind(function(val) { setCSSVar('--iss-green', val); }); });
  wp.customize('iss_color_yellow',  function(v) { v.bind(function(val) { setCSSVar('--iss-yellow', val); }); });
  wp.customize('iss_content_width', function(v) { v.bind(function(val) { setCSSVar('--iss-content-width', val); }); });
})();
JS;
    wp_add_inline_script('customize-preview', $script);
}
add_action('customize_preview_init', 'industriesalon_customizer_preview_js');

// =============================================================================
// ADMIN GUIDE — Site-Handbuch für nicht-technische Nutzer
// Darstellung → Site-Handbuch
// =============================================================================

add_action('admin_menu', function () {
    add_theme_page(
        'Site-Handbuch',
        'Site-Handbuch',
        'edit_posts',
        'iss-site-guide',
        'industriesalon_render_site_guide'
    );
});

function industriesalon_render_site_guide(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('Keine Berechtigung.', 'industriesalon'));
    }

    $customize_url  = esc_url(admin_url('customize.php') . '?autofocus[panel]=iss_design');
    $new_page_url   = esc_url(admin_url('post-new.php?post_type=page'));
    $fuehrung_url   = esc_url(admin_url('edit.php?post_type=fuehrung'));
    $steuerung_url  = esc_url(admin_url('admin.php?page=industriesalon-steuerung'));
    $media_url      = esc_url(admin_url('upload.php'));
    ?>
    <div class="wrap" style="max-width:1000px">
        <h1><?php esc_html_e('Site-Handbuch', 'industriesalon'); ?></h1>
        <p class="description" style="font-size:1rem;margin-bottom:2rem">
            <?php esc_html_e('Hier finden Sie Kurzanleitungen für die häufigsten Aufgaben. Kein Codieren nötig.', 'industriesalon'); ?>
        </p>

        <?php /* ── Schnellstart ── */ ?>
        <h2 style="margin-top:0"><?php esc_html_e('Schnellstart', 'industriesalon'); ?></h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;margin-bottom:2.5rem">
            <?php
            $quicklinks = array(
                array(
                    'url'   => $new_page_url,
                    'icon'  => '📄',
                    'label' => 'Neue Seite erstellen',
                    'hint'  => 'Leere Seite anlegen und Bausteine einfügen.',
                ),
                array(
                    'url'   => $fuehrung_url,
                    'icon'  => '🗺️',
                    'label' => 'Führungen bearbeiten',
                    'hint'  => 'Touren und Führungsangebote verwalten.',
                ),
                array(
                    'url'   => $steuerung_url,
                    'icon'  => '⚙️',
                    'label' => 'Öffnungszeiten & Kontakt',
                    'hint'  => 'Zentrale Angaben für die gesamte Website.',
                ),
                array(
                    'url'   => $customize_url,
                    'icon'  => '🎨',
                    'label' => 'Farben & Layout anpassen',
                    'hint'  => 'Akzentfarbe und Abstände live einstellen.',
                ),
                array(
                    'url'   => $media_url,
                    'icon'  => '🖼️',
                    'label' => 'Bilder verwalten',
                    'hint'  => 'Fotos hochladen und in Beiträge einbinden.',
                ),
            );
            foreach ($quicklinks as $ql) {
                printf(
                    '<a href="%1$s" style="display:block;padding:1.25rem;background:#fff;border:1px solid #e0e0e0;border-left:4px solid #e81d25;border-radius:6px;text-decoration:none;color:#1e1e1e">
                        <div style="font-size:1.5rem;margin-bottom:.4rem">%2$s</div>
                        <strong>%3$s</strong>
                        <p style="margin:.25rem 0 0;font-size:.85rem;color:#666">%4$s</p>
                    </a>',
                    esc_url($ql['url']),
                    $ql['icon'],
                    esc_html($ql['label']),
                    esc_html($ql['hint'])
                );
            }
            ?>
        </div>

        <?php /* ── Häufige Aufgaben ── */ ?>
        <h2><?php esc_html_e('Häufige Aufgaben', 'industriesalon'); ?></h2>
        <?php
        $tasks = array(
            array(
                'q' => 'Wie füge ich einen neuen Abschnitt auf einer Seite ein?',
                'a' => '<ol>
                    <li>Seite in der Seitenübersicht öffnen → <strong>Bearbeiten</strong> klicken.</li>
                    <li>Im Editor auf das <strong>blaue Plus-Symbol (+)</strong> klicken.</li>
                    <li>Reiter <strong>„Muster"</strong> wählen, dann links <strong>„Industriesalon"</strong>.</li>
                    <li>Gewünschtes Muster anklicken — es wird eingefügt.</li>
                    <li>Texte und Bilder direkt im Muster bearbeiten.</li>
                    <li><strong>Aktualisieren</strong> klicken.</li>
                </ol>',
            ),
            array(
                'q' => 'Wie ändere ich ein Bild in einer Karte oder einem Abschnitt?',
                'a' => '<ol>
                    <li>Bild im Editor anklicken.</li>
                    <li>In der Werkzeugleiste <strong>„Ersetzen"</strong> wählen.</li>
                    <li>Bild aus der Mediathek auswählen oder neu hochladen.</li>
                    <li><strong>Aktualisieren</strong> klicken.</li>
                </ol>',
            ),
            array(
                'q' => 'Wie bearbeite ich die Öffnungszeiten?',
                'a' => '<ol>
                    <li>Im Adminmenü links auf <strong>„Steuerung"</strong> klicken.</li>
                    <li>Den Reiter <strong>„Öffnungszeiten"</strong> auswählen.</li>
                    <li>Zeiten anpassen, Ausnahmen (z.B. Feiertage) eintragen.</li>
                    <li><strong>„Änderungen speichern"</strong> klicken. Die Zeiten erscheinen automatisch auf der Website.</li>
                </ol>',
            ),
            array(
                'q' => 'Wie ändere ich die Hauptfarbe der Website?',
                'a' => '<ol>
                    <li>Im Adminmenü links auf <strong>„Darstellung" → „Anpassen"</strong> klicken.</li>
                    <li>Den Bereich <strong>„Industriesalon Design" → „Farben"</strong> öffnen.</li>
                    <li>Bei <strong>„Akzentfarbe (Rot)"</strong> die gewünschte Farbe wählen.</li>
                    <li>Die Vorschau rechts aktualisiert sich sofort.</li>
                    <li><strong>„Veröffentlichen"</strong> klicken.</li>
                </ol>',
            ),
            array(
                'q' => 'Wie füge ich eine neue Führung hinzu?',
                'a' => '<ol>
                    <li>Im Adminmenü links auf <strong>„Führungen" → „Neu hinzufügen"</strong> klicken.</li>
                    <li>Titel, Beschreibung, Bild und Buchungs-Informationen eintragen.</li>
                    <li><strong>„Veröffentlichen"</strong> klicken.</li>
                    <li>Die Führung erscheint automatisch im Führungs-Archiv der Website.</li>
                </ol>',
            ),
            array(
                'q' => 'Wie bearbeite ich den Footer (Fußzeile)?',
                'a' => '<ol>
                    <li>Im Adminmenü auf <strong>„Darstellung" → „Editor"</strong> klicken.</li>
                    <li>Links auf <strong>„Muster" → „Fußzeile"</strong> klicken.</li>
                    <li>Texte und Links direkt bearbeiten.</li>
                    <li><strong>Speichern</strong> klicken.</li>
                    <li><em>Hinweis: E-Mail-Adressen und Telefonnummern am besten über „Steuerung" ändern.</em></li>
                </ol>',
            ),
        );
        foreach ($tasks as $task) {
            printf(
                '<details style="margin-bottom:.75rem;border:1px solid #e0e0e0;border-radius:6px;background:#fff">
                    <summary style="padding:1rem 1.25rem;cursor:pointer;font-weight:600;font-size:1rem">%1$s</summary>
                    <div style="padding:.25rem 1.25rem 1.25rem;border-top:1px solid #f0f0f0">%2$s</div>
                </details>',
                esc_html($task['q']),
                wp_kses_post($task['a'])
            );
        }
        ?>

        <?php /* ── Bausteine-Galerie ── */ ?>
        <h2 style="margin-top:2rem"><?php esc_html_e('Bausteine-Galerie', 'industriesalon'); ?></h2>
        <p class="description"><?php esc_html_e('Diese Bausteine stehen im Editor unter „Muster → Industriesalon" bereit.', 'industriesalon'); ?></p>
        <table class="widefat striped" style="margin-top:1rem">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'industriesalon'); ?></th>
                    <th><?php esc_html_e('Beschreibung', 'industriesalon'); ?></th>
                    <th><?php esc_html_e('Wann benutzen?', 'industriesalon'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pattern_guide = array(
                    array('3 Info-Karten', 'Drei gleichwertige Blöcke nebeneinander.', 'Team, Angebote, Themen — wenn drei Punkte gleich wichtig sind.'),
                    array('4 Kompakt-Karten', 'Vier kleinere Karten mit Bild.', 'Partner, Kategorien, kurze Themen.'),
                    array('Text + Bild (geteilt)', 'Großes Bild links/rechts, langer Text daneben.', 'Hauptthemen und besondere Angebote hervorheben.'),
                    array('Versetztes Feature', 'Bild und Text versetzt angeordnet.', 'Dynamische Abwechslung im Seitenlayout.'),
                    array('Leitkarte + Raster', 'Eine große Karte links, vier kleine rechts.', 'Wenn ein Thema hervorgehoben, aber auch verwandte Inhalte gezeigt werden sollen.'),
                    array('50/50 Bild-Text', 'Gleich breites Bild und Text.', 'Ausgewogene, ruhige Darstellung eines Themas.'),
                    array('Archiv-Startseite', 'Filterleiste und Kartenraster.', 'Startseite für Archiv-Bereiche (z.B. Veranstaltungen, Publikationen).'),
                    array('Mission + Fakten', 'Missions-Aussage mit drei Fakten.', 'Über-uns-Bereich oder Leitbild-Seite.'),
                    array('Hero mit Hinweis', 'Großes Headerbild mit Hinweis-Box.', 'Landing-Pages mit aktuellem Hinweis oder Ticket-Link.'),
                    array('Newsletter + Förderung', 'Newsletter-Anmeldung und Förderer.', 'Am Ende einer Seite, um Kontakt und Förderpartner zu zeigen.'),
                    array('Kontakt & Anmeldung', 'Kontaktdaten für Führungen.', 'Auf Führungs- und Buchungsseiten.'),
                    array('Besuch planen', 'Öffnungszeiten und Anreise.', 'Auf der Kontakt- oder Besucherseite.'),
                    array('Raum mieten', 'Kontakt für Raumvermietung.', 'Auf der Vermietungs-Seite.'),
                );
                foreach ($pattern_guide as $row) {
                    printf(
                        '<tr><td><strong>%s</strong></td><td>%s</td><td>%s</td></tr>',
                        esc_html($row[0]),
                        esc_html($row[1]),
                        esc_html($row[2])
                    );
                }
                ?>
            </tbody>
        </table>

        <?php /* ── Plugin-Übersicht ── */ ?>
        <h2 style="margin-top:2.5rem"><?php esc_html_e('Plugin-Übersicht', 'industriesalon'); ?></h2>
        <table class="widefat" style="margin-top:1rem">
            <thead>
                <tr>
                    <th><?php esc_html_e('Plugin', 'industriesalon'); ?></th>
                    <th><?php esc_html_e('Zuständig für', 'industriesalon'); ?></th>
                    <th><?php esc_html_e('Admin-Bereich', 'industriesalon'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $plugins_info = array(
                    array('Steuerung', 'Öffnungszeiten, Kontaktdaten, Preise, FAQ, Barrierefreiheit', admin_url('admin.php?page=industriesalon-steuerung')),
                    array('Führungen', 'Touren und Führungsangebote (CPT)', admin_url('edit.php?post_type=fuehrung')),
                    array('Programm / Kalender', 'Veranstaltungskalender und Termindarstellung', admin_url('admin.php?page=iss-programm-sync')),
                    array('Hinweise (Notices)', 'Hinweis-Banner auf der Website', admin_url('edit.php?post_type=iss_notice')),
                    array('SuperSaaS API', 'Buchungssystem-Schnittstelle (automatisch)', '—'),
                );
                foreach ($plugins_info as $row) {
                    $link = $row[2] !== '—'
                        ? sprintf('<a href="%s">%s</a>', esc_url($row[2]), esc_html__('Öffnen', 'industriesalon'))
                        : '—';
                    printf(
                        '<tr><td><strong>%s</strong></td><td>%s</td><td>%s</td></tr>',
                        esc_html($row[0]),
                        esc_html($row[1]),
                        $link
                    );
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

