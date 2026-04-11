<?php

if (!defined('ABSPATH')) exit;

define('ISS_CALENDAR_SYNC_HOOK', 'iss_calendar_cron_sync');
define('ISS_CALENDAR_LAST_SYNC_OPTION', 'iss_calendar_last_sync_at');

add_action('admin_menu', function () {
    add_management_page(
        'SaaS Calendar Sync',
        'SaaS Calendar Sync',
        'manage_options',
        'iss-calendar-sync',
        'iss_calendar_render_sync_page'
    );
});

add_action('admin_post_iss_calendar_sync', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed.');
    }

    check_admin_referer('iss_calendar_sync');

    $result = iss_calendar_sync_supersaas_to_cpt();
    set_transient('iss_calendar_sync_result', $result, 60);

    wp_safe_redirect(admin_url('tools.php?page=iss-calendar-sync'));
    exit;
});

add_action(ISS_CALENDAR_SYNC_HOOK, function () {
    iss_calendar_sync_supersaas_to_cpt();
});

function iss_calendar_activate_sync() {
    if (!wp_next_scheduled(ISS_CALENDAR_SYNC_HOOK)) {
        wp_schedule_event(time() + 60, 'hourly', ISS_CALENDAR_SYNC_HOOK);
    }
}

function iss_calendar_deactivate_sync() {
    $ts = wp_next_scheduled(ISS_CALENDAR_SYNC_HOOK);
    if ($ts) {
        wp_unschedule_event($ts, ISS_CALENDAR_SYNC_HOOK);
    }
}

function iss_calendar_render_sync_page() {
    if (!current_user_can('manage_options')) return;

    $result = get_transient('iss_calendar_sync_result');
    if ($result !== false) {
        delete_transient('iss_calendar_sync_result');
    }

    $map = function_exists('iss_calendar_get_source_map') ? iss_calendar_get_source_map() : [];

    echo '<div class="wrap">';
    echo '<h1>SaaS Calendar Sync</h1>';

    if (is_array($result)) {
        $created = (int) ($result['created'] ?? 0);
        $updated = (int) ($result['updated'] ?? 0);
        $errors = (int) ($result['errors'] ?? 0);
        printf(
            '<div class="notice notice-success"><p>Sync done. Created: %d, Updated: %d, Errors: %d.</p></div>',
            $created,
            $updated,
            $errors
        );
    }

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="iss_calendar_sync" />';
    wp_nonce_field('iss_calendar_sync');
    submit_button('Sync now');
    echo '</form>';

    echo '<h2>Source map</h2>';
    if (empty($map)) {
        echo '<p>No tags mapped yet. Render pages containing the calendar shortcode to populate the map automatically.</p>';
    } else {
        echo '<table class="widefat striped"><thead><tr><th>Tag</th><th>Source Post</th><th>Fallback URL</th><th>Last Seen</th></tr></thead><tbody>';
        foreach ($map as $tag => $entry) {
            if (!is_array($entry)) continue;
            $post_id = isset($entry['source_post_id']) ? (int) $entry['source_post_id'] : 0;
            $post_type = isset($entry['source_post_type']) ? (string) $entry['source_post_type'] : '';
            $fallback_url = isset($entry['fallback_url']) ? (string) $entry['fallback_url'] : '';
            $last_seen_at = isset($entry['last_seen_at']) ? (string) $entry['last_seen_at'] : '';

            $post_label = $post_id ? ('#' . $post_id . ' ' . get_the_title($post_id)) : '—';
            $post_edit = $post_id ? get_edit_post_link($post_id) : '';
            if ($post_edit) {
                $post_label = sprintf('<a href="%s">%s</a>', esc_url($post_edit), esc_html($post_label));
            } else {
                $post_label = esc_html($post_label);
            }

            printf(
                '<tr><td>%s</td><td>%s<br><code>%s</code></td><td>%s</td><td>%s</td></tr>',
                esc_html((string) $tag),
                $post_label,
                esc_html($post_type),
                $fallback_url ? ('<a href="' . esc_url($fallback_url) . '" target="_blank" rel="noopener">link</a>') : '—',
                esc_html($last_seen_at)
            );
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}

/**
 * Fetch raw "free slots" from SuperSaaS.
 *
 * @return array|WP_Error
 */
function iss_calendar_supersaas_fetch_free_slots($settings = null) {
    if ($settings === null) {
        $settings = function_exists('is_saas_get_settings') ? is_saas_get_settings() : [];
    }

    if (!is_array($settings)) {
        return new WP_Error('iss_calendar_settings', 'Invalid settings.');
    }

    $schedule_id = isset($settings['schedule_id']) ? (string) $settings['schedule_id'] : '';
    $api_key = isset($settings['api_key']) ? (string) $settings['api_key'] : '';
    $account_name = isset($settings['account_name']) ? (string) $settings['account_name'] : '';
    $base_url = isset($settings['base_url']) ? (string) $settings['base_url'] : '';

    if ($schedule_id === '' || $api_key === '' || $account_name === '' || $base_url === '') {
        return new WP_Error('iss_calendar_config', 'Missing SuperSaaS configuration.');
    }

    $cache_key = 'iss_calendar_free_' . md5($base_url . '|' . $account_name . '|' . $schedule_id);
    $cached = get_transient($cache_key);
    if (is_array($cached)) {
        return $cached;
    }

    $base_url = untrailingslashit($base_url);
    $from = rawurlencode(current_time('Y-m-d H:i:s'));
    $url = $base_url . '/api/free/' . rawurlencode($schedule_id) . '.json?from=' . $from;

    $response = wp_remote_get($url, [
        'timeout' => 20,
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($account_name . ':' . $api_key),
        ],
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('iss_calendar_fetch', $response->get_error_message());
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        return new WP_Error('iss_calendar_upstream', 'Upstream request failed with status ' . $code . '.');
    }

    $body = (string) wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $slot_items = isset($data['slots']) && is_array($data['slots']) ? $data['slots'] : $data;

    if (!is_array($slot_items)) {
        return new WP_Error('iss_calendar_parse', 'Invalid API response.');
    }

    set_transient($cache_key, $slot_items, 60 * 5);
    return $slot_items;
}

/**
 * Parse a SuperSaaS slot title like: "[TAG] Public Title".
 *
 * @return array{tag:string,title:string,raw_title:string}
 */
function iss_calendar_parse_supersaas_title($raw_title) {
    $raw_title = trim((string) $raw_title);
    $tag = '';
    $title = $raw_title;

    if (preg_match('/^\\s*\\[([^\\]]+)\\]\\s*(.*)$/u', $raw_title, $m)) {
        $tag = strtoupper(trim((string) $m[1]));
        $title = trim((string) $m[2]);
        if ($title === '') {
            $title = $raw_title;
        }
    }

    return [
        'tag' => $tag,
        'title' => $title,
        'raw_title' => $raw_title,
    ];
}

/**
 * Normalize SuperSaaS free slots for a specific tag to the REST "slots" shape.
 *
 * @param string $tag
 * @param array|null $settings
 * @return array<int,array<string,mixed>>|WP_Error
 */
function iss_calendar_get_supersaas_slots_for_tag($tag, $settings = null) {
    $tag = strtoupper(sanitize_text_field((string) $tag));
    if ($tag === '') {
        return [];
    }

    $slot_items = iss_calendar_supersaas_fetch_free_slots($settings);
    if (is_wp_error($slot_items)) {
        return $slot_items;
    }

    $slots = [];

    foreach ($slot_items as $slot) {
        if (!is_array($slot)) {
            continue;
        }

        $raw_title = isset($slot['title']) ? trim((string) $slot['title']) : '';
        if ($raw_title === '') {
            continue;
        }

        $parsed = iss_calendar_parse_supersaas_title($raw_title);
        if (empty($parsed['tag']) || strtoupper((string) $parsed['tag']) !== $tag) {
            continue;
        }

        $start = $slot['start'] ?? null;
        if (!$start) {
            continue;
        }

        if (function_exists('is_saas_build_slot_response')) {
            $built = is_saas_build_slot_response($slot, (string) ($parsed['title'] ?? ''), $start);
            if (is_array($built)) {
                $built['id'] = isset($built['id']) ? (string) $built['id'] : '';
                $built['booking_url'] = null;
                $slots[] = $built;
            }
            continue;
        }

        // Fallback normalizer if plugin function isn't available for some reason.
        $available = null;
        if (isset($slot['available'])) {
            $available = (int) $slot['available'];
        } elseif (isset($slot['remaining'])) {
            $available = (int) $slot['remaining'];
        } elseif (isset($slot['count'])) {
            $available = (int) $slot['count'];
        }

        $slots[] = [
            'id' => isset($slot['id']) ? (string) $slot['id'] : '',
            'title' => (string) ($parsed['title'] ?? $raw_title),
            'start' => $start,
            'end' => $slot['end'] ?? ($slot['finish'] ?? null),
            'capacity' => isset($slot['capacity']) ? (int) $slot['capacity'] : null,
            'available' => $available,
            'booking_url' => null,
        ];
    }

    usort($slots, function ($a, $b) {
        return strcmp((string) ($a['start'] ?? ''), (string) ($b['start'] ?? ''));
    });

    return $slots;
}

/**
 * Get normalized slots with automatic CPT fallback when SaaS is unavailable.
 *
 * @param string $tag
 * @param array|null $settings
 * @return array{slots:array<int,array<string,mixed>>,source:string,error:WP_Error|null}
 */
function iss_calendar_get_slots_with_fallback($tag, $settings = null) {
    $tag = strtoupper(sanitize_text_field((string) $tag));
    if ($tag === '') {
        return ['slots' => [], 'source' => 'empty', 'error' => null];
    }

    $slots = iss_calendar_get_supersaas_slots_for_tag($tag, $settings);
    if (!is_wp_error($slots) && !empty($slots)) {
        return ['slots' => $slots, 'source' => 'saas', 'error' => null];
    }

    $fallback = iss_calendar_get_slots_fallback_for_tag($tag);
    if (!empty($fallback)) {
        return [
            'slots' => $fallback,
            'source' => 'cpt',
            'error' => is_wp_error($slots) ? $slots : null,
        ];
    }

    return [
        'slots' => [],
        'source' => is_wp_error($slots) ? 'error' : 'empty',
        'error' => is_wp_error($slots) ? $slots : null,
    ];
}

/**
 * REST fallback: serve future slots from CPT when SuperSaaS is down.
 *
 * @return array<int,array<string,mixed>>
 */
function iss_calendar_get_slots_fallback_for_tag($tag) {
    $entry = function_exists('iss_calendar_get_source_map_entry') ? iss_calendar_get_source_map_entry($tag) : null;
    if (!is_array($entry)) return [];

    $source_post_id = isset($entry['source_post_id']) ? (int) $entry['source_post_id'] : 0;
    if ($source_post_id <= 0) return [];

    $now = current_time('Y-m-d H:i:s');

    $q = new WP_Query([
        'post_type' => ISS_CALENDAR_ITEM_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => 250,
        'orderby' => 'meta_value',
        'meta_type' => 'DATETIME',
        'order' => 'ASC',
        'meta_key' => 'event_start',
        'meta_query' => [
            [
                'key' => 'source_post_id',
                'value' => $source_post_id,
                'compare' => '=',
                'type' => 'NUMERIC',
            ],
            [
                'key' => 'event_start',
                'value' => $now,
                'compare' => '>=',
                'type' => 'DATETIME',
            ],
            [
                'key' => 'is_public',
                'value' => 1,
                'compare' => '=',
                'type' => 'NUMERIC',
            ],
        ],
    ]);

    $out = [];
    foreach ($q->posts as $post) {
        $post_id = (int) $post->ID;

        $external_id = (string) get_post_meta($post_id, 'external_id', true);
        $start = (string) get_post_meta($post_id, 'event_start', true);
        if ($external_id === '' || $start === '') continue;

        $cap_raw = get_post_meta($post_id, 'capacity_total', true);
        $avail_raw = get_post_meta($post_id, 'capacity_available', true);

        $capacity = ($cap_raw === '' || $cap_raw === null) ? null : (int) $cap_raw;
        $available = ($avail_raw === '' || $avail_raw === null) ? null : (int) $avail_raw;
        $booking_url = get_post_meta($post_id, 'booking_url', true);
        $booking_url = $booking_url ? (string) $booking_url : null;

        $end = (string) get_post_meta($post_id, 'event_end', true);
        $end = $end !== '' ? $end : null;

        $out[] = [
            'id' => (string) $external_id,
            'title' => get_the_title($post_id),
            'start' => $start,
            'end' => $end,
            'capacity' => $capacity,
            'available' => $available,
            'booking_url' => $booking_url,
        ];
    }

    return $out;
}

/**
 * Sync SuperSaaS slots into the local CPT for fallback and internal use.
 *
 * @return array{created:int,updated:int,errors:int}
 */
function iss_calendar_sync_supersaas_to_cpt() {
    $settings = function_exists('is_saas_get_settings') ? is_saas_get_settings() : [];
    $slot_items = iss_calendar_supersaas_fetch_free_slots($settings);
    if (is_wp_error($slot_items)) {
        return ['created' => 0, 'updated' => 0, 'errors' => 1];
    }

    $source_calendar = function_exists('is_saas_get_schedule_path')
        ? (is_saas_get_schedule_path($settings) ?: (string) ($settings['schedule_id'] ?? ''))
        : (string) ($settings['schedule_id'] ?? '');
    $source_calendar = sanitize_text_field((string) $source_calendar);

    $schedule_url = iss_calendar_build_schedule_url($settings);
    $map = function_exists('iss_calendar_get_source_map') ? iss_calendar_get_source_map() : [];
    $mapped_tags = array_keys($map);
    $mapped_tags = array_filter(array_map(function ($t) { return strtoupper(sanitize_text_field((string) $t)); }, $mapped_tags));

    $now = current_time('mysql');
    $created = 0;
    $updated = 0;
    $errors = 0;
    $slots_by_tag = [];

    foreach ($slot_items as $slot) {
        if (!is_array($slot)) continue;

        $external_id = isset($slot['id']) ? trim((string) $slot['id']) : '';
        if ($external_id === '') continue;

        $raw_title = isset($slot['title']) ? (string) $slot['title'] : '';
        $parsed = iss_calendar_parse_supersaas_title($raw_title);
        $tag = isset($parsed['tag']) ? (string) $parsed['tag'] : '';
        $clean_title = isset($parsed['title']) ? (string) $parsed['title'] : '';

        if ($tag === '' || !in_array($tag, $mapped_tags, true)) {
            continue;
        }

        $start = isset($slot['start']) ? trim((string) $slot['start']) : '';
        if ($start === '') continue;

        $end = isset($slot['end']) ? trim((string) $slot['end']) : (isset($slot['finish']) ? trim((string) $slot['finish']) : '');
        if ($end === '') {
            $end = null;
        }

        $capacity_total = isset($slot['capacity']) ? (int) $slot['capacity'] : null;
        $available = null;
        if (isset($slot['available'])) {
            $available = (int) $slot['available'];
        } elseif (isset($slot['remaining'])) {
            $available = (int) $slot['remaining'];
        } elseif (isset($slot['count'])) {
            $available = (int) $slot['count'];
        }

        $availability_state = 'inquiry';
        if ($available !== null) {
            $availability_state = $available > 0 ? 'available' : 'sold_out';
        }

        $map_entry = isset($map[$tag]) && is_array($map[$tag]) ? $map[$tag] : [];
        $source_post_id = isset($map_entry['source_post_id']) ? (int) $map_entry['source_post_id'] : 0;
        $source_post_type = isset($map_entry['source_post_type']) ? sanitize_key((string) $map_entry['source_post_type']) : '';
        $fallback_url = isset($map_entry['fallback_url']) ? esc_url_raw((string) $map_entry['fallback_url']) : '';

        $booking_url = $fallback_url ?: $schedule_url;

        $post_id = iss_calendar_find_item_post_id($external_id, $source_calendar);
        $postarr = [
            'post_type' => ISS_CALENDAR_ITEM_POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $clean_title !== '' ? wp_strip_all_tags($clean_title) : 'Calendar Item',
        ];

        if ($post_id) {
            $postarr['ID'] = $post_id;
            $res = wp_update_post($postarr, true);
            if (is_wp_error($res)) {
                $errors++;
                continue;
            }
            $updated++;
        } else {
            $res = wp_insert_post($postarr, true);
            if (is_wp_error($res) || !$res) {
                $errors++;
                continue;
            }
            $post_id = (int) $res;
            $created++;
        }

        update_post_meta($post_id, 'event_start', $start);
        update_post_meta($post_id, 'event_end', $end);
        update_post_meta($post_id, 'item_type', 'tour');
        update_post_meta($post_id, 'source_system', 'supersaas');
        update_post_meta($post_id, 'source_calendar', $source_calendar);
        update_post_meta($post_id, 'external_id', $external_id);
        update_post_meta($post_id, 'source_post_id', $source_post_id);
        update_post_meta($post_id, 'source_post_type', $source_post_type);
        update_post_meta($post_id, 'booking_url', $booking_url);
        update_post_meta($post_id, 'availability_state', $availability_state);
        if ($capacity_total !== null) update_post_meta($post_id, 'capacity_total', (int) $capacity_total);
        if ($available !== null) update_post_meta($post_id, 'capacity_available', (int) $available);
        update_post_meta($post_id, 'is_public', 1);
        update_post_meta($post_id, 'sync_status', 'ok');
        update_post_meta($post_id, 'last_synced_at', $now);
        update_post_meta($post_id, 'last_seen_at', $now);
        update_post_meta($post_id, 'origin_mode', 'supersaas');
        update_post_meta($post_id, 'sort_date', $start);

        // Prime the REST/tag cache from the same normalized shape as the REST endpoint.
        if (!isset($slots_by_tag[$tag])) {
            $slots_by_tag[$tag] = [];
        }
        $slots_by_tag[$tag][] = [
            'id' => (string) $external_id,
            'title' => $clean_title !== '' ? $clean_title : $raw_title,
            'start' => $start,
            'end' => $end,
            'capacity' => $capacity_total !== null ? (int) $capacity_total : null,
            'available' => $available,
            'booking_url' => $booking_url ? (string) $booking_url : null,
        ];
    }

    // Keep the REST endpoint cache and the CPT in sync.
    foreach ($slots_by_tag as $tag => $slots) {
        if (!is_array($slots)) continue;

        usort($slots, function ($a, $b) {
            return strcmp((string) ($a['start'] ?? ''), (string) ($b['start'] ?? ''));
        });

        if (function_exists('is_tours_set_cached_slots_by_tag')) {
            is_tours_set_cached_slots_by_tag($tag, $slots, 60 * 60 * 6);
            if (function_exists('is_tours_set_cached_source_by_tag')) {
                is_tours_set_cached_source_by_tag($tag, 'cpt', 60 * 60 * 6);
            }
        } else {
            set_transient('is_tours_slots_' . md5($tag), $slots, 60 * 60 * 6);
            set_transient('is_tours_slots_src_' . md5($tag), 'cpt', 60 * 60 * 6);
        }
    }

    update_option(ISS_CALENDAR_LAST_SYNC_OPTION, $now, false);

    return [
        'created' => $created,
        'updated' => $updated,
        'errors' => $errors,
    ];
}

function iss_calendar_build_schedule_url($settings) {
    if (!is_array($settings)) return '';

    $base_url = isset($settings['base_url']) ? untrailingslashit((string) $settings['base_url']) : '';
    $account_name = isset($settings['account_name']) ? (string) $settings['account_name'] : '';
    $schedule_path = function_exists('is_saas_get_schedule_path') ? is_saas_get_schedule_path($settings) : '';
    $schedule_path = function_exists('is_saas_normalize_schedule_path') ? is_saas_normalize_schedule_path($schedule_path) : '';

    if ($base_url === '' || $account_name === '' || $schedule_path === '') {
        return '';
    }

    return $base_url . '/schedule/' . rawurlencode($account_name) . '/' . ltrim($schedule_path, '/');
}

function iss_calendar_find_item_post_id($external_id, $source_calendar) {
    global $wpdb;

    $external_id = (string) $external_id;
    $source_calendar = (string) $source_calendar;
    if ($external_id === '' || $source_calendar === '') return 0;

    $sql = "
        SELECT p.ID
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} m1
            ON m1.post_id = p.ID AND m1.meta_key = 'external_id' AND m1.meta_value = %s
        INNER JOIN {$wpdb->postmeta} m2
            ON m2.post_id = p.ID AND m2.meta_key = 'source_calendar' AND m2.meta_value = %s
        WHERE p.post_type = %s
        LIMIT 1
    ";

    $found = $wpdb->get_var($wpdb->prepare($sql, $external_id, $source_calendar, ISS_CALENDAR_ITEM_POST_TYPE));
    return $found ? (int) $found : 0;
}
