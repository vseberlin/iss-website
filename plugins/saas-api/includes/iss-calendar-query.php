<?php

if (!defined('ABSPATH')) exit;

/**
 * Get calendar items linked to a given WP content item (by `source_post_id`).
 *
 * @param int $post_id
 * @return WP_Post[]
 */
function iss_get_events_for_post($post_id) {
    $post_id = (int) $post_id;
    if ($post_id <= 0) return [];

    return get_posts([
        'post_type' => ISS_CALENDAR_ITEM_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'no_found_rows' => true,

        'meta_key' => 'event_start',
        'orderby' => 'meta_value',
        'meta_type' => 'DATETIME',
        'order' => 'ASC',

        'meta_query' => [
            [
                'key' => 'source_post_id',
                'value' => $post_id,
                'compare' => '=',
                'type' => 'NUMERIC',
            ],
        ],
    ]);
}

