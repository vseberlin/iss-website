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
