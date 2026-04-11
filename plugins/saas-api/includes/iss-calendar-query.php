<?php

if (!defined('ABSPATH')) exit;

/**
 * Get calendar items linked to a source post.
 *
 * @param int $post_id
 * @param array $args
 * @return WP_Post[]
 */
function iss_calendar_get_items_for_post($post_id, $args = []) {
    $post_id = (int) $post_id;
    if ($post_id <= 0) return [];

    $defaults = [
        'public_only' => true,
        'future_only' => true,
        'limit' => -1,
    ];

    $args = wp_parse_args($args, $defaults);

    $meta_query = [
        [
            'key' => 'source_post_id',
            'value' => $post_id,
            'compare' => '=',
            'type' => 'NUMERIC',
        ],
    ];

    if (!empty($args['public_only'])) {
        $meta_query[] = [
            'key' => 'is_public',
            'value' => 1,
            'compare' => '=',
            'type' => 'NUMERIC',
        ];
    }

    if (!empty($args['future_only'])) {
        $meta_query[] = [
            'key' => 'event_start',
            'value' => current_time('mysql'),
            'compare' => '>=',
            'type' => 'DATETIME',
        ];
    }

    $limit = isset($args['limit']) ? (int) $args['limit'] : -1;
    if ($limit === 0) {
        $limit = -1;
    }

    $query = new WP_Query([
        'post_type' => ISS_CALENDAR_ITEM_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'no_found_rows' => true,

        'meta_key' => 'event_start',
        'orderby' => 'meta_value',
        'meta_type' => 'DATETIME',
        'order' => 'ASC',

        'meta_query' => $meta_query,
    ]);

    return $query->posts;
}

/**
 * Get next public event for a source post.
 *
 * @param int $post_id
 * @return WP_Post|null
 */
function iss_calendar_get_next_item_for_post($post_id) {
    $items = iss_calendar_get_items_for_post($post_id, [
        'public_only' => true,
        'future_only' => true,
        'limit' => 1,
    ]);

    return !empty($items) ? $items[0] : null;
}

/**
 * Normalize one event into readable frontend data.
 *
 * @param int $item_id
 * @return array<string,mixed>
 */
function iss_calendar_prepare_item($item_id) {
    $item_id = (int) $item_id;
    if ($item_id <= 0) {
        return [];
    }

    $start = (string) get_post_meta($item_id, 'event_start', true);
    $end = (string) get_post_meta($item_id, 'event_end', true);

    $start_ts = null;
    $end_ts = null;

    try {
        if ($start !== '') {
            $start_dt = new DateTimeImmutable($start, wp_timezone());
            $start_ts = $start_dt->getTimestamp();
        }
    } catch (Throwable $e) {
        $start_ts = null;
    }

    try {
        if ($end !== '') {
            $end_dt = new DateTimeImmutable($end, wp_timezone());
            $end_ts = $end_dt->getTimestamp();
        }
    } catch (Throwable $e) {
        $end_ts = null;
    }

    $availability = (string) get_post_meta($item_id, 'availability_state', true);
    $available_raw = get_post_meta($item_id, 'capacity_available', true);
    $available = ($available_raw === '' || $available_raw === null) ? null : (int) $available_raw;
    $booking_url = (string) get_post_meta($item_id, 'booking_url', true);
    $note = (string) get_post_meta($item_id, 'public_note', true);

    return [
        'id' => $item_id,
        'title' => get_the_title($item_id),
        'start_raw' => $start,
        'end_raw' => $end,
        'date_label' => $start_ts ? wp_date('j. F Y', $start_ts) : '',
        'time_label' => $start_ts ? wp_date('G:i', $start_ts) . ' Uhr' : '',
        'datetime_label' => $start_ts ? wp_date('j. F Y, G:i', $start_ts) . ' Uhr' : '',
        'availability' => $availability,
        'available' => $available,
        'booking_url' => $booking_url,
        'note' => $note,
        'start_ts' => $start_ts,
        'end_ts' => $end_ts,
    ];
}

/**
 * Back-compat: original helper (no filters).
 *
 * @param int $post_id
 * @return WP_Post[]
 */
function iss_get_events_for_post($post_id) {
    return iss_calendar_get_items_for_post($post_id, [
        'public_only' => false,
        'future_only' => false,
        'limit' => -1,
    ]);
}
