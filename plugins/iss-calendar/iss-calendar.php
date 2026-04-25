<?php
/**
 * Plugin Name: ISS Calendar
 * Description: Internal calendar items (CPT) and sync helpers.
 * Version: 0.1.0
 */

if (!defined('ABSPATH')) exit;

/**
 * CPT: iss_calendar_item
 */
add_action('init', function () {
    register_post_type('iss_calendar_item', [
        'labels' => [
            'name' => 'Calendar Items',
            'singular_name' => 'Calendar Item',
            'add_new_item' => 'Add Calendar Item',
            'edit_item' => 'Edit Calendar Item',
            'new_item' => 'New Calendar Item',
            'view_item' => 'View Calendar Item',
            'search_items' => 'Search Calendar Items',
        ],
        'public' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'rest_base' => 'iss-calendar-items',
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => ['title', 'editor', 'excerpt'],
        'has_archive' => false,
        'rewrite' => false,
    ]);

    $rest_schema_date_time = [
        'schema' => [
            'type' => 'string',
            'format' => 'date-time',
        ],
    ];

    $meta_fields = [
        'event_start' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => $rest_schema_date_time,
        ],
        'event_end' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => $rest_schema_date_time,
        ],
        'item_type' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => true,
        ],
        'source_system' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => true,
        ],
        'source_calendar' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => true,
        ],
        'external_id' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => true,
        ],
        'source_post_id' => [
            'type' => 'integer',
            'sanitize_callback' => static function ($value) {
                return (int) $value;
            },
            'show_in_rest' => true,
        ],
        'source_post_type' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => true,
        ],
        'booking_url' => [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'show_in_rest' => true,
        ],
        'location' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => true,
        ],
        'availability_state' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => true,
        ],
        'capacity_total' => [
            'type' => 'integer',
            'sanitize_callback' => static function ($value) {
                return (int) $value;
            },
            'show_in_rest' => true,
        ],
        'capacity_available' => [
            'type' => 'integer',
            'sanitize_callback' => static function ($value) {
                return (int) $value;
            },
            'show_in_rest' => true,
        ],
        'is_public' => [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'show_in_rest' => true,
        ],
        'sync_status' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => true,
        ],
        'last_synced_at' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => $rest_schema_date_time,
        ],
        'last_seen_at' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => $rest_schema_date_time,
        ],
        'origin_mode' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => true,
        ],
        'public_note' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'show_in_rest' => true,
        ],
        'sort_date' => [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => $rest_schema_date_time,
        ],
    ];

    foreach ($meta_fields as $meta_key => $args) {
        register_post_meta('iss_calendar_item', $meta_key, array_merge([
            'single' => true,
            'auth_callback' => static function ($allowed, $meta_key, $post_id) {
                return current_user_can('edit_post', $post_id);
            },
        ], $args));
    }
}, 5);

