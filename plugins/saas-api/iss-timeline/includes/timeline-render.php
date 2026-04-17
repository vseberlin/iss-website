<?php
if (!defined('ABSPATH')) exit;

function iss_timeline_extract_teaser_text($post_id, $word_limit = 28) {
    $post_id = (int) $post_id;
    $word_limit = (int) $word_limit;
    if ($post_id <= 0) return '';
    if ($word_limit <= 0) $word_limit = 28;

    $post = get_post($post_id);
    if (!$post instanceof WP_Post) return '';

    if (has_excerpt($post_id)) {
        $ex = trim((string) get_the_excerpt($post_id));
        if ($ex !== '') {
            return wp_trim_words(wp_strip_all_tags($ex), $word_limit);
        }
    }

    $content = trim((string) $post->post_content);
    if ($content === '') return '';

    if (function_exists('has_blocks') && has_blocks($content) && function_exists('parse_blocks')) {
        $blocks = parse_blocks($content);

        $ignore = [
            'core/cover',
            'core/gallery',
            'core/image',
            'core/media-text',
            'core/video',
            'core/audio',
            'core/embed',
            'core/buttons',
            'core/button',
            'core/spacer',
            'core/separator',
        ];

        $allow_text = [
            'core/paragraph',
            'core/heading',
            'core/list',
            'core/quote',
            'core/pullquote',
        ];

        $out = [];
        $walk = function ($block) use (&$walk, &$out, $ignore, $allow_text) {
            if (!is_array($block)) return;
            $name = isset($block['blockName']) ? (string) $block['blockName'] : '';

            if ($name !== '' && in_array($name, $ignore, true)) {
                return;
            }

            if ($name === '' || !in_array($name, $allow_text, true)) {
                if (!empty($block['innerBlocks']) && is_array($block['innerBlocks'])) {
                    foreach ($block['innerBlocks'] as $inner) {
                        $walk($inner);
                    }
                }
                return;
            }

            $html = isset($block['innerHTML']) ? (string) $block['innerHTML'] : '';
            $txt = trim(wp_strip_all_tags($html));
            if ($txt !== '') $out[] = $txt;
        };

        foreach ($blocks as $b) {
            $walk($b);
            if (count($out) >= 3) break;
        }

        $text = trim(implode(' ', $out));
        if ($text !== '') return wp_trim_words($text, $word_limit);
    }

    $plain = trim(wp_strip_all_tags($content));
    if ($plain === '') return '';
    return wp_trim_words($plain, $word_limit);
}

function iss_timeline_prepare_item($item_id) {
    $item_id = (int) $item_id;
    if ($item_id <= 0) return [];

    $sort_date = (string) get_post_meta($item_id, 'sort_date', true);
    $event_end = (string) get_post_meta($item_id, 'event_end', true);

    $public_title = (string) get_post_meta($item_id, 'public_title', true);
    $public_summary = (string) get_post_meta($item_id, 'public_summary', true);
    $item_type = (string) get_post_meta($item_id, 'item_type', true);

    $cta_mode = (string) get_post_meta($item_id, 'cta_mode', true);
    $cta_url = (string) get_post_meta($item_id, 'cta_url', true);
    $cta_label = (string) get_post_meta($item_id, 'cta_label', true);
    $booking_url = (string) get_post_meta($item_id, 'booking_url', true);

    $source_post_id = (int) get_post_meta($item_id, 'source_post_id', true);

    $ts = null;
    $end_ts = null;
    try {
        if ($sort_date !== '') {
            $ts = (new DateTimeImmutable($sort_date, wp_timezone()))->getTimestamp();
        }
    } catch (Throwable $e) {
        $ts = null;
    }
    try {
        if ($event_end !== '') {
            $end_ts = (new DateTimeImmutable($event_end, wp_timezone()))->getTimestamp();
        }
    } catch (Throwable $e) {
        $end_ts = null;
    }

    $date_label = $ts ? wp_date('j. F Y', $ts) : $sort_date;
    $day_label = $ts ? wp_date('D. d.m.', $ts) : $date_label;

    $time_label = '';
    if ($ts) {
        $time_label = wp_date('H:i', $ts);
        if ($end_ts) {
            $time_label .= ' – ' . wp_date('H:i', $end_ts);
        }
        $time_label .= ' Uhr';
    }

    $title = trim($public_title);
    if ($title === '' && $source_post_id > 0) {
        $t = get_the_title($source_post_id);
        if (is_string($t) && trim($t) !== '') $title = $t;
    }
    if ($title === '') $title = get_the_title($item_id);

    $summary = trim($public_summary);
    if ($summary === '' && $source_post_id > 0) {
        $summary = iss_timeline_extract_teaser_text($source_post_id, 30);
    }

    return [
        'id' => $item_id,
        'title' => $title,
        'date_raw' => $sort_date,
        'date_label' => $date_label,
        'day_label' => $day_label,
        'time_label' => $time_label,
        'end_raw' => $event_end,
        'type' => $item_type,
        'summary' => $summary,
        'cta_mode' => $cta_mode,
        'cta_url' => $cta_url,
        'cta_label' => $cta_label !== '' ? $cta_label : __('Mehr erfahren', 'iss-timeline'),
        'booking_url' => $booking_url,
        'source_post_id' => $source_post_id,
        'year' => $ts ? (int) wp_date('Y', $ts) : null,
    ];
}

function iss_timeline_build_render_options($attributes = []) {
    $attributes = is_array($attributes) ? $attributes : [];

    return [
        'showDetailsButton' => !array_key_exists('showDetailsButton', $attributes) || (bool) $attributes['showDetailsButton'],
        'showRecommendButton' => !array_key_exists('showRecommendButton', $attributes) || (bool) $attributes['showRecommendButton'],
        'showTicketsButton' => !array_key_exists('showTicketsButton', $attributes) || (bool) $attributes['showTicketsButton'],
        'detailsButtonUrl' => isset($attributes['detailsButtonUrl']) ? (string) $attributes['detailsButtonUrl'] : '',
        'recommendButtonUrl' => isset($attributes['recommendButtonUrl']) ? (string) $attributes['recommendButtonUrl'] : '',
        'ticketsButtonUrl' => isset($attributes['ticketsButtonUrl']) ? (string) $attributes['ticketsButtonUrl'] : '',
        'detailsButtonText' => isset($attributes['detailsButtonText']) ? (string) $attributes['detailsButtonText'] : '',
        'recommendButtonText' => isset($attributes['recommendButtonText']) ? (string) $attributes['recommendButtonText'] : '',
        'ticketsButtonText' => isset($attributes['ticketsButtonText']) ? (string) $attributes['ticketsButtonText'] : '',
        'showBottomButton' => !empty($attributes['showBottomButton']) && (bool) $attributes['showBottomButton'],
        'bottomButtonUrl' => isset($attributes['bottomButtonUrl']) ? (string) $attributes['bottomButtonUrl'] : '',
        'bottomButtonText' => isset($attributes['bottomButtonText']) ? (string) $attributes['bottomButtonText'] : '',
    ];
}

function iss_timeline_render_bottom_button($opts = []) {
    $opts = is_array($opts) ? $opts : [];
    $url = isset($opts['bottomButtonUrl']) ? esc_url_raw((string) $opts['bottomButtonUrl']) : '';
    $show_bottom = !empty($opts['showBottomButton']) || $url !== '';
    if (!$show_bottom) return '';
    if ($url === '') return '';

    $label = isset($opts['bottomButtonText']) ? trim(sanitize_text_field((string) $opts['bottomButtonText'])) : '';
    if ($label === '') {
        $label = __('Zum gesamten Kalender', 'iss-timeline');
    }

    return '<div class="iss-timeline__footer"><a class="iss-timeline__btn iss-timeline__btn--primary iss-timeline__btn--bottom" href="'
        . esc_url($url) . '">' . esc_html($label) . '</a></div>';
}

function iss_timeline_build_actions($row, $opts = []) {
    if (!is_array($row)) return [];
    $opts = is_array($opts) ? $opts : [];
    $mode = isset($row['cta_mode']) ? sanitize_key((string) $row['cta_mode']) : '';
    $source_post_id = isset($row['source_post_id']) ? (int) $row['source_post_id'] : 0;
    $show_details = !array_key_exists('showDetailsButton', $opts) || (bool) $opts['showDetailsButton'];
    $show_recommend = !array_key_exists('showRecommendButton', $opts) || (bool) $opts['showRecommendButton'];
    $show_tickets = !array_key_exists('showTicketsButton', $opts) || (bool) $opts['showTicketsButton'];

    $details_override = isset($opts['detailsButtonUrl']) ? esc_url_raw((string) $opts['detailsButtonUrl']) : '';
    $recommend_override = isset($opts['recommendButtonUrl']) ? esc_url_raw((string) $opts['recommendButtonUrl']) : '';
    $tickets_override = isset($opts['ticketsButtonUrl']) ? esc_url_raw((string) $opts['ticketsButtonUrl']) : '';
    $details_label = isset($opts['detailsButtonText']) ? trim(sanitize_text_field((string) $opts['detailsButtonText'])) : '';
    $recommend_label = isset($opts['recommendButtonText']) ? trim(sanitize_text_field((string) $opts['recommendButtonText'])) : '';
    $tickets_label = isset($opts['ticketsButtonText']) ? trim(sanitize_text_field((string) $opts['ticketsButtonText'])) : '';
    if ($details_label === '') $details_label = __('Details anschauen', 'iss-timeline');
    if ($recommend_label === '') $recommend_label = __('Empfehlen', 'iss-timeline');
    if ($tickets_label === '') $tickets_label = __('Tickets kaufen', 'iss-timeline');

    $details_url = '';
    if ($source_post_id > 0) {
        $permalink = get_permalink($source_post_id);
        if (is_string($permalink) && $permalink !== '') {
            $details_url = $permalink;
        }
    }

    $booking_url = isset($row['booking_url']) ? trim((string) $row['booking_url']) : '';
    if ($booking_url === '' && $mode === 'booking' && $source_post_id > 0 && function_exists('iss_calendar_get_next_item_for_post')) {
        $next = iss_calendar_get_next_item_for_post($source_post_id);
        if ($next instanceof WP_Post) {
            $booking_url = trim((string) get_post_meta($next->ID, 'booking_url', true));
        }
    }

    $fallback_url = trim((string) ($row['cta_url'] ?? ''));
    if ($mode === 'external' && $fallback_url !== '') {
        $details_url = $fallback_url;
    } elseif ($details_url === '' && $fallback_url !== '') {
        $details_url = $fallback_url;
    }

    $share_target = $details_url !== '' ? $details_url : $booking_url;
    $recommend_url = '';
    if ($share_target !== '') {
        $subject = rawurlencode(sprintf(__('Empfehlung: %s', 'iss-timeline'), (string) ($row['title'] ?? '')));
        $body = rawurlencode($share_target);
        $recommend_url = 'mailto:?subject=' . $subject . '&body=' . $body;
    }

    if ($details_override !== '') {
        $details_url = $details_override;
    }
    if ($recommend_override !== '') {
        $recommend_url = $recommend_override;
    }
    if ($tickets_override !== '') {
        $booking_url = $tickets_override;
    }

    $actions = [];
    if ($show_details && $details_url !== '') {
        $actions[] = [
            'url' => $details_url,
            'label' => $details_label,
            'variant' => 'secondary',
        ];
    }
    if ($show_recommend && $recommend_url !== '') {
        $actions[] = [
            'url' => $recommend_url,
            'label' => $recommend_label,
            'variant' => 'secondary',
        ];
    }
    if ($show_tickets && $booking_url !== '') {
        $actions[] = [
            'url' => $booking_url,
            'label' => $tickets_label,
            'variant' => 'primary',
        ];
    }

    return $actions;
}

function iss_timeline_render_items_list($items, $opts = []) {
    $opts = is_array($opts) ? $opts : [];
    $yearGrouping = array_key_exists('yearGrouping', $opts) ? (bool) $opts['yearGrouping'] : true;
    $order = isset($opts['order']) ? strtoupper((string) $opts['order']) : 'ASC';
    if (!in_array($order, ['ASC', 'DESC'], true)) $order = 'ASC';

    if (empty($items)) {
        return '<p class="iss-timeline__empty">' . esc_html__('Keine Einträge gefunden.', 'iss-timeline') . '</p>';
    }

    $rows = array_map(function ($post) {
        $id = ($post instanceof WP_Post) ? $post->ID : (int) $post;
        return iss_timeline_prepare_item($id);
    }, $items);

    $groups = [];
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        $key = $yearGrouping ? ($row['year'] ?? '—') : 'all';
        if (!isset($groups[$key])) $groups[$key] = [];
        $groups[$key][] = $row;
    }

    if ($yearGrouping) {
        if ($order === 'DESC') {
            krsort($groups, SORT_NUMERIC);
        } else {
            ksort($groups, SORT_NUMERIC);
        }
    }

    $out = '';
    foreach ($groups as $year => $groupRows) {
        if ($yearGrouping) {
            $out .= '<div class="iss-timeline__year">';
            $out .= '<h3 class="iss-timeline__year-label">' . esc_html((string) $year) . '</h3>';
        }

        foreach ($groupRows as $row) {
            $out .= '<article class="iss-timeline__item">';
            $out .= '<div class="iss-timeline__date">';
            $out .= '<div class="iss-timeline__day">' . esc_html((string) ($row['day_label'] ?? '')) . '</div>';
            if (!empty($row['time_label'])) {
                $out .= '<div class="iss-timeline__time">' . esc_html((string) $row['time_label']) . '</div>';
            }
            $out .= '</div>';
            $out .= '<div class="iss-timeline__content">';
            $out .= '<h4 class="iss-timeline__title">' . esc_html((string) ($row['title'] ?? '')) . '</h4>';
            if (!empty($row['summary'])) {
                $out .= '<div class="iss-timeline__summary">' . esc_html((string) $row['summary']) . '</div>';
            } elseif (!empty($row['type'])) {
                $out .= '<div class="iss-timeline__summary">' . esc_html((string) $row['type']) . '</div>';
            }

            $actions = iss_timeline_build_actions($row, $opts);
            if (!empty($actions)) {
                $out .= '<div class="iss-timeline__actions">';
                foreach ($actions as $action) {
                    if (!is_array($action) || empty($action['url'])) continue;
                    $variant = isset($action['variant']) ? sanitize_key((string) $action['variant']) : 'secondary';
                    if (!in_array($variant, ['secondary', 'primary'], true)) {
                        $variant = 'secondary';
                    }
                    $out .= '<a class="iss-timeline__btn iss-timeline__btn--' . esc_attr($variant) . '" href="'
                        . esc_url((string) $action['url']) . '">'
                        . esc_html((string) ($action['label'] ?? '')) . '</a>';
                }
                $out .= '</div>';
            }
            $out .= '</div></article>';
        }

        if ($yearGrouping) {
            $out .= '</div>';
        }
    }

    return $out;
}

function iss_timeline_render($attributes = [], $content = '', $block = null) {
    if (function_exists('is_saas_enqueue_timeline_assets')) {
        is_saas_enqueue_timeline_assets();
    }

    $attributes = is_array($attributes) ? $attributes : [];

    $title = isset($attributes['title']) ? sanitize_text_field((string) $attributes['title']) : '';
    $kicker = isset($attributes['kicker']) ? sanitize_text_field((string) $attributes['kicker']) : '';
    $show_title = !array_key_exists('showTitle', $attributes) || (bool) $attributes['showTitle'];
    $show_kicker = !empty($attributes['showKicker']) && (bool) $attributes['showKicker'];
    $intro = isset($attributes['intro']) ? sanitize_textarea_field((string) $attributes['intro']) : '';
    $limit = isset($attributes['limit']) ? (int) $attributes['limit'] : 50;
    $group = isset($attributes['group']) ? sanitize_text_field((string) $attributes['group']) : '';
    $yearGrouping = array_key_exists('yearGrouping', $attributes) ? (bool) $attributes['yearGrouping'] : true;

    $items = function_exists('iss_timeline_get_items_advanced')
        ? iss_timeline_get_items_advanced(['limit' => $limit, 'order' => 'ASC', 'group' => $group])
        : [];
    $render_opts = iss_timeline_build_render_options($attributes);

    $use_block_wrapper = function_exists('get_block_wrapper_attributes') && ($block instanceof WP_Block);
    $attrs = $use_block_wrapper
        ? get_block_wrapper_attributes(['class' => 'iss-timeline iss-container'])
        : 'class="iss-timeline iss-container"';

    $out = '<section ' . $attrs . '>';
    if (($show_kicker && $kicker !== '') || ($show_title && $title !== '') || $intro !== '') {
        $out .= '<header class="iss-timeline__intro">';
        if ($show_kicker && $kicker !== '') $out .= '<p class="iss-kicker iss-timeline__kicker">' . esc_html($kicker) . '</p>';
        if ($show_title && $title !== '') $out .= '<h2 class="iss-timeline__title-heading">' . esc_html($title) . '</h2>';
        if ($intro !== '') $out .= '<div class="iss-timeline__intro-text">' . wp_kses_post(wpautop(esc_html($intro))) . '</div>';
        $out .= '</header>';
    }
    $out .= iss_timeline_render_items_list($items, [
        'yearGrouping' => $yearGrouping,
        'order' => 'ASC',
        'showDetailsButton' => (bool) $render_opts['showDetailsButton'],
        'showRecommendButton' => (bool) $render_opts['showRecommendButton'],
        'showTicketsButton' => (bool) $render_opts['showTicketsButton'],
        'detailsButtonUrl' => (string) $render_opts['detailsButtonUrl'],
        'recommendButtonUrl' => (string) $render_opts['recommendButtonUrl'],
        'ticketsButtonUrl' => (string) $render_opts['ticketsButtonUrl'],
        'detailsButtonText' => (string) $render_opts['detailsButtonText'],
        'recommendButtonText' => (string) $render_opts['recommendButtonText'],
        'ticketsButtonText' => (string) $render_opts['ticketsButtonText'],
    ]);
    $out .= iss_timeline_render_bottom_button($render_opts);
    $out .= '</section>';
    return $out;
}

function iss_timeline_render_latest($attributes = [], $content = '', $block = null) {
    if (function_exists('is_saas_enqueue_timeline_assets')) {
        is_saas_enqueue_timeline_assets();
    }

    $attributes = is_array($attributes) ? $attributes : [];

    $title = isset($attributes['title']) ? sanitize_text_field((string) $attributes['title']) : '';
    $kicker = isset($attributes['kicker']) ? sanitize_text_field((string) $attributes['kicker']) : '';
    $show_title = !array_key_exists('showTitle', $attributes) || (bool) $attributes['showTitle'];
    $show_kicker = !empty($attributes['showKicker']) && (bool) $attributes['showKicker'];
    $intro = isset($attributes['intro']) ? sanitize_textarea_field((string) $attributes['intro']) : '';
    $group = isset($attributes['group']) ? sanitize_text_field((string) $attributes['group']) : '';

    $items = function_exists('iss_timeline_get_items_advanced')
        ? iss_timeline_get_items_advanced([
            'range' => 'future',
            'order' => 'ASC',
            'limit' => 4,
            'group' => $group,
        ])
        : [];
    $render_opts = iss_timeline_build_render_options($attributes);

    $use_block_wrapper = function_exists('get_block_wrapper_attributes') && ($block instanceof WP_Block);
    $attrs = $use_block_wrapper
        ? get_block_wrapper_attributes(['class' => 'iss-timeline-latest iss-container'])
        : 'class="iss-timeline-latest iss-container"';

    $out = '<section ' . $attrs . '>';
    if (($show_kicker && $kicker !== '') || ($show_title && $title !== '') || $intro !== '') {
        $out .= '<header class="iss-timeline__intro">';
        if ($show_kicker && $kicker !== '') $out .= '<p class="iss-kicker iss-timeline__kicker">' . esc_html($kicker) . '</p>';
        if ($show_title && $title !== '') $out .= '<h2 class="iss-timeline__title-heading">' . esc_html($title) . '</h2>';
        if ($intro !== '') $out .= '<div class="iss-timeline__intro-text">' . wp_kses_post(wpautop(esc_html($intro))) . '</div>';
        $out .= '</header>';
    }
    $out .= '<div class="iss-timeline iss-timeline--latest">';
    $out .= iss_timeline_render_items_list($items, [
        'yearGrouping' => false,
        'order' => 'ASC',
        'showDetailsButton' => (bool) $render_opts['showDetailsButton'],
        'showRecommendButton' => (bool) $render_opts['showRecommendButton'],
        'showTicketsButton' => (bool) $render_opts['showTicketsButton'],
        'detailsButtonUrl' => (string) $render_opts['detailsButtonUrl'],
        'recommendButtonUrl' => (string) $render_opts['recommendButtonUrl'],
        'ticketsButtonUrl' => (string) $render_opts['ticketsButtonUrl'],
        'detailsButtonText' => (string) $render_opts['detailsButtonText'],
        'recommendButtonText' => (string) $render_opts['recommendButtonText'],
        'ticketsButtonText' => (string) $render_opts['ticketsButtonText'],
    ]);
    $out .= iss_timeline_render_bottom_button($render_opts);
    $out .= '</div></section>';
    return $out;
}

function iss_timeline_format_month_label($ym) {
    if (!function_exists('iss_timeline_month_to_range')) return $ym;
    $r = iss_timeline_month_to_range($ym);
    if (!is_array($r)) return $ym;
    try {
        $dt = new DateTimeImmutable($r['start'], wp_timezone());
        return wp_date('F Y', $dt->getTimestamp());
    } catch (Throwable $e) {
        return $ym;
    }
}

function iss_timeline_collect_future_month_options($horizon_months = 12) {
    $horizon_months = (int) $horizon_months;
    if ($horizon_months <= 0) $horizon_months = 12;
    if ($horizon_months > 36) $horizon_months = 36;

    $now = new DateTimeImmutable('now', wp_timezone());
    $months = [];
    for ($i = 0; $i <= $horizon_months; $i++) {
        $ym = $now->modify('first day of this month')->modify('+' . $i . ' months')->format('Y-m');
        $months[] = $ym;
    }
    return $months;
}

function iss_timeline_render_sections($attributes = [], $content = '', $block = null) {
    if (function_exists('is_saas_enqueue_timeline_assets')) {
        is_saas_enqueue_timeline_assets();
    }

    $attributes = is_array($attributes) ? $attributes : [];

    $title = isset($attributes['title']) ? sanitize_text_field((string) $attributes['title']) : '';
    $intro = isset($attributes['intro']) ? sanitize_textarea_field((string) $attributes['intro']) : '';

    $next_title = isset($attributes['nextTitle']) ? sanitize_text_field((string) $attributes['nextTitle']) : 'Was kommt als Nächstes';
    $next_limit = isset($attributes['nextLimit']) ? (int) $attributes['nextLimit'] : 4;

    $monthly_title = isset($attributes['monthlyTitle']) ? sanitize_text_field((string) $attributes['monthlyTitle']) : 'Monatsübersicht';
    $monthly_limit = isset($attributes['monthlyLimit']) ? (int) $attributes['monthlyLimit'] : 80;

    $archive_title = isset($attributes['archiveTitle']) ? sanitize_text_field((string) $attributes['archiveTitle']) : 'Archiv';
    $archive_limit = isset($attributes['archiveLimit']) ? (int) $attributes['archiveLimit'] : 250;

    $group = isset($attributes['group']) ? sanitize_text_field((string) $attributes['group']) : '';

    // Monthly filters via GET.
    $type = isset($_GET['iss_tl_type']) ? sanitize_key((string) $_GET['iss_tl_type']) : 'all';
    if (!in_array($type, ['all', 'fuehrungen', 'veranstaltungen'], true)) $type = 'all';

    $month = isset($_GET['iss_tl_month']) ? preg_replace('/[^0-9\\-]/', '', (string) $_GET['iss_tl_month']) : '';
    $month = preg_match('/^\\d{4}-\\d{2}$/', $month) ? $month : '';
    if ($month === '') {
        $month = wp_date('Y-m', null, wp_timezone());
    }

    $use_block_wrapper = function_exists('get_block_wrapper_attributes') && ($block instanceof WP_Block);
    $attrs = $use_block_wrapper
        ? get_block_wrapper_attributes(['class' => 'iss-timeline-sections iss-container'])
        : 'class="iss-timeline-sections iss-container"';

    $section_heading_tag = ($title !== '') ? 'h3' : 'h2';

    $out = '<section ' . $attrs . '>';
    if ($title !== '' || $intro !== '') {
        $out .= '<header class="iss-timeline__intro">';
        if ($title !== '') $out .= '<h2 class="iss-timeline__title-heading">' . esc_html($title) . '</h2>';
        if ($intro !== '') $out .= '<div class="iss-timeline__intro-text">' . wp_kses_post(wpautop(esc_html($intro))) . '</div>';
        $out .= '</header>';
    }

    // 1) Next
    $out .= '<div class="iss-timeline__section iss-timeline__section--next">';
    $out .= '<' . $section_heading_tag . ' class="iss-timeline__section-title">' . esc_html($next_title) . '</' . $section_heading_tag . '>';

    $next_items = function_exists('iss_timeline_get_items_advanced')
        ? iss_timeline_get_items_advanced([
            'range' => 'future',
            'order' => 'ASC',
            'limit' => $next_limit,
            'group' => $group,
        ])
        : [];
    $out .= '<div class="iss-timeline iss-timeline--next">';
    $out .= iss_timeline_render_items_list($next_items, ['yearGrouping' => false, 'order' => 'ASC']);
    $out .= '</div></div>';

    // 2) Monthly
    $out .= '<div class="iss-timeline__section iss-timeline__section--monthly">';
    $out .= '<' . $section_heading_tag . ' class="iss-timeline__section-title">' . esc_html($monthly_title) . '</' . $section_heading_tag . '>';

    $months = iss_timeline_collect_future_month_options(18);
    $out .= '<form class="iss-timeline__filters" method="get">';
    foreach ($_GET as $k => $v) {
        $k = (string) $k;
        if (in_array($k, ['iss_tl_type', 'iss_tl_month'], true)) continue;
        if (is_array($v)) continue;
        $out .= '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr((string) $v) . '">';
    }
    $out .= '<label class="iss-timeline__filter"><span class="iss-timeline__filter-label">' . esc_html__('Typ', 'iss-timeline') . '</span>';
    $out .= '<select name="iss_tl_type">';
    $out .= '<option value="all"' . selected($type, 'all', false) . '>' . esc_html__('Alle', 'iss-timeline') . '</option>';
    $out .= '<option value="fuehrungen"' . selected($type, 'fuehrungen', false) . '>' . esc_html__('Führungen', 'iss-timeline') . '</option>';
    $out .= '<option value="veranstaltungen"' . selected($type, 'veranstaltungen', false) . '>' . esc_html__('Veranstaltungen', 'iss-timeline') . '</option>';
    $out .= '</select></label>';

    $out .= '<label class="iss-timeline__filter"><span class="iss-timeline__filter-label">' . esc_html__('Monat', 'iss-timeline') . '</span>';
    $out .= '<select name="iss_tl_month">';
    foreach ($months as $ym) {
        $out .= '<option value="' . esc_attr($ym) . '"' . selected($month, $ym, false) . '>' . esc_html(iss_timeline_format_month_label($ym)) . '</option>';
    }
    $out .= '</select></label>';

    $out .= '<button type="submit" class="iss-timeline__apply">' . esc_html__('Anwenden', 'iss-timeline') . '</button>';
    $out .= '</form>';

    $monthly_items = function_exists('iss_timeline_get_items_advanced')
        ? iss_timeline_get_items_advanced([
            'range' => 'future',
            'order' => 'ASC',
            'limit' => $monthly_limit,
            'month' => $month,
            'type' => $type,
            'group' => $group,
        ])
        : [];
    $out .= '<div class="iss-timeline iss-timeline--monthly">';
    $out .= '<h3 class="iss-timeline__month-label">' . esc_html(iss_timeline_format_month_label($month)) . '</h3>';
    $out .= iss_timeline_render_items_list($monthly_items, ['yearGrouping' => false, 'order' => 'ASC']);
    $out .= '</div></div>';

    // 3) Archive
    $out .= '<div class="iss-timeline__section iss-timeline__section--archive">';
    $out .= '<' . $section_heading_tag . ' class="iss-timeline__section-title">' . esc_html($archive_title) . '</' . $section_heading_tag . '>';

    $archive_items = function_exists('iss_timeline_get_items_advanced')
        ? iss_timeline_get_items_advanced([
            'range' => 'past',
            'order' => 'DESC',
            'limit' => $archive_limit,
            'group' => $group,
        ])
        : [];
    $out .= '<div class="iss-timeline iss-timeline--archive">';
    $out .= iss_timeline_render_items_list($archive_items, ['yearGrouping' => true, 'order' => 'DESC']);
    $out .= '</div></div>';

    $out .= '</section>';
    return $out;
}
