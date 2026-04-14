<?php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', function () {
    add_meta_box(
        'iss_calendar_linked_content',
        __('Verknüpfter Inhalt', 'saas-api'),
        'iss_calendar_render_linked_content_metabox',
        ISS_CALENDAR_ITEM_POST_TYPE,
        'side',
        'high'
    );
}, 15);

function iss_calendar_render_linked_content_metabox($post) {
    if (!($post instanceof WP_Post)) return;

    $current_id = (int) get_post_meta($post->ID, 'source_post_id', true);
    $current_type = (string) get_post_meta($post->ID, 'source_post_type', true);
    $tag = strtoupper((string) get_post_meta($post->ID, 'calendar_tag', true));
    $saas_title = (string) get_post_meta($post->ID, 'supersaas_title', true);

    $posts = iss_calendar_get_linkable_posts();

    wp_nonce_field('iss_calendar_linked_content_save', 'iss_calendar_linked_content_nonce');

    echo '<p><label for="iss_calendar_source_post_id"><strong>' . esc_html__('Inhalt auswählen', 'saas-api') . '</strong></label></p>';
    echo '<p><select id="iss_calendar_source_post_id" name="iss_calendar_source_post_id" class="widefat">';
    echo '<option value="0">— ' . esc_html__('Nicht verknüpft', 'saas-api') . ' —</option>';
    foreach ($posts as $item) {
        $pid = (int) $item->ID;
        $ptype_obj = get_post_type_object((string) get_post_type($pid));
        $ptype_label = $ptype_obj && !empty($ptype_obj->labels->singular_name) ? (string) $ptype_obj->labels->singular_name : (string) get_post_type($pid);
        $label = '#' . $pid . ' ' . (get_the_title($pid) ?: __('(ohne Titel)', 'saas-api')) . ' [' . $ptype_label . ']';
        printf(
            '<option value="%1$d"%2$s>%3$s</option>',
            $pid,
            selected($current_id, $pid, false),
            esc_html($label)
        );
    }
    echo '</select></p>';
    echo '<p class="description">' . esc_html__('Verbindet diesen Termin mit einer Seite oder einem Beitrag. Diese Verknüpfung steuert Details/Buttons in der Timeline.', 'saas-api') . '</p>';

    $suggest = ['post_id' => 0, 'post_type' => '', 'ambiguous' => false];
    if ($tag !== '' && function_exists('iss_calendar_resolve_source_by_tag')) {
        $suggest = iss_calendar_resolve_source_by_tag($tag);
    }
    if ((int) ($suggest['post_id'] ?? 0) <= 0 && $saas_title !== '' && function_exists('iss_calendar_resolve_source_by_saas_title')) {
        $suggest = iss_calendar_resolve_source_by_saas_title($saas_title);
    }

    if ((int) ($suggest['post_id'] ?? 0) > 0) {
        $sid = (int) $suggest['post_id'];
        $stitle = get_the_title($sid);
        echo '<p class="description">' . esc_html__('Vorschlag:', 'saas-api') . ' ' . esc_html('#' . $sid . ' ' . $stitle) . '</p>';
    } elseif (!empty($suggest['ambiguous'])) {
        echo '<p class="description">' . esc_html__('Vorschlag uneindeutig: Mehrere Inhalte passen zu diesem Eintrag.', 'saas-api') . '</p>';
    } else {
        echo '<p class="description">' . esc_html__('Kein automatischer Vorschlag gefunden.', 'saas-api') . '</p>';
    }

    if ($current_id > 0) {
        $edit = get_edit_post_link($current_id);
        $txt = '#' . $current_id . ' ' . (get_the_title($current_id) ?: '');
        if ($edit) {
            echo '<p class="description"><a href="' . esc_url($edit) . '">' . esc_html($txt) . '</a></p>';
        } else {
            echo '<p class="description">' . esc_html($txt) . '</p>';
        }
    } elseif ($current_type !== '') {
        $ptype_obj = get_post_type_object($current_type);
        $ptype_label = ($ptype_obj && !empty($ptype_obj->labels->singular_name))
            ? (string) $ptype_obj->labels->singular_name
            : $current_type;
        echo '<p class="description">' . esc_html__('Inhaltstyp:', 'saas-api') . ' ' . esc_html($ptype_label) . '</p>';
    }
}

add_action('save_post_' . ISS_CALENDAR_ITEM_POST_TYPE, function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['iss_calendar_linked_content_nonce']) || !wp_verify_nonce((string) $_POST['iss_calendar_linked_content_nonce'], 'iss_calendar_linked_content_save')) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) return;

    $source_post_id = isset($_POST['iss_calendar_source_post_id']) ? (int) $_POST['iss_calendar_source_post_id'] : 0;
    if ($source_post_id < 0) $source_post_id = 0;

    $source_post_type = '';
    if ($source_post_id > 0) {
        $source_post_type = (string) get_post_type($source_post_id);
        if ($source_post_type === 'attachment') {
            return;
        }
    }

    update_post_meta($post_id, 'source_post_id', $source_post_id);
    update_post_meta($post_id, 'source_post_type', sanitize_key($source_post_type));
}, 12);

/**
 * Bulk edit: set `source_post_id` and (optionally) apply to all recurring items.
 *
 * Recurrence logic uses `series_key`, which is derived from the visible
 * SuperSaaS title (stored in `supersaas_title`).
 */

add_action('bulk_edit_custom_box', function ($column_name, $post_type) {
    if ($post_type !== ISS_CALENDAR_ITEM_POST_TYPE) return;
    if ($column_name !== 'source_post_id') return;

    wp_nonce_field('iss_calendar_bulk_edit', 'iss_calendar_bulk_nonce');
    $posts = iss_calendar_get_linkable_posts(350);

    echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col">';
    echo '<div class="inline-edit-group">';
    echo '<label class="alignleft">';
    echo '<span class="title">' . esc_html__('Verknüpfter Inhalt', 'saas-api') . '</span>';
    echo '<span class="input-text-wrap"><select name="iss_calendar_bulk_source_post_id" class="iss-calendar-bulk-source-select">';
    echo '<option value="">' . esc_html__('Nicht ändern', 'saas-api') . '</option>';
    echo '<option value="0">' . esc_html__('Verknüpfung entfernen', 'saas-api') . '</option>';
    foreach ($posts as $item) {
        $pid = (int) $item->ID;
        $ptype_obj = get_post_type_object((string) get_post_type($pid));
        $ptype_label = $ptype_obj && !empty($ptype_obj->labels->singular_name) ? (string) $ptype_obj->labels->singular_name : (string) get_post_type($pid);
        $label = '#' . $pid . ' ' . (get_the_title($pid) ?: __('(ohne Titel)', 'saas-api')) . ' [' . $ptype_label . ']';
        echo '<option value="' . esc_attr((string) $pid) . '">' . esc_html($label) . '</option>';
    }
    echo '</select></span>';
    echo '</label>';
    echo '<label class="alignleft" style="margin-left:12px;">';
    echo '<span class="title">' . esc_html__('Übernahme', 'saas-api') . '</span>';
    echo '<span class="input-text-wrap"><label><input type="checkbox" name="iss_calendar_bulk_apply_series" value="1" /> ' . esc_html__('auf gleich benannte Terminserie anwenden', 'saas-api') . '</label></span>';
    echo '</label>';
    echo '</div>';
    echo '<p class="description" style="margin:8px 0 0;">' .
        esc_html__('Wählen Sie einen Inhalt aus. Lassen Sie „Nicht ändern“, wenn bestehende Verknüpfungen unverändert bleiben sollen.', 'saas-api') .
        '</p>';
    echo '</div></fieldset>';
}, 10, 2);

add_action('save_post_' . ISS_CALENDAR_ITEM_POST_TYPE, function ($post_id) {
    // Bulk edit uses AJAX inline-save.
    if (!defined('DOING_AJAX') || !DOING_AJAX) return;
    if (!isset($_POST['action']) || (string) $_POST['action'] !== 'inline-save') return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (!isset($_POST['iss_calendar_bulk_nonce']) || !wp_verify_nonce((string) $_POST['iss_calendar_bulk_nonce'], 'iss_calendar_bulk_edit')) {
        return;
    }

    if (!array_key_exists('iss_calendar_bulk_source_post_id', $_POST)) {
        return; // field not present => not our bulk edit
    }

    $raw = trim((string) wp_unslash($_POST['iss_calendar_bulk_source_post_id']));
    if ($raw === '') {
        return; // empty means "don't change"
    }

    $source_post_id = (int) $raw;
    if ($source_post_id < 0) $source_post_id = 0;

    $source_post_type = '';
    if ($source_post_id > 0) {
        $source_post_type = (string) get_post_type($source_post_id);
        if ($source_post_type === 'attachment') {
            // Never link to attachments.
            return;
        }
    }

    update_post_meta($post_id, 'source_post_id', $source_post_id);
    update_post_meta($post_id, 'source_post_type', sanitize_key($source_post_type));

    $apply_series = isset($_POST['iss_calendar_bulk_apply_series']) && (string) $_POST['iss_calendar_bulk_apply_series'] === '1';
    if (!$apply_series) return;

    $series_key = (string) get_post_meta($post_id, 'series_key', true);
    if ($series_key === '') {
        $title = (string) get_post_meta($post_id, 'supersaas_title', true);
        if ($title === '') {
            $title = get_the_title($post_id);
        }
        $item_type = (string) get_post_meta($post_id, 'item_type', true);
        if (function_exists('iss_calendar_build_series_key')) {
            $series_key = iss_calendar_build_series_key($title, $item_type);
            if ($series_key !== '') {
                update_post_meta($post_id, 'series_key', $series_key);
            }
        }
    }

    if ($series_key === '') return;

    // Apply to all items with same series key (typically recurring weekly slots).
    $q = new WP_Query([
        'post_type' => ISS_CALENDAR_ITEM_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_query' => [
            [
                'key' => 'series_key',
                'value' => $series_key,
                'compare' => '=',
            ],
        ],
    ]);

    if (empty($q->posts)) return;

    foreach ($q->posts as $id) {
        $id = (int) $id;
        if ($id <= 0) continue;
        update_post_meta($id, 'source_post_id', $source_post_id);
        update_post_meta($id, 'source_post_type', sanitize_key($source_post_type));
    }
}, 20);

/**
 * Admin view: show upcoming items that still need linking.
 */
add_filter('views_edit-' . ISS_CALENDAR_ITEM_POST_TYPE, function ($views) {
    if (!is_array($views)) $views = [];

    $q = new WP_Query([
        'post_type' => ISS_CALENDAR_ITEM_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => 'sort_date',
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

    $count = (int) ($q->found_posts ?? 0);
    if ($count <= 0) return $views;

    $current = isset($_GET['iss_needs_linking']) && (string) $_GET['iss_needs_linking'] === '1';
    $url = add_query_arg('iss_needs_linking', '1', admin_url('edit.php?post_type=' . ISS_CALENDAR_ITEM_POST_TYPE));
    $label = sprintf(__('Zuordnung fehlt <span class="count">(%d)</span>', 'saas-api'), $count);
    $views['iss_needs_linking'] = '<a href="' . esc_url($url) . '"' . ($current ? ' class="current"' : '') . '>' . $label . '</a>';
    return $views;
});

function iss_calendar_get_linkable_posts($limit = 250) {
    $limit = (int) $limit;
    if ($limit <= 0) $limit = 250;

    return get_posts([
        'post_type' => function_exists('iss_calendar_get_source_post_types') ? iss_calendar_get_source_post_types() : ['page', 'post'],
        'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => $limit,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
}

add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) return;
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->id !== 'edit-' . ISS_CALENDAR_ITEM_POST_TYPE) return;

    if (!isset($_GET['iss_needs_linking']) || (string) $_GET['iss_needs_linking'] !== '1') return;

    $mq = (array) $query->get('meta_query');
    $mq[] = [
        'key' => 'sort_date',
        'value' => current_time('mysql'),
        'compare' => '>=',
        'type' => 'DATETIME',
    ];
    $mq[] = [
        'key' => 'source_post_id',
        'value' => 0,
        'compare' => '=',
        'type' => 'NUMERIC',
    ];
    $query->set('meta_query', $mq);
});
