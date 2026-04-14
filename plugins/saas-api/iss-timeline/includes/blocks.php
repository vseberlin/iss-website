<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    if (!function_exists('register_block_type')) return;

    $dir = __DIR__ . '/../blocks/timeline';
    if (file_exists($dir . '/block.json')) {
        register_block_type($dir, [
            'render_callback' => 'iss_timeline_render',
        ]);
    }

    $dir_sections = __DIR__ . '/../blocks/timeline-sections';
    if (file_exists($dir_sections . '/block.json')) {
        register_block_type($dir_sections, [
            'render_callback' => 'iss_timeline_render_sections',
        ]);
    }
});

