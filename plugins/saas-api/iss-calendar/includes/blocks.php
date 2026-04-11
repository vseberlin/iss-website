<?php

if (!defined('ABSPATH')) exit;

// Scaffolding for Gutenberg blocks. Safe to keep enabled even if block assets
// are not present yet.
add_action('init', function () {
    if (!function_exists('register_block_type')) return;

    $dir = __DIR__ . '/../blocks/tour-dates';
    if (!file_exists($dir . '/block.json')) return;

    register_block_type($dir);
});

