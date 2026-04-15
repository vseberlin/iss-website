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

    // 1. Base / style.css (contains global variables)
    wp_enqueue_style(
        'industriesalon-base',
        get_stylesheet_uri(),
        array(),
        $version
    );

    // 2. Header Styles
    if (file_exists(get_stylesheet_directory() . '/header.css')) {
        wp_enqueue_style(
            'industriesalon-header',
            get_stylesheet_directory_uri() . '/header.css',
            array('industriesalon-base'),
            $version
        );
    }

    // 3. Front Page Styles (conditional)
    if ((is_front_page() || is_home() || is_page_template('templates/front-page.html')) && file_exists(get_stylesheet_directory() . '/front-page.css')) {
        wp_enqueue_style(
            'industriesalon-front-page',
            get_stylesheet_directory_uri() . '/front-page.css',
            array('industriesalon-base'),
            $version
        );
    }

    // 4. Timeline Styles
    if (file_exists(get_stylesheet_directory() . '/assets/timeline.css')) {
        wp_enqueue_style(
            'industriesalon-timeline',
            get_stylesheet_directory_uri() . '/assets/timeline.css',
            array('industriesalon-base'),
            $version
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
