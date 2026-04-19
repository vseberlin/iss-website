<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('editor-styles');
    add_editor_style('style.css');
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
            'name' => 'industriesalon/tours-1-9-grid',
            'title' => 'ISS Tours 1-9 Grid',
            'description' => 'Lead tour card with 3x3 grid for nine additional tours.',
            'categories' => array('industriesalon', 'text', 'media'),
            'file' => '/patterns/iss-tours-1-9-grid.html',
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
    $theme = wp_get_theme();
    $version = $theme->get('Version');
    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();

    // 1. Base / style.css (contains global variables)
    wp_enqueue_style(
        'industriesalon-base',
        get_stylesheet_uri(),
        array(),
        $version
    );

    // 2. Header styles
    $header_rel = '/assets/css/header.css';
    $header_abs = $theme_dir . $header_rel;
    if (file_exists($header_abs)) {
        wp_enqueue_style(
            'industriesalon-header',
            $theme_uri . $header_rel,
            array('industriesalon-base'),
            (string) filemtime($header_abs)
        );
    }

    // 3. Front page styles (conditional)
    $front_page_rel = '/assets/css/front-page.css';
    $front_page_abs = $theme_dir . $front_page_rel;
    if ((is_front_page() || is_home() || is_page_template('templates/front-page.html')) && file_exists($front_page_abs)) {
        wp_enqueue_style(
            'industriesalon-front-page',
            $theme_uri . $front_page_rel,
            array('industriesalon-base'),
            (string) filemtime($front_page_abs)
        );
    }

    // 4. Timeline theme tokens/classes (plugin keeps structural timeline layout)
    $timeline_theme_rel = '/assets/css/timeline-theme.css';
    $timeline_theme_abs = $theme_dir . $timeline_theme_rel;
    if (file_exists($timeline_theme_abs)) {
        wp_enqueue_style(
            'industriesalon-timeline-theme',
            $theme_uri . $timeline_theme_rel,
            array('industriesalon-base'),
            (string) filemtime($timeline_theme_abs)
        );
    }

    // 5. Visit info theme tokens/classes (plugin keeps structural visit info layout)
    $visit_info_rel = '/assets/css/visit-info.css';
    $visit_info_abs = $theme_dir . $visit_info_rel;
    if (file_exists($visit_info_abs)) {
        wp_enqueue_style(
            'industriesalon-visit-info',
            $theme_uri . $visit_info_rel,
            array('industriesalon-base'),
            (string) filemtime($visit_info_abs)
        );
    }

    // 6. Tour styles
    $tour_rel = '/assets/css/tour.css';
    $tour_abs = $theme_dir . $tour_rel;
    if (file_exists($tour_abs)) {
        wp_enqueue_style(
            'industriesalon-tour',
            $theme_uri . $tour_rel,
            array('industriesalon-base'),
            (string) filemtime($tour_abs)
        );
    }

    // 7. Tours page styles
    $tours_page_rel = '/assets/css/tours-page.css';
    $tours_page_abs = $theme_dir . $tours_page_rel;
    if (file_exists($tours_page_abs)) {
        wp_enqueue_style(
            'industriesalon-tours-page',
            $theme_uri . $tours_page_rel,
            array('industriesalon-base'),
            (string) filemtime($tours_page_abs)
        );
    }

    // 8. Info panel styles
    $info_panel_rel = '/assets/css/info-panel.css';
    $info_panel_abs = $theme_dir . $info_panel_rel;
    if (file_exists($info_panel_abs)) {
        wp_enqueue_style(
            'industriesalon-info-panel',
            $theme_uri . $info_panel_rel,
            array('industriesalon-base'),
            (string) filemtime($info_panel_abs)
        );
    }

    // 9. Feature split styles
    $feature_split_rel = '/assets/css/feature-split.css';
    $feature_split_abs = $theme_dir . $feature_split_rel;
    if (file_exists($feature_split_abs)) {
        wp_enqueue_style(
            'industriesalon-feature-split',
            $theme_uri . $feature_split_rel,
            array('industriesalon-base'),
            (string) filemtime($feature_split_abs)
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
