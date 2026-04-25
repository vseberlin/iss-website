<?php
if (!defined('ABSPATH')) {
    exit;
}

$industriesalon_fuehrungen_filters_helper = get_stylesheet_directory() . '/assets/css/staging/industriesalon-fuehrungen-filters.php';
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
            'title' => 'Info Panel – Anmeldung',
            'description' => 'Kontakt- und Anmeldeblock für Führungen',
            'categories' => array('industriesalon', 'text'),
            'file' => '/patterns/pattern-info-panel-anmeldung.html',
        ),
        array(
            'name' => 'industriesalon/info-panel-besuch',
            'title' => 'Info Panel – Besuch planen',
            'description' => 'Öffnungszeiten und Besuchsinformationen',
            'categories' => array('industriesalon', 'text'),
            'file' => '/patterns/pattern-info-panel-besuch.html',
        ),
        array(
            'name' => 'industriesalon/info-panel-vermietung',
            'title' => 'Info Panel – Vermietung',
            'description' => 'Kontakt für Raumvermietung und Anfragen',
            'categories' => array('industriesalon', 'text'),
            'file' => '/patterns/pattern-info-panel-vermietung.html',
        ),
        array(
            'name' => 'industriesalon/feature-split',
            'title' => 'ISS Feature Split',
            'description' => 'Linear feature section with text left and image right. Used to break card grids.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-section-feature-split.html',
        ),
        array(
            'name' => 'industriesalon/1to4-grid',
            'title' => 'ISS 1to4 Grid',
            'description' => 'Lead card with a compact query grid.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-1to4-grid.html',
        ),
        array(
            'name' => 'industriesalon/50-50-media-text',
            'title' => 'ISS 50/50 Media Text',
            'description' => 'Two-column section with text on one side and image on the other.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-50-50-media-text.html',
        ),
        array(
            'name' => 'industriesalon/asymmetric-feature',
            'title' => 'ISS Asymmetric Feature',
            'description' => 'Asymmetric content and image section with offset visual rhythm.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-asymmetric-feature.html',
        ),
        array(
            'name' => 'industriesalon/4-card-row',
            'title' => 'ISS 4 Card Row',
            'description' => 'Section heading above a row of four compact cards with image and title.',
            'categories' => array('industriesalon', 'media', 'cards'),
            'file' => '/patterns/iss-4-card-row.html',
        ),
        array(
            'name' => 'industriesalon/3-card-row',
            'title' => 'ISS 3 Card Row',
            'description' => 'Section heading above a row of three compact info cards.',
            'categories' => array('industriesalon', 'media', 'cards'),
            'file' => '/patterns/iss-3-card-row.html',
        ),
        array(
            'name' => 'industriesalon/newsletter-funders',
            'title' => 'ISS Newsletter + Förderer',
            'description' => 'Newsletter signup section with sponsor/funder panel.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-newsletter-funders.html',
        ),
        array(
            'name' => 'industriesalon/archive-landing',
            'title' => 'ISS Archive Landing',
            'description' => 'Archive and media landing page layout.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/archive-landing.html',
        ),
        array(
            'name' => 'industriesalon/mission-support-strip',
            'title' => 'ISS Mission Support Strip',
            'description' => 'Mission statement with three supporting fact modules.',
            'categories' => array('industriesalon', 'text'),
            'file' => '/patterns/iss-section-mission-support-strip.html',
        ),
        array(
            'name' => 'industriesalon/landing-hero-with-note',
            'title' => 'ISS Landing Hero + Note',
            'description' => 'Full-width landing hero with right-side note banner (iss-hero-note).',
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
