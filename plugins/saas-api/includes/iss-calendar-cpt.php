<?php

if (!defined('ABSPATH')) exit;

define('ISS_CALENDAR_ITEM_POST_TYPE', 'iss_calendar_item');
define('ISS_CALENDAR_SOURCE_MAP_OPTION', 'iss_calendar_source_map');

add_action('init', function () {
    $labels = [
        'name'               => 'Calendar Items',
        'singular_name'      => 'Calendar Item',
        'add_new_item'       => 'Add Calendar Item',
        'edit_item'          => 'Edit Calendar Item',
        'new_item'           => 'New Calendar Item',
        'view_item'          => 'View Calendar Item',
        'search_items'       => 'Search Calendar Items',
        'not_found'          => 'No calendar items found',
        'not_found_in_trash' => 'No calendar items found in Trash',
        'menu_name'          => 'Calendar Items',
    ];

    register_post_type(ISS_CALENDAR_ITEM_POST_TYPE, [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'excerpt'],
        'menu_icon' => 'dashicons-calendar-alt',
        'has_archive' => false,
        'rewrite' => false,
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);

    iss_calendar_register_item_meta();
});

function iss_calendar_register_item_meta() {
    $fields = [
        'event_start' => ['type' => 'string'],
        'event_end' => ['type' => 'string'],
        'item_type' => ['type' => 'string'],
        'source_system' => ['type' => 'string'],
        'source_calendar' => ['type' => 'string'],
        'external_id' => ['type' => 'string'],
        'source_post_id' => ['type' => 'integer'],
        'source_post_type' => ['type' => 'string'],
        'booking_url' => ['type' => 'string'],
        'location' => ['type' => 'string'],
        'availability_state' => ['type' => 'string'],
        'capacity_total' => ['type' => 'integer'],
        'capacity_available' => ['type' => 'integer'],
        'is_public' => ['type' => 'integer'],
        'sync_status' => ['type' => 'string'],
        'last_synced_at' => ['type' => 'string'],
        'last_seen_at' => ['type' => 'string'],
        'origin_mode' => ['type' => 'string'],
        'public_note' => ['type' => 'string'],
        'sort_date' => ['type' => 'string'],
    ];

    foreach ($fields as $key => $cfg) {
        register_post_meta(ISS_CALENDAR_ITEM_POST_TYPE, $key, [
            'type' => $cfg['type'],
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'iss_calendar_sanitize_meta_value',
            'auth_callback' => static function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
}

function iss_calendar_sanitize_meta_value($value, $meta_key, $meta_type) {
    if (is_array($value) || is_object($value)) {
        return null;
    }

    if (in_array($meta_key, ['source_post_id', 'capacity_total', 'capacity_available', 'is_public'], true)) {
        return (int) $value;
    }

    return sanitize_text_field((string) $value);
}

/**
 * Save (tag => source page) mapping for later sync.
 *
 * This is intentionally automatic to avoid editor involvement:
 * the act of rendering a page containing the calendar is enough
 * to remember which WP page corresponds to which SuperSaaS tag.
 */
function iss_calendar_remember_source_mapping($tag, $fallback_url, $source_post_id, $source_post_type) {
    $tag = strtoupper(sanitize_text_field((string) $tag));
    if ($tag === '') return;

    $source_post_id = $source_post_id ? (int) $source_post_id : 0;
    $source_post_type = sanitize_key((string) $source_post_type);
    $fallback_url = esc_url_raw((string) $fallback_url);

    $map = get_option(ISS_CALENDAR_SOURCE_MAP_OPTION, []);
    if (!is_array($map)) {
        $map = [];
    }

    $next = [
        'source_post_id' => $source_post_id,
        'source_post_type' => $source_post_type,
        'fallback_url' => $fallback_url,
        'last_seen_at' => current_time('mysql'),
    ];

    $prev = isset($map[$tag]) && is_array($map[$tag]) ? $map[$tag] : null;
    if ($prev === $next) return;

    $map[$tag] = $next;
    update_option(ISS_CALENDAR_SOURCE_MAP_OPTION, $map, false);
}

function iss_calendar_get_source_map() {
    $map = get_option(ISS_CALENDAR_SOURCE_MAP_OPTION, []);
    return is_array($map) ? $map : [];
}

function iss_calendar_get_source_map_entry($tag) {
    $tag = strtoupper(sanitize_text_field((string) $tag));
    if ($tag === '') return null;
    $map = iss_calendar_get_source_map();
    if (!isset($map[$tag]) || !is_array($map[$tag])) return null;
    return $map[$tag];
}

add_filter('manage_' . ISS_CALENDAR_ITEM_POST_TYPE . '_posts_columns', function ($cols) {
    $out = [];
    foreach ($cols as $key => $label) {
        $out[$key] = $label;
        if ($key === 'title') {
            $out['event_start'] = 'Start';
            $out['availability_state'] = 'Status';
            $out['source_post_id'] = 'Source';
            $out['sync_status'] = 'Sync';
        }
    }
    return $out;
});

add_action('manage_' . ISS_CALENDAR_ITEM_POST_TYPE . '_posts_custom_column', function ($col, $post_id) {
    if ($col === 'event_start') {
        echo esc_html((string) get_post_meta($post_id, 'event_start', true));
        return;
    }
    if ($col === 'availability_state') {
        echo esc_html((string) get_post_meta($post_id, 'availability_state', true));
        return;
    }
    if ($col === 'source_post_id') {
        $id = (int) get_post_meta($post_id, 'source_post_id', true);
        if ($id > 0) {
            $title = get_the_title($id);
            $link = get_edit_post_link($id);
            if ($link) {
                printf('<a href="%s">#%d %s</a>', esc_url($link), $id, esc_html($title));
                return;
            }
            echo esc_html('#' . $id . ' ' . $title);
            return;
        }
        echo '—';
        return;
    }
    if ($col === 'sync_status') {
        echo esc_html((string) get_post_meta($post_id, 'sync_status', true));
        return;
    }
}, 10, 2);
