<?php

if (!defined('ABSPATH')) exit;

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
 * Dynamic block renderer: iss/tour-dates.
 *
 * Renders upcoming `iss_calendar_item` entries linked to the current post
 * (via `source_post_id`), or a specific post id when passed via attributes.
 *
 * @param array<string,mixed> $attributes
 * @param string $content
 * @return string
 */
function iss_calendar_render_dates($attributes = [], $content = '') {
    $attributes = is_array($attributes) ? $attributes : [];

    $source_post_id = isset($attributes['sourcePostId']) ? (int) $attributes['sourcePostId'] : 0;
    if ($source_post_id <= 0) {
        $source_post_id = (int) get_the_ID();
    }

    $limit = isset($attributes['limit']) ? (int) $attributes['limit'] : 6;
    if ($limit <= 0) {
        $limit = 6;
    }

    if (!function_exists('iss_calendar_get_items_for_post')) {
        return '';
    }

    $items = iss_calendar_get_items_for_post($source_post_id, [
        'public_only' => true,
        'future_only' => true,
        'limit' => $limit,
    ]);

    $attrs = function_exists('get_block_wrapper_attributes')
        ? get_block_wrapper_attributes(['class' => 'wp-block-iss-tour-dates'])
        : 'class="wp-block-iss-tour-dates"';

    if (empty($items)) {
        return '<div ' . $attrs . '><p>' . esc_html__('Aktuell sind keine Termine verfügbar.', 'iss-calendar') . '</p></div>';
    }

    $out = '<div ' . $attrs . '>';
    $out .= '<ul class="iss-tour-dates">';

    foreach ($items as $item) {
        if (!($item instanceof WP_Post)) {
            continue;
        }

        $data = iss_calendar_prepare_item($item->ID);
        $label = isset($data['datetime_label']) ? (string) $data['datetime_label'] : '';
        if ($label === '') {
            $label = get_the_title($item->ID);
        }

        $booking_url = isset($data['booking_url']) ? (string) $data['booking_url'] : '';
        $is_sold_out = isset($data['availability']) && (string) $data['availability'] === 'sold_out';

        $out .= '<li class="iss-tour-dates__item">';
        $out .= '<span class="iss-tour-dates__label">' . esc_html($label) . '</span>';

        if ($booking_url !== '') {
            $out .= ' <a class="iss-tour-dates__link" href="' . esc_url($booking_url) . '">';
            $out .= $is_sold_out ? esc_html__('Ausgebucht', 'iss-calendar') : esc_html__('Buchen', 'iss-calendar');
            $out .= '</a>';
        }

        $out .= '</li>';
    }

    $out .= '</ul></div>';
    return $out;
}
