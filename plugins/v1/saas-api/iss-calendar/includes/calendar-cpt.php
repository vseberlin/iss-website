<?php

if (!defined('ABSPATH')) exit;

define('ISS_CALENDAR_ITEM_POST_TYPE', 'iss_calendar_item');
define('ISS_CALENDAR_SOURCE_MAP_OPTION', 'iss_calendar_source_map');
define('ISS_CALENDAR_SOURCE_MAP_VERSION', 2);
if (!defined('ISS_CALENDAR_TAG_META_KEY')) define('ISS_CALENDAR_TAG_META_KEY', 'calendar_tag');
if (!defined('ISS_CALENDAR_SAAS_TITLE_META_KEY')) define('ISS_CALENDAR_SAAS_TITLE_META_KEY', 'calendar_saas_title');

add_action('init', function () {
    $labels = [
        'name'               => 'Kalender-Termine',
        'singular_name'      => 'Kalender-Termin',
        'add_new_item'       => 'Kalender-Termin hinzufügen',
        'edit_item'          => 'Kalender-Termin bearbeiten',
        'new_item'           => 'Neuer Kalender-Termin',
        'view_item'          => 'Kalender-Termin ansehen',
        'search_items'       => 'Kalender-Termine suchen',
        'not_found'          => 'Keine Kalender-Termine gefunden',
        'not_found_in_trash' => 'Keine Kalender-Termine im Papierkorb gefunden',
        'menu_name'          => 'Kalender-Termine',
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
        'supersaas_title' => ['type' => 'string'],
        'series_key' => ['type' => 'string'],
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
 * Stable identifier for a "recurring series" of imported items.
 *
 * This deliberately does NOT rely on TAG markers. It is title-driven
 * because that's what editors see in SuperSaaS.
 */
function iss_calendar_build_series_key($title, $item_type = '') {
    $slug = sanitize_title((string) $title);
    $item_type = sanitize_key((string) $item_type);
    if ($item_type !== '') {
        return $item_type . ':' . $slug;
    }
    return $slug;
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
        'supersaas_title' => '',
        'version' => ISS_CALENDAR_SOURCE_MAP_VERSION,
        'last_seen_at' => current_time('mysql'),
    ];

    $prev = isset($map[$tag]) && is_array($map[$tag]) ? $map[$tag] : null;
    if ($prev === $next) return;

    $map[$tag] = $next;
    update_option(ISS_CALENDAR_SOURCE_MAP_OPTION, $map, false);
}

/**
 * Upsert one source-map entry.
 *
 * Allows automatic map generation during sync even when no source post exists yet.
 *
 * @param string $tag
 * @param array<string,mixed> $data
 */
function iss_calendar_upsert_source_map_entry($tag, $data = []) {
    $tag = strtoupper(sanitize_text_field((string) $tag));
    if ($tag === '') return;

    $data = is_array($data) ? $data : [];
    $map = get_option(ISS_CALENDAR_SOURCE_MAP_OPTION, []);
    if (!is_array($map)) {
        $map = [];
    }

    $prev = isset($map[$tag]) && is_array($map[$tag]) ? $map[$tag] : [];

    $source_post_id = isset($data['source_post_id'])
        ? max(0, (int) $data['source_post_id'])
        : (int) ($prev['source_post_id'] ?? 0);
    $source_post_type = isset($data['source_post_type'])
        ? sanitize_key((string) $data['source_post_type'])
        : sanitize_key((string) ($prev['source_post_type'] ?? ''));

    $fallback_url = array_key_exists('fallback_url', $data)
        ? esc_url_raw((string) $data['fallback_url'])
        : esc_url_raw((string) ($prev['fallback_url'] ?? ''));

    $supersaas_title = isset($data['supersaas_title'])
        ? sanitize_text_field((string) $data['supersaas_title'])
        : sanitize_text_field((string) ($prev['supersaas_title'] ?? ''));

    $next = [
        'source_post_id' => $source_post_id,
        'source_post_type' => $source_post_type,
        'fallback_url' => $fallback_url,
        'supersaas_title' => $supersaas_title,
        'version' => ISS_CALENDAR_SOURCE_MAP_VERSION,
        'last_seen_at' => current_time('mysql'),
    ];

    if ($next === $prev) return;
    $map[$tag] = $next;
    update_option(ISS_CALENDAR_SOURCE_MAP_OPTION, $map, false);
}

function iss_calendar_get_source_map() {
    $map = get_option(ISS_CALENDAR_SOURCE_MAP_OPTION, []);
    if (!is_array($map)) return [];

    // Backfill missing keys for older entries.
    foreach ($map as $tag => $entry) {
        if (!is_array($entry)) continue;
        if (!array_key_exists('supersaas_title', $entry)) {
            $entry['supersaas_title'] = '';
        }
        if (!array_key_exists('version', $entry)) {
            $entry['version'] = ISS_CALENDAR_SOURCE_MAP_VERSION;
        }
        $map[$tag] = $entry;
    }

    return $map;
}

function iss_calendar_get_source_map_entry($tag) {
    $tag = strtoupper(sanitize_text_field((string) $tag));
    if ($tag === '') return null;
    $map = iss_calendar_get_source_map();
    if (!isset($map[$tag]) || !is_array($map[$tag])) return null;
    return $map[$tag];
}

function iss_calendar_get_source_post_types() {
    $types = get_post_types(['public' => true], 'names');
    if (!is_array($types)) {
        $types = [];
    }

    return array_values(array_filter($types, function ($t) {
        return $t && $t !== 'attachment' && $t !== ISS_CALENDAR_ITEM_POST_TYPE;
    }));
}

/**
 * Resolve linked source post by explicit `calendar_tag` field.
 *
 * Returns array with:
 * - post_id (int)
 * - post_type (string)
 * - ambiguous (bool)
 */
function iss_calendar_resolve_source_by_tag($tag) {
    $tag = strtoupper(sanitize_text_field((string) $tag));
    if ($tag === '') {
        return ['post_id' => 0, 'post_type' => '', 'ambiguous' => false];
    }

    $q = new WP_Query([
        'post_type' => iss_calendar_get_source_post_types(),
        'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => 2,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_query' => [
            [
                'key' => ISS_CALENDAR_TAG_META_KEY,
                'value' => $tag,
                'compare' => '=',
            ],
        ],
    ]);

    $ids = array_map('intval', (array) $q->posts);
    if (count($ids) === 1) {
        $pid = (int) $ids[0];
        return [
            'post_id' => $pid,
            'post_type' => (string) get_post_type($pid),
            'ambiguous' => false,
        ];
    }

    return ['post_id' => 0, 'post_type' => '', 'ambiguous' => count($ids) > 1];
}

/**
 * Resolve linked source post by explicit `calendar_saas_title` field.
 */
function iss_calendar_resolve_source_by_saas_title($title) {
    $title = sanitize_text_field((string) $title);
    if ($title === '') {
        return ['post_id' => 0, 'post_type' => '', 'ambiguous' => false];
    }

    $q = new WP_Query([
        'post_type' => iss_calendar_get_source_post_types(),
        'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => 2,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_query' => [
            [
                'key' => ISS_CALENDAR_SAAS_TITLE_META_KEY,
                'value' => $title,
                'compare' => '=',
            ],
        ],
    ]);

    $ids = array_map('intval', (array) $q->posts);
    if (count($ids) === 1) {
        $pid = (int) $ids[0];
        return [
            'post_id' => $pid,
            'post_type' => (string) get_post_type($pid),
            'ambiguous' => false,
        ];
    }

    return ['post_id' => 0, 'post_type' => '', 'ambiguous' => count($ids) > 1];
}

/**
 * Best-effort: resolve a calendar tag for a given source post id.
 *
 * @param int $source_post_id
 * @return string
 */
function iss_calendar_resolve_tag_for_source_post_id($source_post_id) {
    $source_post_id = (int) $source_post_id;
    if ($source_post_id <= 0) return '';

    $map = iss_calendar_get_source_map();
    foreach ($map as $tag => $entry) {
        if (!is_array($entry)) continue;
        if ((int) ($entry['source_post_id'] ?? 0) === $source_post_id) {
            return strtoupper(sanitize_text_field((string) $tag));
        }
    }

    return '';
}

add_filter('manage_' . ISS_CALENDAR_ITEM_POST_TYPE . '_posts_columns', function ($cols) {
    $out = [];
    foreach ($cols as $key => $label) {
        $out[$key] = $label;
        if ($key === 'title') {
            $out['event_start'] = 'Beginn';
            $out['availability_state'] = 'Status';
            $out['source_post_id'] = 'Verknüpfter Inhalt';
            $out['sync_status'] = 'Abgleich';
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
