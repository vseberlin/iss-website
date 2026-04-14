<?php
if (!defined('ABSPATH')) exit;

function iss_timeline_get_now_mysql() {
    return current_time('mysql');
}

function iss_timeline_build_visibility_meta_query() {
    // Visible items only.
    // Back-compat: if `is_visible` is not set, fall back to `is_public`.
    return [
        'relation' => 'OR',
        [
            'key' => 'is_visible',
            'value' => 1,
            'compare' => '=',
            'type' => 'NUMERIC',
        ],
        [
            'relation' => 'AND',
            [
                'key' => 'is_visible',
                'compare' => 'NOT EXISTS',
            ],
            [
                'key' => 'is_public',
                'value' => 1,
                'compare' => '=',
                'type' => 'NUMERIC',
            ],
        ],
    ];
}

function iss_timeline_month_to_range($ym) {
    $ym = trim((string) $ym);
    if (!preg_match('/^\\d{4}-\\d{2}$/', $ym)) return null;

    try {
        $tz = wp_timezone();
        $start_dt = new DateTimeImmutable($ym . '-01 00:00:00', $tz);
        $end_dt = $start_dt->modify('+1 month')->modify('-1 second');
        return [
            'start' => $start_dt->format('Y-m-d H:i:s'),
            'end' => $end_dt->format('Y-m-d H:i:s'),
        ];
    } catch (Throwable $e) {
        return null;
    }
}

function iss_timeline_build_type_meta_query($type_filter) {
    $type_filter = sanitize_key((string) $type_filter);
    if ($type_filter === '' || $type_filter === 'all') return [];

    // Simple editorial filters used by the site.
    if (in_array($type_filter, ['fuehrungen', 'fuehrung', 'tour'], true)) {
        return [
            'key' => 'item_type',
            'value' => 'tour',
            'compare' => '=',
        ];
    }

    if (in_array($type_filter, ['veranstaltungen', 'veranstaltung', 'event'], true)) {
        return [
            'key' => 'item_type',
            'value' => 'event',
            'compare' => '=',
        ];
    }

    return [
        'key' => 'item_type',
        'value' => $type_filter,
        'compare' => '=',
    ];
}

function iss_timeline_get_items_advanced($args = []) {
    $post_type = function_exists('iss_timeline_get_post_type') ? iss_timeline_get_post_type() : 'iss_calendar_item';

    $defaults = [
        'limit' => 50,
        'order' => 'ASC',
        'group' => '',
        'range' => 'all', // all|future|past
        'month' => '', // YYYY-MM
        'type' => '',  // all|fuehrungen|veranstaltungen|...
    ];
    $args = wp_parse_args($args, $defaults);

    $limit = (int) ($args['limit'] ?? 50);
    if ($limit <= 0) $limit = -1;

    $order = strtoupper(sanitize_text_field((string) ($args['order'] ?? 'ASC')));
    if (!in_array($order, ['ASC', 'DESC'], true)) $order = 'ASC';

    $meta_query = [];
    $meta_query[] = iss_timeline_build_visibility_meta_query();

    $range = sanitize_key((string) ($args['range'] ?? 'all'));
    if ($range === 'future') {
        $meta_query[] = [
            'key' => 'sort_date',
            'value' => iss_timeline_get_now_mysql(),
            'compare' => '>=',
            'type' => 'DATETIME',
        ];
    } elseif ($range === 'past') {
        $meta_query[] = [
            'key' => 'sort_date',
            'value' => iss_timeline_get_now_mysql(),
            'compare' => '<',
            'type' => 'DATETIME',
        ];
    }

    $month = isset($args['month']) ? (string) $args['month'] : '';
    if ($month !== '') {
        $range2 = iss_timeline_month_to_range($month);
        if (is_array($range2)) {
            $meta_query[] = [
                'key' => 'sort_date',
                'value' => [$range2['start'], $range2['end']],
                'compare' => 'BETWEEN',
                'type' => 'DATETIME',
            ];
        }
    }

    $type = $args['type'] ?? '';
    $type_q = iss_timeline_build_type_meta_query($type);
    if (!empty($type_q)) {
        $meta_query[] = $type_q;
    }

    $tax_query = [];
    $group = isset($args['group']) ? sanitize_text_field((string) $args['group']) : '';
    if ($group !== '' && taxonomy_exists('iss_timeline_group')) {
        $tax_query[] = [
            'taxonomy' => 'iss_timeline_group',
            'field' => 'slug',
            'terms' => [$group],
        ];
    }

    $query_args = [
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'no_found_rows' => true,
        'meta_key' => 'sort_date',
        'orderby' => 'meta_value',
        'meta_type' => 'DATETIME',
        'order' => $order,
        'meta_query' => $meta_query,
    ];
    if (!empty($tax_query)) {
        $query_args['tax_query'] = $tax_query;
    }

    $q = new WP_Query($query_args);
    return $q->posts;
}

