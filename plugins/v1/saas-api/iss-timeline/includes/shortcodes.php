<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    if (!function_exists('add_shortcode')) return;

    add_shortcode('iss_timeline', function ($atts = []) {
        $atts = is_array($atts) ? $atts : [];
        $attrs = shortcode_atts([
            'title' => '',
            'intro' => '',
            'limit' => 50,
            'group' => '',
            'yearGrouping' => '1',
        ], $atts, 'iss_timeline');

        return function_exists('iss_timeline_render')
            ? iss_timeline_render([
                'title' => (string) $attrs['title'],
                'intro' => (string) $attrs['intro'],
                'limit' => (int) $attrs['limit'],
                'group' => (string) $attrs['group'],
                'yearGrouping' => (string) $attrs['yearGrouping'] !== '0',
            ], '')
            : '';
    });

    add_shortcode('iss_timeline_sections', function ($atts = []) {
        $atts = is_array($atts) ? $atts : [];
        $attrs = shortcode_atts([
            'title' => '',
            'intro' => '',
            'group' => '',
            'nextTitle' => 'Was kommt als Nächstes',
            'nextLimit' => 4,
            'monthlyTitle' => 'Monatsübersicht',
            'monthlyLimit' => 80,
            'archiveTitle' => 'Archiv',
            'archiveLimit' => 250,
        ], $atts, 'iss_timeline_sections');

        return function_exists('iss_timeline_render_sections')
            ? iss_timeline_render_sections([
                'title' => (string) $attrs['title'],
                'intro' => (string) $attrs['intro'],
                'group' => (string) $attrs['group'],
                'nextTitle' => (string) $attrs['nextTitle'],
                'nextLimit' => (int) $attrs['nextLimit'],
                'monthlyTitle' => (string) $attrs['monthlyTitle'],
                'monthlyLimit' => (int) $attrs['monthlyLimit'],
                'archiveTitle' => (string) $attrs['archiveTitle'],
                'archiveLimit' => (int) $attrs['archiveLimit'],
            ], '')
            : '';
    });
});

