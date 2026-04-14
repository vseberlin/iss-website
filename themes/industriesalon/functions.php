<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
});

function industriesalon_enqueue_header_assets(): void
{
    $theme = wp_get_theme();
    $version = $theme->get('Version');
    $style_rel_path = '/style.css';
    $style_abs_path = get_stylesheet_directory() . $style_rel_path;
    $style_uri = get_stylesheet_directory_uri() . $style_rel_path;
    $script_rel_path = '/assets/js/header.js';
    $script_abs_path = get_stylesheet_directory() . $script_rel_path;
    $script_uri = get_stylesheet_directory_uri() . $script_rel_path;

    if (file_exists($style_abs_path)) {
        wp_enqueue_style(
            'industriesalon-theme',
            $style_uri,
            array(),
            (string) filemtime($style_abs_path) ?: $version
        );
    }

    if (!file_exists($script_abs_path)) {
        return;
    }

    wp_enqueue_script(
        'industriesalon-header',
        $script_uri,
        array(),
        (string) filemtime($script_abs_path) ?: $version,
        true
    );
}
add_action('wp_enqueue_scripts', 'industriesalon_enqueue_header_assets');
