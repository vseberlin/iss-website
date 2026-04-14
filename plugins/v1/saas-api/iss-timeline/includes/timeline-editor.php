<?php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', function () {
    $post_type = function_exists('iss_timeline_get_post_type') ? iss_timeline_get_post_type() : '';
    if (!$post_type) return;

    add_meta_box(
        'iss_timeline_public_fields',
        'Timeline (Public)',
        'iss_timeline_render_public_metabox',
        $post_type,
        'normal',
        'default'
    );
}, 20);

function iss_timeline_render_public_metabox($post) {
    if (!$post instanceof WP_Post) return;

    $public_title = (string) get_post_meta($post->ID, 'public_title', true);
    $public_summary = (string) get_post_meta($post->ID, 'public_summary', true);
    $is_visible = get_post_meta($post->ID, 'is_visible', true);
    $is_visible = ($is_visible === '' || $is_visible === null) ? 1 : (int) $is_visible;

    $item_type = (string) get_post_meta($post->ID, 'item_type', true);
    $cta_mode = (string) get_post_meta($post->ID, 'cta_mode', true);
    $cta_url = (string) get_post_meta($post->ID, 'cta_url', true);
    $cta_label = (string) get_post_meta($post->ID, 'cta_label', true);

    wp_nonce_field('iss_timeline_save', 'iss_timeline_nonce');

    echo '<p><label><input type="checkbox" name="iss_timeline_is_visible" value="1" ' . checked($is_visible, 1, false) . '> Sichtbar in Timeline</label></p>';

    echo '<p><label for="iss_timeline_public_title"><strong>Öffentlicher Titel (optional)</strong></label></p>';
    echo '<p><input id="iss_timeline_public_title" name="iss_timeline_public_title" type="text" class="widefat" value="' . esc_attr($public_title) . '" placeholder="Titel für die Timeline" /></p>';

    echo '<p><label for="iss_timeline_public_summary"><strong>Öffentliche Kurzbeschreibung (optional)</strong></label></p>';
    echo '<p><textarea id="iss_timeline_public_summary" name="iss_timeline_public_summary" class="widefat" rows="4" placeholder="1–2 Sätze als Teaser">' . esc_textarea($public_summary) . '</textarea></p>';

    echo '<hr />';

    echo '<p><label for="iss_timeline_item_type"><strong>Typ</strong></label></p>';
    echo '<p><select id="iss_timeline_item_type" name="iss_timeline_item_type" class="widefat">';
    $types = [
        '' => '—',
        'tour' => 'Führung',
        'event' => 'Veranstaltung',
    ];
    foreach ($types as $k => $label) {
        echo '<option value="' . esc_attr($k) . '"' . selected($item_type, $k, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></p>';

    echo '<p><label for="iss_timeline_cta_mode"><strong>Link/CTA</strong></label></p>';
    echo '<p><select id="iss_timeline_cta_mode" name="iss_timeline_cta_mode" class="widefat">';
    $modes = [
        'auto' => 'Auto (Details →, sonst extern)',
        'details' => 'Details (verknüpfter Beitrag)',
        'booking' => 'Buchung (nächster Termin)',
        'external' => 'Extern (URL)',
    ];
    foreach ($modes as $k => $label) {
        echo '<option value="' . esc_attr($k) . '"' . selected($cta_mode ?: 'auto', $k, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></p>';

    echo '<p><label for="iss_timeline_cta_url"><strong>Externe URL (optional)</strong></label></p>';
    echo '<p><input id="iss_timeline_cta_url" name="iss_timeline_cta_url" type="url" class="widefat" value="' . esc_attr($cta_url) . '" placeholder="https://…" /></p>';

    echo '<p><label for="iss_timeline_cta_label"><strong>Link-Label (optional)</strong></label></p>';
    echo '<p><input id="iss_timeline_cta_label" name="iss_timeline_cta_label" type="text" class="widefat" value="' . esc_attr($cta_label) . '" placeholder="Mehr erfahren" /></p>';

    echo '<p class="description">Hinweis: Wenn ein verknüpfter Beitrag existiert (<code>source_post_id</code>), verwendet die Timeline standardmäßig dessen Titel/Teaser als Fallback.</p>';
}

add_action('save_post', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    if (!isset($_POST['iss_timeline_nonce']) || !wp_verify_nonce((string) $_POST['iss_timeline_nonce'], 'iss_timeline_save')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) return;

    $post_type = get_post_type($post_id);
    $expected = function_exists('iss_timeline_get_post_type') ? iss_timeline_get_post_type() : '';
    if (!$expected || $post_type !== $expected) return;

    $visible = isset($_POST['iss_timeline_is_visible']) ? 1 : 0;
    update_post_meta($post_id, 'is_visible', $visible);

    $public_title = isset($_POST['iss_timeline_public_title']) ? sanitize_text_field((string) $_POST['iss_timeline_public_title']) : '';
    $public_summary = isset($_POST['iss_timeline_public_summary']) ? sanitize_textarea_field((string) $_POST['iss_timeline_public_summary']) : '';
    update_post_meta($post_id, 'public_title', $public_title);
    update_post_meta($post_id, 'public_summary', $public_summary);

    $item_type = isset($_POST['iss_timeline_item_type']) ? sanitize_key((string) $_POST['iss_timeline_item_type']) : '';
    if ($item_type !== '') {
        update_post_meta($post_id, 'item_type', $item_type);
    }

    $cta_mode = isset($_POST['iss_timeline_cta_mode']) ? sanitize_key((string) $_POST['iss_timeline_cta_mode']) : 'auto';
    update_post_meta($post_id, 'cta_mode', $cta_mode);

    $cta_url = isset($_POST['iss_timeline_cta_url']) ? esc_url_raw((string) $_POST['iss_timeline_cta_url']) : '';
    update_post_meta($post_id, 'cta_url', $cta_url);

    $cta_label = isset($_POST['iss_timeline_cta_label']) ? sanitize_text_field((string) $_POST['iss_timeline_cta_label']) : '';
    update_post_meta($post_id, 'cta_label', $cta_label);
});

add_filter('manage_' . ISS_CALENDAR_ITEM_POST_TYPE . '_posts_columns', function ($cols) {
    // Insert a few public-facing columns for easier editorial work.
    $out = [];
    foreach ($cols as $key => $label) {
        $out[$key] = $label;
        if ($key === 'title') {
            $out['public_title'] = 'Public Title';
            $out['is_visible'] = 'Visible';
        }
    }
    return $out;
}, 20);

add_action('manage_' . ISS_CALENDAR_ITEM_POST_TYPE . '_posts_custom_column', function ($col, $post_id) {
    if ($col === 'public_title') {
        $t = (string) get_post_meta($post_id, 'public_title', true);
        echo $t !== '' ? esc_html($t) : '—';
        return;
    }
    if ($col === 'is_visible') {
        $v = get_post_meta($post_id, 'is_visible', true);
        if ($v === '' || $v === null) {
            $v = (int) get_post_meta($post_id, 'is_public', true);
        }
        echo ((int) $v === 1) ? '✓' : '—';
        return;
    }
}, 20, 2);

