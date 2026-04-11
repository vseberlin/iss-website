<?php

if (!defined('ABSPATH')) exit;

define('ISS_CALENDAR_TAG_META_KEY', 'calendar_tag');
define('ISS_CALENDAR_SAAS_TITLE_META_KEY', 'calendar_saas_title');

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

        register_post_meta($post_type, ISS_CALENDAR_SAAS_TITLE_META_KEY, [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => static function ($value) {
                return sanitize_text_field((string) $value);
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

add_action('admin_post_iss_calendar_set_tag', function () {
    if (!current_user_can('edit_posts')) {
        wp_die('Not allowed.');
    }

    check_admin_referer('iss_calendar_set_tag');

    $post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
    $tag = isset($_GET['tag']) ? strtoupper(sanitize_text_field((string) $_GET['tag'])) : '';
    $saas_title = isset($_GET['saas_title']) ? sanitize_text_field((string) $_GET['saas_title']) : '';

    if ($post_id <= 0 || ($tag === '' && $saas_title === '')) {
        wp_safe_redirect(wp_get_referer() ?: admin_url('edit.php'));
        exit;
    }

    if (!current_user_can('edit_post', $post_id)) {
        wp_die('Not allowed.');
    }

    if ($saas_title !== '') {
        update_post_meta($post_id, ISS_CALENDAR_SAAS_TITLE_META_KEY, $saas_title);
    }

    if ($tag === '' && function_exists('iss_calendar_generate_tag_from_title') && $saas_title !== '') {
        $tag = iss_calendar_generate_tag_from_title($saas_title);
    }

    if ($tag !== '') {
        update_post_meta($post_id, ISS_CALENDAR_TAG_META_KEY, $tag);
    }

    $post_type = get_post_type($post_id);
    if ($post_type && $post_type !== 'attachment' && $post_type !== ISS_CALENDAR_ITEM_POST_TYPE) {
        if (function_exists('iss_calendar_remember_source_mapping')) {
            iss_calendar_remember_source_mapping($tag, '', $post_id, $post_type);
        }
    }

    // Persist title mapping for this tag.
    if ($tag !== '' && $saas_title !== '') {
        $map = function_exists('iss_calendar_get_source_map') ? iss_calendar_get_source_map() : [];
        if (!is_array($map)) $map = [];
        $entry = isset($map[$tag]) && is_array($map[$tag]) ? $map[$tag] : [];
        $entry['source_post_id'] = $post_id;
        $entry['source_post_type'] = (string) $post_type;
        $entry['supersaas_title'] = $saas_title;
        $entry['last_seen_at'] = current_time('mysql');
        $map[$tag] = $entry;
        update_option(ISS_CALENDAR_SOURCE_MAP_OPTION, $map, false);
    }

    $redirect = get_edit_post_link($post_id, 'raw');
    if (!$redirect) {
        $redirect = admin_url('post.php?post=' . $post_id . '&action=edit');
    }
    $redirect = add_query_arg('iss_calendar_suggest', '1', $redirect);

    wp_safe_redirect($redirect);
    exit;
});

function iss_calendar_render_tag_metabox($post) {
    if (!$post instanceof WP_Post) return;

    $value = (string) get_post_meta($post->ID, ISS_CALENDAR_TAG_META_KEY, true);
    $value = strtoupper(trim($value));
    $saas_title_value = (string) get_post_meta($post->ID, ISS_CALENDAR_SAAS_TITLE_META_KEY, true);

    wp_nonce_field('iss_calendar_tag_save', 'iss_calendar_tag_nonce');

    echo '<p><label for="iss_calendar_tag_field"><strong>Calendar Tag</strong></label></p>';
    echo '<p><input id="iss_calendar_tag_field" name="iss_calendar_tag_field" type="text" value="' . esc_attr($value) . '" class="widefat" placeholder="ELEKTRO" /></p>';
    echo '<p><label for="iss_calendar_title_field"><strong>SuperSaaS Tour Title</strong></label></p>';
    echo '<p><input id="iss_calendar_title_field" name="iss_calendar_title_field" type="text" value="' . esc_attr($saas_title_value) . '" class="widefat" placeholder="Waldfriedhof Oberschöneweide" /></p>';
    echo '<p class="description">Recommended: put <code>Elektropolis Tour &lt;!-- TAG=ELEKTRO --&gt;</code> into the SuperSaaS slot <code>description</code> (keeps the tag invisible if HTML comments are not shown). Legacy: <code>[TAG]</code> prefix in slot titles.</p>';

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

    // Optional: show SuperSaaS suggestions (cached) on-demand.
    $show = isset($_GET['iss_calendar_suggest']) && (string) $_GET['iss_calendar_suggest'] === '1';
    $edit_url = get_edit_post_link($post->ID, 'raw');
    if (!$edit_url) {
        $edit_url = admin_url('post.php?post=' . (int) $post->ID . '&action=edit');
    }

    if (!$show) {
        echo '<p><a href="' . esc_url(add_query_arg('iss_calendar_suggest', '1', $edit_url)) . '">Load SuperSaaS suggestions</a></p>';
        return;
    }

    if (!function_exists('iss_calendar_supersaas_fetch_free_slots') || !function_exists('is_saas_get_settings')) {
        echo '<p class="description">SuperSaaS suggestions unavailable.</p>';
        return;
    }

    $slot_items = iss_calendar_supersaas_fetch_free_slots(is_saas_get_settings());
    if (is_wp_error($slot_items)) {
        echo '<p class="description">Could not load suggestions.</p>';
        return;
    }

    $tags = [];
    foreach ($slot_items as $slot) {
        if (!is_array($slot)) continue;
        if (!function_exists('iss_calendar_extract_supersaas_slot_tag')) continue;
        $t = iss_calendar_extract_supersaas_slot_tag($slot);
        if ($t !== '') {
            $tags[$t] = true;
        }
    }

    $tags = array_keys($tags);
    sort($tags);

    if (empty($tags)) {
        echo '<p class="description">No TAG markers detected in recent SuperSaaS slots.</p>';
        return;
    }

    // Also show titles and generate a tag suggestion for untagged titles.
    $titles = [];
    foreach ($slot_items as $slot) {
        if (!is_array($slot)) continue;
        if (!function_exists('iss_calendar_clean_supersaas_title')) continue;
        $raw = isset($slot['title']) ? (string) $slot['title'] : '';
        $ct = iss_calendar_clean_supersaas_title($raw);
        if ($ct !== '') {
            if (!isset($titles[$ct])) $titles[$ct] = [];
            if (function_exists('iss_calendar_extract_supersaas_slot_tag')) {
                $tt = iss_calendar_extract_supersaas_slot_tag($slot);
                if ($tt) $titles[$ct][$tt] = true;
            }
        }
    }

    ksort($titles);
    echo '<p><strong>Detected tours (titles)</strong></p><ul>';
    foreach ($titles as $t => $tagset) {
        $known_tags = array_keys($tagset);
        sort($known_tags);
        $suggested = '';
        if (count($known_tags) === 1) {
            $suggested = $known_tags[0];
        } elseif (function_exists('iss_calendar_generate_tag_from_title')) {
            $suggested = iss_calendar_generate_tag_from_title($t);
        }

        $url = add_query_arg([
            'action' => 'iss_calendar_set_tag',
            'post_id' => (int) $post->ID,
            'tag' => $suggested,
            'saas_title' => $t,
        ], admin_url('admin-post.php'));
        $url = wp_nonce_url($url, 'iss_calendar_set_tag');

        $meta = $suggested ? ('<br><small>Tag: <code>' . esc_html($suggested) . '</code></small>') : '';
        echo '<li><strong>' . esc_html($t) . '</strong>' . $meta . ' — <a href="' . esc_url($url) . '">Use</a></li>';
    }
    echo '</ul>';
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
    $raw_title = isset($_POST['iss_calendar_title_field']) ? (string) $_POST['iss_calendar_title_field'] : '';
    $saas_title = sanitize_text_field($raw_title);

    if ($tag === '') {
        delete_post_meta($post_id, ISS_CALENDAR_TAG_META_KEY);
        if ($saas_title === '') {
            delete_post_meta($post_id, ISS_CALENDAR_SAAS_TITLE_META_KEY);
        } else {
            update_post_meta($post_id, ISS_CALENDAR_SAAS_TITLE_META_KEY, $saas_title);
        }
        return;
    }

    update_post_meta($post_id, ISS_CALENDAR_TAG_META_KEY, $tag);
    if ($saas_title !== '') {
        update_post_meta($post_id, ISS_CALENDAR_SAAS_TITLE_META_KEY, $saas_title);
    } else {
        delete_post_meta($post_id, ISS_CALENDAR_SAAS_TITLE_META_KEY);
    }

    // Explicit editor input should immediately update the tag→post mapping.
    if (function_exists('iss_calendar_remember_source_mapping')) {
        iss_calendar_remember_source_mapping($tag, '', (int) $post_id, (string) $post_type);
    }

    if ($saas_title !== '') {
        $map = function_exists('iss_calendar_get_source_map') ? iss_calendar_get_source_map() : [];
        if (!is_array($map)) $map = [];
        $entry = isset($map[$tag]) && is_array($map[$tag]) ? $map[$tag] : [];
        $entry['source_post_id'] = (int) $post_id;
        $entry['source_post_type'] = (string) $post_type;
        $entry['supersaas_title'] = $saas_title;
        $entry['last_seen_at'] = current_time('mysql');
        $map[$tag] = $entry;
        update_option(ISS_CALENDAR_SOURCE_MAP_OPTION, $map, false);
    }
}, 10, 1);
