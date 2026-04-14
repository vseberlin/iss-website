<?php

if (!defined('ABSPATH')) exit;

define('ISS_CALENDAR_SYNC_HOOK', 'iss_calendar_cron_sync');
define('ISS_CALENDAR_LAST_SYNC_OPTION', 'iss_calendar_last_sync_at');

add_action('admin_menu', function () {
    add_management_page(
        'SaaS-Kalenderabgleich',
        'SaaS-Kalenderabgleich',
        'manage_options',
        'iss-calendar-sync',
        'iss_calendar_render_sync_page'
    );
});

add_action('admin_notices', function () {
    if (!is_admin() || !current_user_can('manage_options')) return;

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || strpos((string) $screen->id, ISS_CALENDAR_ITEM_POST_TYPE) === false) {
        return;
    }

    $count = (int) get_option('iss_calendar_unmapped_count', 0);
    if ($count <= 0) {
        $count = iss_calendar_count_unmapped_upcoming_items();
        update_option('iss_calendar_unmapped_count', (int) $count, false);
    }
    if ($count <= 0) return;

    $link = admin_url('edit.php?post_type=' . ISS_CALENDAR_ITEM_POST_TYPE . '&iss_needs_linking=1');
    printf(
        '<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
        esc_html__('SaaS-Einträge ohne Inhaltszuordnung erkannt.', 'saas-api'),
        esc_html(sprintf(__('%d kommende Einträge sind bereits aus SuperSaaS importiert und in der Timeline sichtbar. Bitte Quelle nachtragen.', 'saas-api'), $count)),
        esc_url($link),
        esc_html__('Jetzt prüfen', 'saas-api')
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
    echo '<h1>SaaS-Kalenderabgleich</h1>';

    if (is_array($result)) {
        $created = (int) ($result['created'] ?? 0);
        $updated = (int) ($result['updated'] ?? 0);
        $errors = (int) ($result['errors'] ?? 0);
        $conflicts = (int) ($result['conflicts'] ?? 0);
        printf(
            '<div class="notice notice-success"><p>Abgleich abgeschlossen. Neu: %d, Aktualisiert: %d, Fehler: %d, Konflikte: %d.</p></div>',
            $created,
            $updated,
            $errors,
            $conflicts
        );
    }

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="iss_calendar_sync" />';
    wp_nonce_field('iss_calendar_sync');
    submit_button('Jetzt abgleichen');
    echo '</form>';

    $unmapped_count = (int) get_option('iss_calendar_unmapped_count', 0);
    $conflict_count = (int) get_option('iss_calendar_mapping_conflicts', 0);
    if ($unmapped_count > 0) {
        printf(
            '<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
            esc_html__('Warnung:', 'saas-api'),
            esc_html(sprintf(__('%d kommende SaaS-Einträge haben noch keine verknüpfte Inhaltsseite.', 'saas-api'), $unmapped_count))
        );
    }
    if ($conflict_count > 0) {
        printf(
            '<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
            esc_html__('Konflikt:', 'saas-api'),
            esc_html(sprintf(__('%d Einträge hatten mehrdeutige Zuordnungen (mehrere mögliche Quellen). Bitte prüfen.', 'saas-api'), $conflict_count))
        );
    }

    echo '<h2>Zuordnungstabelle</h2>';
    if (empty($map)) {
        echo '<p>' . esc_html__('Noch keine Zuordnungen vorhanden. Die Map wird automatisch beim Sync aus SuperSaaS aufgebaut.', 'saas-api') . '</p>';
    } else {
        echo '<table class="widefat striped"><thead><tr><th>Tag</th><th>Verknüpfter Inhalt</th><th>Buchungslink</th><th>Zuletzt gesehen</th></tr></thead><tbody>';
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

            $ptype_obj = $post_type !== '' ? get_post_type_object($post_type) : null;
            $ptype_label = ($ptype_obj && !empty($ptype_obj->labels->singular_name))
                ? (string) $ptype_obj->labels->singular_name
                : $post_type;
            $type_line = $ptype_label !== '' ? '<br><small>' . esc_html($ptype_label) . '</small>' : '';

            printf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                esc_html((string) $tag),
                $post_label . $type_line,
                $fallback_url ? ('<a href="' . esc_url($fallback_url) . '" target="_blank" rel="noopener">' . esc_html__('Öffnen', 'saas-api') . '</a>') : '—',
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

function iss_calendar_clean_supersaas_title($raw_title) {
    $raw_title = trim((string) $raw_title);
    if ($raw_title === '') return '';

    $parsed = iss_calendar_parse_supersaas_title($raw_title);
    $title = isset($parsed['title']) ? (string) $parsed['title'] : '';
    return $title !== '' ? $title : $raw_title;
}

function iss_calendar_generate_tag_from_title($title) {
    $title = trim((string) $title);
    if ($title === '') {
        return 'TOUR_' . substr(md5((string) microtime(true)), 0, 8);
    }

    $slug = sanitize_title($title);
    $slug = strtoupper(str_replace('-', '_', $slug));
    $slug = preg_replace('/[^A-Z0-9_]+/', '', (string) $slug);
    $slug = trim((string) $slug, '_');

    if ($slug === '') {
        $slug = 'TOUR_' . substr(md5($title), 0, 8);
    }

    if (strlen($slug) > 32) {
        $slug = substr($slug, 0, 32);
        $slug = rtrim($slug, '_');
    }

    return $slug;
}

function iss_calendar_extract_tag_from_text($text) {
    $text = trim((string) $text);
    if ($text === '') return '';

    // Hidden marker (if SuperSaaS renders HTML): "Elektropolis Tour <!-- TAG=ELEKTRO -->"
    if (preg_match('/<!--\\s*TAG\\s*[:=]\\s*([A-Z0-9_-]{2,})\\s*-->/i', $text, $m)) {
        return strtoupper(trim((string) $m[1]));
    }

    // Example: "TAG=ELEKTRO" or "tag: elektro"
    if (preg_match('/\\bTAG\\s*[:=]\\s*([A-Z0-9_-]{2,})\\b/i', $text, $m)) {
        return strtoupper(trim((string) $m[1]));
    }

    // Example: "[ELEKTRO] some note"
    if (preg_match('/^\\s*\\[([^\\]]{2,})\\]/u', $text, $m)) {
        return strtoupper(trim((string) $m[1]));
    }

    return '';
}

function iss_calendar_extract_supersaas_slot_tag($slot) {
    if (!is_array($slot)) return '';

    // Preferred: keep public title clean, store tag in description.
    $desc = isset($slot['description']) ? (string) $slot['description'] : '';
    $tag = iss_calendar_extract_tag_from_text($desc);
    if ($tag !== '') return $tag;

    // Back-compat: legacy "[TAG] Title" prefix in title.
    $raw_title = isset($slot['title']) ? (string) $slot['title'] : '';
    $parsed = iss_calendar_parse_supersaas_title($raw_title);
    $tag = isset($parsed['tag']) ? strtoupper((string) $parsed['tag']) : '';
    if ($tag !== '') return $tag;

    // Optional: allow tagging via location only if it matches our tag pattern.
    $loc = isset($slot['location']) ? (string) $slot['location'] : '';
    $tag = iss_calendar_extract_tag_from_text($loc);
    if ($tag !== '') return $tag;

    return '';
}

/**
 * Normalize SuperSaaS free slots for a given (clean) title to the REST "slots" shape.
 *
 * @param string $title
 * @param array|null $settings
 * @return array<int,array<string,mixed>>|WP_Error
 */
function iss_calendar_get_supersaas_slots_for_title($title, $settings = null) {
    $title = trim((string) $title);
    if ($title === '') {
        return [];
    }

    $slot_items = iss_calendar_supersaas_fetch_free_slots($settings);
    if (is_wp_error($slot_items)) {
        return $slot_items;
    }

    $slots = [];

    foreach ($slot_items as $slot) {
        if (!is_array($slot)) continue;

        $raw_title = isset($slot['title']) ? (string) $slot['title'] : '';
        $clean_title = iss_calendar_clean_supersaas_title($raw_title);
        if ($clean_title === '' || $clean_title !== $title) {
            continue;
        }

        $start = $slot['start'] ?? null;
        if (!$start) continue;

        $available = null;
        if (isset($slot['available'])) {
            $available = (int) $slot['available'];
        } elseif (isset($slot['remaining'])) {
            $available = (int) $slot['remaining'];
        } elseif (isset($slot['count'])) {
            $available = (int) $slot['count'];
        }

        $end = $slot['end'] ?? ($slot['finish'] ?? null);
        if ($end === '') $end = null;

        $slots[] = [
            'id' => isset($slot['id']) ? (string) $slot['id'] : '',
            'title' => $clean_title,
            'start' => $start,
            'end' => $end,
            'available' => $available,
            'capacity' => isset($slot['capacity']) ? (int) $slot['capacity'] : null,
            'booking_url' => null,
        ];
    }

    usort($slots, function ($a, $b) {
        return strcmp((string) ($a['start'] ?? ''), (string) ($b['start'] ?? ''));
    });

    return $slots;
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

        $slot_tag = iss_calendar_extract_supersaas_slot_tag($slot);
        if ($slot_tag === '' || $slot_tag !== $tag) {
            continue;
        }

        $parsed = iss_calendar_parse_supersaas_title($raw_title);
        $clean_title = !empty($parsed['title']) ? (string) $parsed['title'] : $raw_title;

        $start = $slot['start'] ?? null;
        if (!$start) {
            continue;
        }

        if (function_exists('is_saas_build_slot_response')) {
            $built = is_saas_build_slot_response($slot, $clean_title, $start);
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
            'title' => $clean_title,
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
    if (!is_wp_error($slots) && empty($slots) && function_exists('iss_calendar_get_source_map_entry')) {
        $entry = iss_calendar_get_source_map_entry($tag);
        if (is_array($entry) && !empty($entry['supersaas_title'])) {
            $slots = iss_calendar_get_supersaas_slots_for_title((string) $entry['supersaas_title'], $settings);
        }
    }
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
 * @return array{created:int,updated:int,errors:int,conflicts:int}
 */
function iss_calendar_sync_supersaas_to_cpt() {
    $settings = function_exists('is_saas_get_settings') ? is_saas_get_settings() : [];
    $slot_items = iss_calendar_supersaas_fetch_free_slots($settings);
    if (is_wp_error($slot_items)) {
        return ['created' => 0, 'updated' => 0, 'errors' => 1, 'conflicts' => 0];
    }

    $source_calendar = function_exists('is_saas_get_schedule_path')
        ? (is_saas_get_schedule_path($settings) ?: (string) ($settings['schedule_id'] ?? ''))
        : (string) ($settings['schedule_id'] ?? '');
    $source_calendar = sanitize_text_field((string) $source_calendar);

    $schedule_url = iss_calendar_build_schedule_url($settings);
    $map = function_exists('iss_calendar_get_source_map') ? iss_calendar_get_source_map() : [];

    $title_index = [];
    foreach ($map as $map_tag => $entry) {
        if (!is_array($entry)) continue;
        $t = isset($entry['supersaas_title']) ? trim((string) $entry['supersaas_title']) : '';
        if ($t !== '') {
            $title_index[$t] = strtoupper(sanitize_text_field((string) $map_tag));
        }
    }

    $now = current_time('mysql');
    $created = 0;
    $updated = 0;
    $errors = 0;
    $conflicts = 0;
    $slots_by_tag = [];

    foreach ($slot_items as $slot) {
        if (!is_array($slot)) continue;

        $external_id = isset($slot['id']) ? trim((string) $slot['id']) : '';
        if ($external_id === '') continue;

        $raw_title = isset($slot['title']) ? (string) $slot['title'] : '';
        $parsed = iss_calendar_parse_supersaas_title($raw_title);
        $tag = iss_calendar_extract_supersaas_slot_tag($slot);
        if ($tag === '' && !empty($title_index)) {
            $ct = iss_calendar_clean_supersaas_title($raw_title);
            if ($ct !== '' && isset($title_index[$ct])) {
                $tag = $title_index[$ct];
            }
        }

        $clean_title = isset($parsed['title']) ? (string) $parsed['title'] : '';
        if ($clean_title === '') {
            $clean_title = trim((string) $raw_title);
        }

        if ($tag === '' && $clean_title !== '' && function_exists('iss_calendar_generate_tag_from_title')) {
            $tag = iss_calendar_generate_tag_from_title($clean_title);
        }

        if ($tag === '') {
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
        $resolved = ['post_id' => 0, 'post_type' => '', 'ambiguous' => false];
        if (function_exists('iss_calendar_resolve_source_by_tag')) {
            $resolved = iss_calendar_resolve_source_by_tag($tag);
        }
        if ((int) ($resolved['post_id'] ?? 0) <= 0 && $clean_title !== '' && function_exists('iss_calendar_resolve_source_by_saas_title')) {
            $resolved2 = iss_calendar_resolve_source_by_saas_title($clean_title);
            if (!empty($resolved2['ambiguous'])) {
                $resolved['ambiguous'] = true;
            }
            if ((int) ($resolved2['post_id'] ?? 0) > 0) {
                $resolved = $resolved2;
            }
        }

        if (!empty($resolved['ambiguous'])) {
            $conflicts++;
        }

        $source_post_id = (int) ($resolved['post_id'] ?? 0);
        $source_post_type = sanitize_key((string) ($resolved['post_type'] ?? ''));

        if (function_exists('iss_calendar_upsert_source_map_entry')) {
            $fallback_for_map = isset($map_entry['fallback_url']) && (string) $map_entry['fallback_url'] !== ''
                ? (string) $map_entry['fallback_url']
                : $schedule_url;

            iss_calendar_upsert_source_map_entry($tag, [
                'source_post_id' => $source_post_id,
                'source_post_type' => $source_post_type,
                'fallback_url' => $fallback_for_map,
                'supersaas_title' => $clean_title,
            ]);
        }

        // keep local map in sync for this run
        if (!isset($map[$tag]) || !is_array($map[$tag])) {
            $map[$tag] = [];
        }
        $map[$tag]['source_post_id'] = $source_post_id;
        $map[$tag]['source_post_type'] = $source_post_type;
        $map[$tag]['supersaas_title'] = $clean_title;
        if (empty($map[$tag]['fallback_url']) && $schedule_url !== '') {
            $map[$tag]['fallback_url'] = $schedule_url;
        }

        $fallback_url = isset($map[$tag]['fallback_url']) ? esc_url_raw((string) $map[$tag]['fallback_url']) : '';

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
        update_post_meta($post_id, 'supersaas_title', $clean_title);
        if (function_exists('iss_calendar_build_series_key')) {
            update_post_meta($post_id, 'series_key', iss_calendar_build_series_key($clean_title, 'tour'));
        }
        update_post_meta($post_id, 'source_system', 'supersaas');
        update_post_meta($post_id, 'source_calendar', $source_calendar);
        update_post_meta($post_id, 'external_id', $external_id);
        update_post_meta($post_id, 'source_post_id', $source_post_id);
        update_post_meta($post_id, 'source_post_type', $source_post_type);
        update_post_meta($post_id, 'booking_url', $booking_url);
        if (!empty($slot['location'])) {
            update_post_meta($post_id, 'location', sanitize_text_field((string) $slot['location']));
        }
        update_post_meta($post_id, 'availability_state', $availability_state);
        if ($capacity_total !== null) update_post_meta($post_id, 'capacity_total', (int) $capacity_total);
        if ($available !== null) update_post_meta($post_id, 'capacity_available', (int) $available);
        update_post_meta($post_id, 'is_public', 1);
        update_post_meta($post_id, 'is_visible', 1);
        update_post_meta($post_id, 'sync_status', 'ok');
        update_post_meta($post_id, 'last_synced_at', $now);
        update_post_meta($post_id, 'last_seen_at', $now);
        update_post_meta($post_id, 'origin_mode', 'supersaas');
        update_post_meta($post_id, 'sort_date', $start);
        update_post_meta($post_id, 'calendar_tag', $tag);

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

    $unmapped_count = iss_calendar_count_unmapped_upcoming_items();
    update_option('iss_calendar_unmapped_count', (int) $unmapped_count, false);
    update_option('iss_calendar_mapping_conflicts', (int) $conflicts, false);

    return [
        'created' => $created,
        'updated' => $updated,
        'errors' => $errors,
        'conflicts' => $conflicts,
    ];
}

/**
 * Count upcoming imported items with no linked source post.
 */
function iss_calendar_count_unmapped_upcoming_items() {
    $q = new WP_Query([
        'post_type' => ISS_CALENDAR_ITEM_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => 'source_system',
                'value' => 'supersaas',
                'compare' => '=',
            ],
            [
                'key' => 'event_start',
                'value' => current_time('mysql'),
                'compare' => '>=',
                'type' => 'DATETIME',
            ],
            [
                'key' => 'source_post_id',
                'value' => 0,
                'compare' => '=',
                'type' => 'NUMERIC',
            ],
        ],
    ]);

    return (int) ($q->found_posts ?? 0);
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
