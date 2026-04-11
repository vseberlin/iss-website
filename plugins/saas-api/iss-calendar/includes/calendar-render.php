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

    $limit = isset($attributes['limit']) ? (int) $attributes['limit'] : 12;
    if ($limit <= 0) {
        $limit = 12;
    }

    $title = isset($attributes['title']) ? (string) $attributes['title'] : 'Termine';
    $title = trim($title);
    if ($title === '') {
        $title = 'Termine';
    }

    if (!function_exists('iss_calendar_get_items_for_post')) {
        return '';
    }

    $post_id = (int) get_the_ID();
    if ($post_id <= 0) {
        return '';
    }

    $items = iss_calendar_get_items_for_post($post_id, [
        'public_only' => true,
        'future_only' => true,
        'limit' => $limit,
    ]);

    $attrs = function_exists('get_block_wrapper_attributes')
        ? get_block_wrapper_attributes(['class' => 'wp-block-iss-tour-dates'])
        : 'class="wp-block-iss-tour-dates"';

    if (empty($items)) {
        $out = '<div ' . $attrs . '>';
        $out .= '<h3 class="iss-tour-dates__title">' . esc_html($title) . '</h3>';
        $out .= '<p>' . esc_html__('Aktuell sind keine Termine verfügbar.', 'iss-calendar') . '</p>';
        $out .= '</div>';
        return $out;
    }

    $out = '<div ' . $attrs . '>';
    $out .= '<h3 class="iss-tour-dates__title">' . esc_html($title) . '</h3>';
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

/**
 * Dynamic block renderer: iss/tour-calendar.
 *
 * Uses the existing shortcode markup so the front-end JS can attach reliably.
 *
 * @param array<string,mixed> $attributes
 * @param string $content
 * @return string
 */
function iss_render_tour_calendar($attributes = [], $content = '') {
    $attributes = is_array($attributes) ? $attributes : [];

    $title = isset($attributes['title']) ? sanitize_text_field((string) $attributes['title']) : 'Termine wählen';
    $fallback_url = isset($attributes['fallbackUrl']) ? esc_url_raw((string) $attributes['fallbackUrl']) : '';

    $post_id = (int) get_the_ID();
    $post_type = $post_id ? get_post_type($post_id) : '';

    $tag = isset($attributes['tag']) ? strtoupper(sanitize_text_field((string) $attributes['tag'])) : '';
    if ($tag === '' && $post_id) {
        $tag = strtoupper(sanitize_text_field((string) get_post_meta($post_id, 'calendar_tag', true)));
    }

    if ($tag === '') {
        // Can't render an interactive calendar without a tag.
        $attrs = function_exists('get_block_wrapper_attributes')
            ? get_block_wrapper_attributes([
                'class' => 'is-tour-calendar wp-block-group alignwide has-global-padding is-layout-constrained',
            ])
            : 'class="is-tour-calendar wp-block-group alignwide has-global-padding is-layout-constrained"';

        $msg = esc_html__('Kalender ist nicht konfiguriert (Tag fehlt).', 'iss-calendar');
        $link_text = ($fallback_url && str_starts_with((string) $fallback_url, '#'))
            ? esc_html__('Alle Termine anzeigen', 'iss-calendar')
            : esc_html__('Direkt buchen', 'iss-calendar');
        $link = $fallback_url ? ' <a href="' . esc_url($fallback_url) . '">' . $link_text . '</a>' : '';
        return '<div ' . $attrs . '><p class="is-tour-calendar__status has-small-font-size">' . $msg . $link . '</p></div>';
    }

    if (function_exists('iss_calendar_remember_source_mapping')) {
        iss_calendar_remember_source_mapping($tag, $fallback_url, $post_id, $post_type);
    }

    // Render only a lightweight mount node; front-end JS builds the UI.
    $attrs = function_exists('get_block_wrapper_attributes')
        ? get_block_wrapper_attributes([
            'class' => 'is-tour-calendar wp-block-group alignwide has-global-padding is-layout-constrained',
        ])
        : 'class="is-tour-calendar wp-block-group alignwide has-global-padding is-layout-constrained"';

    $fallback_label = ($fallback_url && str_starts_with((string) $fallback_url, '#'))
        ? esc_html__('Alle Termine anzeigen', 'iss-calendar')
        : esc_html__('Direkt buchen', 'iss-calendar');

    $fallback_html = '';
    if ($fallback_url) {
        $fallback_html = '<p class="is-tour-calendar__fallback has-small-font-size">'
            . '<a class="is-tour-calendar__fallback-link" href="' . esc_url($fallback_url) . '">' . $fallback_label . '</a>'
            . '</p>';
    }

    $noscript = '<noscript><p class="is-tour-calendar__status has-small-font-size">'
        . esc_html__('Bitte JavaScript aktivieren, um den Kalender zu nutzen.', 'iss-calendar')
        . '</p>'
        . $fallback_html
        . '</noscript>';

    return sprintf(
        '<div %s data-tag="%s" data-fallback="%s" data-title="%s" data-source-post-id="%s" data-source-post-type="%s"><p class="is-tour-calendar__status has-small-font-size">%s</p>%s%s</div>',
        $attrs,
        esc_attr($tag),
        esc_url($fallback_url),
        esc_attr($title),
        esc_attr($post_id ? (string) $post_id : ''),
        esc_attr((string) $post_type),
        esc_html__('Termine werden geladen …', 'iss-calendar'),
        $fallback_html,
        $noscript
    );
}
