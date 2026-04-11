<?php

if (!defined('ABSPATH')) exit;

// Register Gutenberg blocks (dynamic render).
add_action('init', function () {
    if (!function_exists('register_block_type')) return;

    $dir = __DIR__ . '/../blocks/tour-dates';
    if (!file_exists($dir . '/block.json')) return;

    register_block_type($dir, [
        'render_callback' => 'iss_calendar_render_dates',
    ]);
});
