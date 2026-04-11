<?php

if (!defined('ABSPATH')) exit;

define('ISS_CALENDAR_TAG_META_KEY', 'calendar_tag');

/**
 * Register calendar tag meta on all public content types (except attachments and our CPT).
 */
add_action('init', function () {
    $types = get_post_types(['public' => true], 'names');
    if (!is_array($types)) {
        $types = [];
    }

    $types = array_values(array_filter($types, function ($t) {
        return $t && $t !== 'attachment' && $t !== ISS_CALENDAR_ITEM_POST_TYPE;
    }));

    foreach ($types as $post_type) {
        register_post_meta($post_type, ISS_CALENDAR_TAG_META_KEY, [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => static function ($value) {
                $value = strtoupper(sanitize_text_field((string) $value));
                return $value;
            },
            'auth_callback' => static function () {
                return current_user_can('edit_posts');
            },
        ]);
    }
}, 6);

add_action('add_meta_boxes', function () {
    $types = get_post_types(['public' => true], 'names');
    if (!is_array($types)) {
        $types = [];
    }

    foreach ($types as $post_type) {
        if (!$post_type || $post_type === 'attachment' || $post_type === ISS_CALENDAR_ITEM_POST_TYPE) {
            continue;
        }

        add_meta_box(
            'iss_calendar_tag',
            'Tour Calendar',
            'iss_calendar_render_tag_metabox',
            $post_type,
            'side',
            'default'
        );
    }
});

function iss_calendar_render_tag_metabox($post) {
    if (!$post instanceof WP_Post) return;

    $value = (string) get_post_meta($post->ID, ISS_CALENDAR_TAG_META_KEY, true);
    $value = strtoupper(trim($value));

    wp_nonce_field('iss_calendar_tag_save', 'iss_calendar_tag_nonce');

    echo '<p><label for="iss_calendar_tag_field"><strong>Calendar Tag</strong></label></p>';
    echo '<p><input id="iss_calendar_tag_field" name="iss_calendar_tag_field" type="text" value="' . esc_attr($value) . '" class="widefat" placeholder="ELEKTRO" /></p>';
    echo '<p class="description">Use the same value as the SuperSaaS slot <code>location</code> (recommended), or legacy <code>[TAG]</code> prefix in titles.</p>';

    if (function_exists('iss_calendar_get_source_map_entry') && $value !== '') {
        $entry = iss_calendar_get_source_map_entry($value);
        if (is_array($entry) && !empty($entry['source_post_id'])) {
            $mapped_id = (int) $entry['source_post_id'];
            $note = ($mapped_id === (int) $post->ID)
                ? 'Mapped to this post.'
                : ('Currently mapped to post #' . $mapped_id . '.');
            echo '<p class="description">' . esc_html($note) . '</p>';
        }
    }

    $tools_url = admin_url('tools.php?page=iss-calendar-sync');
    echo '<p><a href="' . esc_url($tools_url) . '">Open Sync & Source Map</a></p>';
}

add_action('save_post', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    if (!isset($_POST['iss_calendar_tag_nonce']) || !wp_verify_nonce((string) $_POST['iss_calendar_tag_nonce'], 'iss_calendar_tag_save')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $post_type = get_post_type($post_id);
    if (!$post_type || $post_type === 'attachment' || $post_type === ISS_CALENDAR_ITEM_POST_TYPE) {
        return;
    }

    $raw = isset($_POST['iss_calendar_tag_field']) ? (string) $_POST['iss_calendar_tag_field'] : '';
    $tag = strtoupper(sanitize_text_field($raw));

    if ($tag === '') {
        delete_post_meta($post_id, ISS_CALENDAR_TAG_META_KEY);
        return;
    }

    update_post_meta($post_id, ISS_CALENDAR_TAG_META_KEY, $tag);

    // Explicit editor input should immediately update the tag→post mapping.
    if (function_exists('iss_calendar_remember_source_mapping')) {
        iss_calendar_remember_source_mapping($tag, '', (int) $post_id, (string) $post_type);
    }
}, 10, 1);

