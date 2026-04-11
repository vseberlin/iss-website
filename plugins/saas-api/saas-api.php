<?php
/**
 * Plugin Name: SuperSaaS API
 * Description: Fetches filtered tour availability from SuperSaaS and renders an Ollie-friendly public calendar.
 * Version: 1.2.0
 */

if (!defined('ABSPATH')) exit;

define('IS_SAAS_VERSION', '1.2.0');
define('IS_SAAS_OPTION_GROUP', 'is_saas_options');
define('IS_SAAS_OPTION_NAME', 'is_saas_settings');

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
        'menu_icon' => 'calendar-alt',
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
            'auth_callback' => static function () {
                return current_user_can('edit_posts');
            },
        ], $args));
    }
}, 5);

function is_saas_get_settings() {
    $defaults = [
        'schedule_id'   => '',
        'api_key'       => '',
        'base_url'      => 'https://www.supersaas.de',
        'account_name'  => '',
        'schedule_path' => '',
    ];

    $settings = get_option(IS_SAAS_OPTION_NAME, []);
    if (!is_array($settings)) {
        $settings = [];
    }

    return array_merge($defaults, $settings);
}

function is_saas_get_schedule_path($settings = null) {
    if ($settings === null) {
        $settings = is_saas_get_settings();
    }

    if (!empty($settings['schedule_path'])) {
        return $settings['schedule_path'];
    }

    return '';
}

function is_saas_normalize_schedule_path($schedule_path) {
    $schedule_path = trim((string) $schedule_path);
    if ($schedule_path === '') {
        return '';
    }

    return str_replace('%2F', '/', rawurlencode(rawurldecode($schedule_path)));
}

function is_saas_register_settings() {
    register_setting(
        IS_SAAS_OPTION_GROUP,
        IS_SAAS_OPTION_NAME,
        [
            'sanitize_callback' => 'is_saas_sanitize_settings',
            'default' => [],
        ]
    );

    add_settings_section(
        'is_saas_main',
        'SuperSaaS Configuration',
        '__return_false',
        IS_SAAS_OPTION_GROUP
    );

    add_settings_field('schedule_id', 'Schedule ID', 'is_saas_field_schedule_id', IS_SAAS_OPTION_GROUP, 'is_saas_main');
    add_settings_field('api_key', 'API Key', 'is_saas_field_api_key', IS_SAAS_OPTION_GROUP, 'is_saas_main');
    add_settings_field('base_url', 'API Base URL', 'is_saas_field_base_url', IS_SAAS_OPTION_GROUP, 'is_saas_main');
    add_settings_field('account_name', 'Account Name', 'is_saas_field_account_name', IS_SAAS_OPTION_GROUP, 'is_saas_main');
    add_settings_field('schedule_path', 'Schedule Path', 'is_saas_field_schedule_path', IS_SAAS_OPTION_GROUP, 'is_saas_main');
}
add_action('admin_init', 'is_saas_register_settings');

function is_saas_sanitize_settings($input) {
    $out = [];
    $out['schedule_id']   = isset($input['schedule_id']) ? preg_replace('/[^0-9]/', '', $input['schedule_id']) : '';
    $out['api_key']       = isset($input['api_key']) ? sanitize_text_field($input['api_key']) : '';
    $out['base_url']      = isset($input['base_url']) ? esc_url_raw(trim($input['base_url'])) : '';
    $out['account_name']  = isset($input['account_name']) ? sanitize_text_field($input['account_name']) : '';
    $out['schedule_path'] = isset($input['schedule_path']) ? trim((string) $input['schedule_path']) : '';
    return $out;
}

function is_saas_add_admin_menu() {
    add_options_page(
        'SuperSaaS API',
        'SuperSaaS API',
        'manage_options',
        'is-saas-api',
        'is_saas_render_settings_page'
    );
}
add_action('admin_menu', 'is_saas_add_admin_menu');

function is_saas_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>SuperSaaS API</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields(IS_SAAS_OPTION_GROUP);
            do_settings_sections(IS_SAAS_OPTION_GROUP);
            submit_button();
            ?>
        </form>
        <p><strong>Shortcode:</strong> <code>[is_tour_calendar tag="ELEKTRO" title="Termine wählen" fallback_url="https://example.com"]</code></p>
    </div>
    <?php
}

function is_saas_build_slot_response($slot, $title, $start) {
    $available = null;
    if (isset($slot['available'])) {
        $available = (int) $slot['available'];
    } elseif (isset($slot['remaining'])) {
        $available = (int) $slot['remaining'];
    } elseif (isset($slot['count'])) {
        $available = (int) $slot['count'];
    }

    return [
        'id'        => $slot['id'] ?? null,
        'title'     => $title,
        'start'     => $start,
        'end'       => $slot['end'] ?? ($slot['finish'] ?? null),
        'capacity'  => isset($slot['capacity']) ? (int) $slot['capacity'] : null,
        'available' => $available,
    ];
}

function is_saas_field_schedule_id() {
    $settings = is_saas_get_settings();
    printf(
        '<input type="text" name="%s[schedule_id]" value="%s" class="regular-text" />',
        esc_attr(IS_SAAS_OPTION_NAME),
        esc_attr($settings['schedule_id'])
    );
}

function is_saas_field_api_key() {
    $settings = is_saas_get_settings();
    printf(
        '<input type="password" name="%s[api_key]" value="%s" class="regular-text" autocomplete="new-password" />',
        esc_attr(IS_SAAS_OPTION_NAME),
        esc_attr($settings['api_key'])
    );
}

function is_saas_field_base_url() {
    $settings = is_saas_get_settings();
    printf(
        '<input type="text" name="%s[base_url]" value="%s" class="regular-text" />',
        esc_attr(IS_SAAS_OPTION_NAME),
        esc_attr($settings['base_url'])
    );
    echo '<p class="description">Example: https://www.supersaas.de</p>';
}

function is_saas_field_account_name() {
    $settings = is_saas_get_settings();
    printf(
        '<input type="text" name="%s[account_name]" value="%s" class="regular-text" />',
        esc_attr(IS_SAAS_OPTION_NAME),
        esc_attr($settings['account_name'])
    );
    echo '<p class="description">Used for booking links.</p>';
}

function is_saas_field_schedule_path() {
    $settings = is_saas_get_settings();
    printf(
        '<input type="text" name="%s[schedule_path]" value="%s" class="regular-text" />',
        esc_attr(IS_SAAS_OPTION_NAME),
        esc_attr($settings['schedule_path'])
    );
    echo '<p class="description">Required for booking links. Example: Fuehrungen_%28oeffentlich%29</p>';
}

add_action('rest_api_init', function () {
    register_rest_route('is-tours/v1', '/slots', [
        'methods'  => 'GET',
        'callback' => 'is_tours_get_slots',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('is-tours/v1', '/book', [
        'methods'  => 'POST',
        'callback' => 'is_tours_create_booking',
        'permission_callback' => '__return_true',
    ]);

});


function is_tours_get_slots(WP_REST_Request $request) {
    $settings = is_saas_get_settings();

    if (empty($settings['schedule_id']) || empty($settings['api_key']) || empty($settings['account_name'])) {
        return new WP_REST_Response([
            'error' => 'Missing SuperSaaS configuration'
        ], 500);
    }

    $tag = strtoupper(sanitize_text_field($request->get_param('tag')));
    if (!$tag) {
        return new WP_REST_Response([
            'error' => 'Missing tag'
        ], 400);
    }

    $cache_key = 'is_tours_slots_' . md5($tag);
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return new WP_REST_Response($cached, 200);
    }

    $base_url = untrailingslashit($settings['base_url']);
    $from = rawurlencode(current_time('Y-m-d H:i:s'));
    $url = $base_url . '/api/free/' . rawurlencode($settings['schedule_id']) . '.json?from=' . $from;

    $response = wp_remote_get($url, [
        'timeout' => 15,
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($settings['account_name'] . ':' . $settings['api_key']),
        ],
    ]);

    if (is_wp_error($response)) {
        return new WP_REST_Response([
            'error'    => 'Availability fetch failed',
            'fallback' => true,
            'details'  => $response->get_error_message(),
        ], 502);
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        return new WP_REST_Response([
            'error'           => 'API request failed',
            'fallback'        => true,
            'upstream_status' => $code,
            'upstream_body'   => wp_remote_retrieve_body($response),
        ], 502);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $slot_items = isset($data['slots']) && is_array($data['slots']) ? $data['slots'] : $data;

    if (!is_array($slot_items)) {
        return new WP_REST_Response([
            'error'    => 'Invalid API response',
            'fallback' => true,
        ], 502);
    }

    $slots = [];

    foreach ($slot_items as $slot) {
        if (!is_array($slot)) {
            continue;
        }

        $title = isset($slot['title']) ? trim((string) $slot['title']) : '';
        if ($title === '' || stripos($title, '[' . $tag . ']') !== 0) {
            continue;
        }

        $start = $slot['start'] ?? null;
        if (!$start) {
            continue;
        }

        $slots[] = is_saas_build_slot_response($slot, $title, $start);
    }

    usort($slots, function ($a, $b) {
        return strcmp((string) $a['start'], (string) $b['start']);
    });

    set_transient($cache_key, $slots, 60 * 60 * 6); // 6h

    return new WP_REST_Response($slots, 200);
}

	add_shortcode('is_tour_calendar', function ($atts) {
	    $atts = shortcode_atts([
	        'tag'          => '',
	        'title'        => 'Termine wählen',
	        'fallback_url' => '',
	    ], $atts);

	    $tag = esc_attr(strtoupper($atts['tag']));
	    $title = esc_html($atts['title']);
	    $fallback = esc_url($atts['fallback_url']);
	    $slot_select_id = 'is-tour-slot-' . sanitize_key($tag) . '-' . wp_rand(1000, 9999);

	    ob_start();
	    ?>
	    <section class="is-tour-calendar wp-block-group alignwide has-global-padding is-layout-constrained" data-tag="<?php echo $tag; ?>" data-fallback="<?php echo $fallback; ?>">
	        <div class="is-tour-calendar__inner wp-block-group is-layout-constrained">
	            <div class="is-tour-calendar__header wp-block-group is-layout-constrained">
	                <p class="is-tour-calendar__eyebrow has-small-font-size">Kalender</p>
	                <h3 class="is-tour-calendar__heading wp-block-heading"><?php echo $title; ?></h3>
	                <p class="is-tour-calendar__status has-small-font-size">Termine werden geladen …</p>
	                <?php if (!empty($fallback)) : ?>
	                    <p class="is-tour-calendar__fallback has-small-font-size">
	                        <a class="is-tour-calendar__fallback-link" href="<?php echo esc_url($fallback); ?>">Direkt buchen</a>
	                    </p>
	                <?php endif; ?>
	            </div>

		            <div class="is-tour-calendar__layout">
		                <div class="is-tour-calendar__calendar">
		                    <input type="text" class="is-tour-calendar__date-input" aria-label="Datum auswählen" />

		                    <div class="is-tour-calendar__slots-panel">
		                        <p class="is-tour-calendar__selected-date has-small-font-size">Bitte wählen Sie einen Tag.</p>
		                        <div class="is-tour-calendar__appointments">
		                            <p class="is-tour-calendar__appointments-title">
		                                <span class="is-tour-calendar__appointments-title-label">Verfügbare Termine am</span>
		                                <span class="is-tour-calendar__appointments-title-date"></span>
		                            </p>
		                            <div class="is-tour-calendar__appointments-divider" aria-hidden="true"></div>
		                            <div class="is-tour-calendar__appointments-list"></div>
		                        </div>
		                        <div class="is-tour-calendar__slot-select-wrap">
		                            <label class="is-tour-calendar__slot-label" for="<?php echo esc_attr($slot_select_id); ?>">
		                                Uhrzeit
		                            </label>
		                            <select id="<?php echo esc_attr($slot_select_id); ?>" class="is-tour-calendar__slot-select" disabled>
		                                <option value="">Bitte zuerst ein Datum wählen</option>
		                            </select>
		                        </div>
		                        <div class="is-tour-calendar__booking"></div>
		                    </div>
		                </div>
		            </div>
		        </div>
		    </section>
	    <?php
    return ob_get_clean();
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'is-tour-calendar-flatpickr',
        plugin_dir_url(__FILE__) . 'vendor/flatpickr/flatpickr.min.css',
        [],
        '4.6.13'
    );

    wp_enqueue_script(
        'is-tour-calendar-flatpickr',
        plugin_dir_url(__FILE__) . 'vendor/flatpickr/flatpickr.min.js',
        [],
        '4.6.13',
        true
    );

    wp_enqueue_script(
        'is-tour-calendar-flatpickr-l10n-de',
        plugin_dir_url(__FILE__) . 'vendor/flatpickr/l10n/de.js',
        ['is-tour-calendar-flatpickr'],
        '4.6.13',
        true
    );

    wp_enqueue_script(
        'is-tour-calendar',
        plugin_dir_url(__FILE__) . 'saas-api.js',
        ['is-tour-calendar-flatpickr', 'is-tour-calendar-flatpickr-l10n-de'],
        IS_SAAS_VERSION,
        true
    );

    wp_enqueue_style(
        'is-tour-calendar',
        plugin_dir_url(__FILE__) . 'saas-api.css',
        [],
        IS_SAAS_VERSION
    );

    // Feed config to JS (internal endpoints only)
    wp_add_inline_script(
        'is-tour-calendar',
        'window.IS_TOUR_CALENDAR = ' . wp_json_encode([
            'restUrl' => rest_url('is-tours/v1/slots'),
        ]) . ';',
        'before'
    );

    wp_add_inline_script(
        'is-tour-calendar',
        'window.IS_TOUR_CALENDAR = Object.assign({}, window.IS_TOUR_CALENDAR, {' .
        '"bookUrl": ' . wp_json_encode( rest_url('is-tours/v1/book') ) .
        '});',
        'after'
    );
});

/**
 * Return cached slots for a given tag, or an empty array when not cached.
 */
function is_tours_get_cached_slots_by_tag($tag) {
    $tag = strtoupper(sanitize_text_field((string) $tag));
    if ($tag === '') {
        return [];
    }

    $cache_key = 'is_tours_slots_' . md5($tag);
    $cached = get_transient($cache_key);
    if ($cached === false || !is_array($cached)) {
        return [];
    }

    return $cached;
}

/**
 * Return the next available slot from cache for a tag (or null).
 */
function is_tours_get_next_slot($tag) {
    $slots = is_tours_get_cached_slots_by_tag($tag);

    foreach ($slots as $slot) {
        if (!is_array($slot)) {
            continue;
        }

        if (!array_key_exists('available', $slot) || $slot['available'] === null || (int) $slot['available'] > 0) {
            return $slot;
        }
    }

    return null;
}

function is_tours_create_booking(WP_REST_Request $request) {
    $payload = json_decode($request->get_body(), true);
    if (!is_array($payload)) {
        return new WP_REST_Response(['ok' => false, 'error' => 'Invalid payload'], 400);
    }

    $name = sanitize_text_field($payload['name'] ?? '');
    $email = sanitize_email($payload['email'] ?? '');
    $tickets = isset($payload['tickets']) ? (int) $payload['tickets'] : 0;
    $slot_id = isset($payload['slot_id']) ? trim((string) $payload['slot_id']) : '';
    $payment = sanitize_text_field($payload['payment'] ?? '');

    $tag = isset($payload['tag']) ? strtoupper(sanitize_text_field((string) $payload['tag'])) : '';
    $start = sanitize_text_field($payload['start'] ?? '');
    $title = sanitize_text_field($payload['title'] ?? '');

    $errors = [];
    if ($name === '') { $errors[] = 'Name fehlt.'; }
    if (!is_email($email)) { $errors[] = 'Ungültige E-Mail.'; }
    if ($tickets < 1) { $errors[] = 'Bitte mindestens 1 Ticket.'; }
    if ($slot_id === '') { $errors[] = 'Slot fehlt.'; }
    if (!in_array($payment, ['onsite', 'mollie'], true)) { $errors[] = 'Ungültige Zahlungsart.'; }

    if ($tag !== '') {
        $slots = is_tours_get_cached_slots_by_tag($tag);
        $found = null;
        foreach ($slots as $slot) {
            if (is_array($slot) && (string) ($slot['id'] ?? '') === (string) $slot_id) {
                $found = $slot;
                break;
            }
        }

        if ($found === null) {
            $errors[] = 'Slot nicht gefunden (Cache).';
        } elseif (array_key_exists('available', $found) && $found['available'] !== null && (int) $found['available'] <= 0) {
            $errors[] = 'Slot ist ausgebucht.';
        }
    }

    if (!empty($errors)) {
        return new WP_REST_Response(['ok' => false, 'error' => implode(' ', $errors)], 400);
    }

    $entry = [
        'time' => current_time('mysql'),
        'name' => $name,
        'email' => $email,
        'tickets' => $tickets,
        'slot_id' => $slot_id,
        'payment' => $payment,
        'tag' => $tag,
        'start' => $start,
        'title' => $title,
        'ip' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '',
        'ua' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field((string) $_SERVER['HTTP_USER_AGENT']) : '',
    ];

    $requests = get_option('is_tours_booking_requests', []);
    if (!is_array($requests)) {
        $requests = [];
    }
    $requests[] = $entry;
    if (count($requests) > 200) {
        $requests = array_slice($requests, -200);
    }
    update_option('is_tours_booking_requests', $requests, false);

    /**
     * Hook for handling bookings (email, CRM, etc.).
     *
     * @param array $entry Sanitized booking entry.
     * @param WP_REST_Request $request Original REST request.
     */
    do_action('is_tours_booking_created', $entry, $request);

    return new WP_REST_Response(['ok' => true], 200);
}
