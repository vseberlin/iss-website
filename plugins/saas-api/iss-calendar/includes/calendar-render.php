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

