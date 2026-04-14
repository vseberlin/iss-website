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

    $source_post_id = (int) get_post_meta($item_id, 'source_post_id', true);

    $ts = null;
    try {
        if ($sort_date !== '') {
            $ts = (new DateTimeImmutable($sort_date, wp_timezone()))->getTimestamp();
        }
    } catch (Throwable $e) {
        $ts = null;
    }

    $date_label = $ts ? wp_date('j. F Y', $ts) : $sort_date;

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
        'end_raw' => $event_end,
        'type' => $item_type,
        'summary' => $summary,
        'cta_mode' => $cta_mode,
        'cta_url' => $cta_url,
        'cta_label' => $cta_label !== '' ? $cta_label : __('Mehr erfahren', 'iss-timeline'),
        'source_post_id' => $source_post_id,
        'year' => $ts ? (int) wp_date('Y', $ts) : null,
    ];
}

function iss_timeline_build_action($row) {
    if (!is_array($row)) return null;
    $mode = isset($row['cta_mode']) ? sanitize_key((string) $row['cta_mode']) : '';
    $source_post_id = isset($row['source_post_id']) ? (int) $row['source_post_id'] : 0;

    $label = isset($row['cta_label']) ? trim((string) $row['cta_label']) : '';
    if ($label === '') $label = __('Mehr erfahren', 'iss-timeline');

    if ($mode === 'details' && $source_post_id > 0) {
        $url = get_permalink($source_post_id);
        return $url ? ['url' => $url, 'label' => $label] : null;
    }

    if ($mode === 'booking' && $source_post_id > 0 && function_exists('iss_calendar_get_next_item_for_post')) {
        $next = iss_calendar_get_next_item_for_post($source_post_id);
        if ($next instanceof WP_Post) {
            $booking = (string) get_post_meta($next->ID, 'booking_url', true);
            return $booking !== '' ? ['url' => $booking, 'label' => $label] : null;
        }
        return null;
    }

    if ($mode === 'external') {
        $url = isset($row['cta_url']) ? (string) $row['cta_url'] : '';
        return $url !== '' ? ['url' => $url, 'label' => $label] : null;
    }

    // auto/default
    if ($source_post_id > 0) {
        $url = get_permalink($source_post_id);
        if ($url) return ['url' => $url, 'label' => $label];
    }
    $url = isset($row['cta_url']) ? (string) $row['cta_url'] : '';
    return $url !== '' ? ['url' => $url, 'label' => $label] : null;
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
            $out .= '<div class="iss-timeline__marker" aria-hidden="true"></div>';
            $out .= '<div class="iss-timeline__date">' . esc_html((string) ($row['date_label'] ?? '')) . '</div>';
            $out .= '<div class="iss-timeline__content">';
            if (!empty($row['type'])) {
                $out .= '<div class="iss-timeline__type">' . esc_html((string) $row['type']) . '</div>';
            }
            $out .= '<h4 class="iss-timeline__title">' . esc_html((string) ($row['title'] ?? '')) . '</h4>';
            if (!empty($row['summary'])) {
                $out .= '<div class="iss-timeline__summary">' . esc_html((string) $row['summary']) . '</div>';
            }

            $action = iss_timeline_build_action($row);
            if (is_array($action) && !empty($action['url'])) {
                $out .= '<p class="iss-timeline__actions">';
                $out .= '<a class="iss-timeline__link" href="' . esc_url((string) $action['url']) . '">' . esc_html((string) ($action['label'] ?? '')) . '</a>';
                $out .= '</p>';
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
    $attributes = is_array($attributes) ? $attributes : [];

    $title = isset($attributes['title']) ? sanitize_text_field((string) $attributes['title']) : '';
    $intro = isset($attributes['intro']) ? sanitize_textarea_field((string) $attributes['intro']) : '';
    $limit = isset($attributes['limit']) ? (int) $attributes['limit'] : 50;
    $group = isset($attributes['group']) ? sanitize_text_field((string) $attributes['group']) : '';
    $yearGrouping = array_key_exists('yearGrouping', $attributes) ? (bool) $attributes['yearGrouping'] : true;

    $items = function_exists('iss_timeline_get_items_advanced')
        ? iss_timeline_get_items_advanced(['limit' => $limit, 'order' => 'ASC', 'group' => $group])
        : [];

    $use_block_wrapper = function_exists('get_block_wrapper_attributes') && ($block instanceof WP_Block);
    $attrs = $use_block_wrapper
        ? get_block_wrapper_attributes(['class' => 'iss-timeline'])
        : 'class="iss-timeline"';

    $out = '<section ' . $attrs . '>';
    if ($title !== '' || $intro !== '') {
        $out .= '<header class="iss-timeline__intro">';
        if ($title !== '') $out .= '<h2 class="iss-timeline__title-heading">' . esc_html($title) . '</h2>';
        if ($intro !== '') $out .= '<div class="iss-timeline__intro-text">' . wp_kses_post(wpautop(esc_html($intro))) . '</div>';
        $out .= '</header>';
    }
    $out .= iss_timeline_render_items_list($items, ['yearGrouping' => $yearGrouping, 'order' => 'ASC']);
    $out .= '</section>';
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
        ? get_block_wrapper_attributes(['class' => 'iss-timeline-sections'])
        : 'class="iss-timeline-sections"';

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
