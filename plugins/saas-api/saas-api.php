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

require_once __DIR__ . '/iss-calendar/iss-calendar.php';
require_once __DIR__ . '/iss-timeline/iss-timeline.php';

register_activation_hook(__FILE__, 'iss_calendar_activate_sync');
register_deactivation_hook(__FILE__, 'iss_calendar_deactivate_sync');

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
        'id'        => isset($slot['id']) ? (string) $slot['id'] : '',
        'title'     => $title,
        'start'     => $start,
        'end'       => $slot['end'] ?? ($slot['finish'] ?? null),
        'capacity'  => isset($slot['capacity']) ? (int) $slot['capacity'] : null,
        'available' => $available,
        'booking_url' => null,
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

    $tag = strtoupper(sanitize_text_field($request->get_param('tag')));
    $source_post_id = (int) $request->get_param('post_id');

    if (!$tag && $source_post_id > 0) {
        $tag = strtoupper(sanitize_text_field((string) get_post_meta($source_post_id, 'calendar_tag', true)));
        if (!$tag && function_exists('iss_calendar_resolve_tag_for_source_post_id')) {
            $tag = iss_calendar_resolve_tag_for_source_post_id($source_post_id);
        }
    }

    if (!$tag) {
        // Return empty array (UI will show fallback link). Also signals the reason via header.
        $res = new WP_REST_Response(['source' => 'nomap', 'slots' => []], 200);
        $res->header('X-IS-Tours-Source', 'nomap');
        $res->header('X-IS-Tours-Error', 'missing-tag');
        $res->header('Cache-Control', 'no-store');
        return $res;
    }

	    $cache_key = 'is_tours_slots_' . md5($tag);
	    $cached = get_transient($cache_key);
	    if ($cached !== false) {
        $cached_at = is_tours_get_cached_at_by_tag($tag);
        $source = is_tours_get_cached_source_by_tag($tag);
        if ($source === '' || $source === 'cache') {
            $source = 'saas';
            if (is_array($cached)) {
                foreach ($cached as $row) {
                    if (!is_array($row)) continue;
                    if (array_key_exists('booking_url', $row) && $row['booking_url']) {
                        $source = 'cpt';
                        break;
                    }
                }
            }
            is_tours_set_cached_source_by_tag($tag, $source, 60 * 60 * 6);
        }
        $payload = ['source' => $source, 'slots' => is_array($cached) ? $cached : []];
        $etag = is_tours_build_etag($payload);
        $maybe = is_tours_maybe_304($request, $etag, $cached_at, 60);
        if ($maybe) {
            $maybe->header('X-IS-Tours-Source', 'cache');
            return $maybe;
        }

        $res = new WP_REST_Response($payload, 200);
        $res->header('X-IS-Tours-Source', 'cache');
        if ($etag !== '') $res->header('ETag', $etag);
        $res->header('Cache-Control', 'public, max-age=60');
        if ($cached_at > 0) {
            $res->header('Last-Modified', gmdate('D, d M Y H:i:s', $cached_at) . ' GMT');
        }
        return $res;
	    }

    if (!function_exists('iss_calendar_get_slots_with_fallback')) {
        return new WP_REST_Response([
            'error' => 'Calendar module missing',
        ], 500);
    }

    $result = iss_calendar_get_slots_with_fallback($tag, $settings);
    $slots = isset($result['slots']) && is_array($result['slots']) ? $result['slots'] : [];
    $source = isset($result['source']) ? (string) $result['source'] : 'unknown';
    $err = isset($result['error']) ? $result['error'] : null;

    if (!empty($slots)) {
        $ttl = ($source === 'saas') ? (60 * 60 * 6) : (60 * 10);
        is_tours_set_cached_slots_by_tag($tag, $slots, $ttl);
        is_tours_set_cached_source_by_tag($tag, $source, $ttl);

        $payload = ['source' => $source, 'slots' => $slots];
        $etag = is_tours_build_etag($payload);
        $cached_at = is_tours_get_cached_at_by_tag($tag);
        $max_age = ($source === 'saas') ? 300 : 60;
        $maybe = is_tours_maybe_304($request, $etag, $cached_at, $max_age);
        if ($maybe) {
            $maybe->header('X-IS-Tours-Source', $source);
            if ($source === 'cpt') {
                $maybe->header('X-IS-Tours-Fallback', 'cpt');
            }
            return $maybe;
        }

        $res = new WP_REST_Response($payload, 200);
        $res->header('X-IS-Tours-Source', $source);
        if ($source === 'cpt') {
            $res->header('X-IS-Tours-Fallback', 'cpt');
        }
        if ($etag !== '') $res->header('ETag', $etag);
        $res->header('Cache-Control', 'public, max-age=' . $max_age);
        if ($cached_at > 0) {
            $res->header('Last-Modified', gmdate('D, d M Y H:i:s', $cached_at) . ' GMT');
        }
        return $res;
    }

    if ($err instanceof WP_Error) {
        return new WP_REST_Response([
            'source' => 'error',
            'slots' => [],
            'error' => 'Availability fetch failed',
            'fallback' => true,
            'details' => $err->get_error_message(),
        ], 502);
    }

    $res = new WP_REST_Response(['source' => $source, 'slots' => []], 200);
    $res->header('X-IS-Tours-Source', $source);
    $res->header('Cache-Control', 'no-store');
    return $res;
}

function is_saas_register_frontend_assets() {
    wp_register_style(
        'is-tour-calendar-flatpickr',
        plugin_dir_url(__FILE__) . 'vendor/flatpickr/flatpickr.min.css',
        [],
        '4.6.13'
    );

    wp_register_script(
        'is-tour-calendar-flatpickr',
        plugin_dir_url(__FILE__) . 'vendor/flatpickr/flatpickr.min.js',
        [],
        '4.6.13',
        true
    );

    wp_register_script(
        'is-tour-calendar-flatpickr-l10n-de',
        plugin_dir_url(__FILE__) . 'vendor/flatpickr/l10n/de.js',
        ['is-tour-calendar-flatpickr'],
        '4.6.13',
        true
    );

    wp_register_script(
        'is-tour-calendar',
        plugin_dir_url(__FILE__) . 'saas-api.js',
        ['is-tour-calendar-flatpickr', 'is-tour-calendar-flatpickr-l10n-de'],
        IS_SAAS_VERSION,
        true
    );

    wp_register_style(
        'is-tour-calendar',
        plugin_dir_url(__FILE__) . 'saas-api.css',
        [],
        IS_SAAS_VERSION
    );

    wp_register_style(
        'iss-timeline',
        plugin_dir_url(__FILE__) . 'iss-timeline/timeline.css',
        [],
        IS_SAAS_VERSION
    );

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
        '"bookUrl": ' . wp_json_encode(rest_url('is-tours/v1/book')) .
        '});',
        'after'
    );
}
add_action('wp_enqueue_scripts', 'is_saas_register_frontend_assets');

function is_saas_enqueue_calendar_assets() {
    if (!wp_style_is('is-tour-calendar-flatpickr', 'registered')) {
        is_saas_register_frontend_assets();
    }

    wp_enqueue_style('is-tour-calendar-flatpickr');
    wp_enqueue_style('is-tour-calendar');
    wp_enqueue_script('is-tour-calendar-flatpickr');
    wp_enqueue_script('is-tour-calendar-flatpickr-l10n-de');
    wp_enqueue_script('is-tour-calendar');
}

function is_saas_enqueue_timeline_assets() {
    if (!wp_style_is('iss-timeline', 'registered')) {
        is_saas_register_frontend_assets();
    }

    wp_enqueue_style('iss-timeline');
}

	add_shortcode('is_tour_calendar', function ($atts) {
	    $atts = shortcode_atts([
	        'tag'          => '',
	        'title'        => 'Termine wählen',
	        'fallback_url' => '',
	    ], $atts);

        is_saas_enqueue_calendar_assets();

	    $tag = esc_attr(strtoupper($atts['tag']));
	    $title = esc_html($atts['title']);
	    $fallback = esc_url($atts['fallback_url']);
	    $source_post_id = get_the_ID();
	    $source_post_type = $source_post_id ? get_post_type($source_post_id) : '';
	    if (!$source_post_id) {
	        $source_post_id = '';
	    }
	    $slot_select_id = 'is-tour-slot-' . sanitize_key($tag) . '-' . wp_rand(1000, 9999);

	    iss_calendar_remember_source_mapping($tag, $fallback, $source_post_id, $source_post_type);

	    ob_start();
	    ?>
	    <section class="is-tour-calendar wp-block-group alignwide has-global-padding is-layout-constrained" data-tag="<?php echo $tag; ?>" data-fallback="<?php echo $fallback; ?>" data-source-post-id="<?php echo esc_attr($source_post_id); ?>" data-source-post-type="<?php echo esc_attr($source_post_type); ?>">
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

function is_tours_get_cached_source_by_tag($tag) {
    $tag = strtoupper(sanitize_text_field((string) $tag));
    if ($tag === '') {
        return '';
    }

    $cache_key = 'is_tours_slots_src_' . md5($tag);
    $src = get_transient($cache_key);
    return $src ? (string) $src : '';
}

/**
 * Return when the tag cache was last written (unix timestamp), or 0.
 */
function is_tours_get_cached_at_by_tag($tag) {
    $tag = strtoupper(sanitize_text_field((string) $tag));
    if ($tag === '') {
        return 0;
    }

    $cache_key = 'is_tours_slots_ts_' . md5($tag);
    $ts = get_transient($cache_key);
    return $ts ? (int) $ts : 0;
}

/**
 * Store normalized slots into the shared tag cache.
 *
 * @param string $tag
 * @param array $slots
 * @param int $ttl_seconds
 * @return void
 */
function is_tours_set_cached_slots_by_tag($tag, $slots, $ttl_seconds) {
    $tag = strtoupper(sanitize_text_field((string) $tag));
    if ($tag === '') return;

    if (!is_array($slots)) {
        $slots = [];
    }

    $ttl_seconds = (int) $ttl_seconds;
    if ($ttl_seconds <= 0) {
        $ttl_seconds = 60 * 10;
    }

    $cache_key = 'is_tours_slots_' . md5($tag);
    set_transient($cache_key, $slots, $ttl_seconds);

    $ts_key = 'is_tours_slots_ts_' . md5($tag);
    set_transient($ts_key, (int) current_time('timestamp'), $ttl_seconds);
}

function is_tours_set_cached_source_by_tag($tag, $source, $ttl_seconds) {
    $tag = strtoupper(sanitize_text_field((string) $tag));
    if ($tag === '') return;

    $source = sanitize_key((string) $source);
    if ($source === '') return;

    $ttl_seconds = (int) $ttl_seconds;
    if ($ttl_seconds <= 0) {
        $ttl_seconds = 60 * 10;
    }

    $cache_key = 'is_tours_slots_src_' . md5($tag);
    set_transient($cache_key, $source, $ttl_seconds);
}

function is_tours_build_etag($data) {
    try {
        $json = wp_json_encode($data);
        if (!is_string($json)) return '';
        return '"' . md5($json) . '"';
    } catch (Throwable $e) {
        return '';
    }
}

/**
 * Apply HTTP cache headers + conditional GET handling.
 *
 * @return WP_REST_Response|null Return a 304 response to short-circuit, or null to continue.
 */
function is_tours_maybe_304(WP_REST_Request $request, $etag, $last_modified_ts, $max_age) {
    $etag = (string) $etag;
    $last_modified_ts = (int) $last_modified_ts;
    $max_age = (int) $max_age;
    if ($max_age <= 0) {
        $max_age = 60;
    }

    if ($etag !== '') {
        $if_none_match = (string) $request->get_header('if-none-match');
        if ($if_none_match !== '' && trim($if_none_match) === $etag) {
            $res = new WP_REST_Response(null, 304);
            $res->header('ETag', $etag);
            $res->header('Cache-Control', 'public, max-age=' . $max_age);
            if ($last_modified_ts > 0) {
                $res->header('Last-Modified', gmdate('D, d M Y H:i:s', $last_modified_ts) . ' GMT');
            }
            return $res;
        }
    }

    if ($last_modified_ts > 0) {
        $if_modified_since = (string) $request->get_header('if-modified-since');
        if ($if_modified_since !== '') {
            $since = strtotime($if_modified_since);
            if ($since && $since >= $last_modified_ts) {
                $res = new WP_REST_Response(null, 304);
                if ($etag !== '') {
                    $res->header('ETag', $etag);
                }
                $res->header('Cache-Control', 'public, max-age=' . $max_age);
                $res->header('Last-Modified', gmdate('D, d M Y H:i:s', $last_modified_ts) . ' GMT');
                return $res;
            }
        }
    }

    return null;
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

    $payload_tag = isset($payload['tag']) ? strtoupper(sanitize_text_field((string) $payload['tag'])) : '';
    $start = sanitize_text_field($payload['start'] ?? '');
    $title = sanitize_text_field($payload['title'] ?? '');
    $source_post_id = isset($payload['source_post_id']) ? (int) $payload['source_post_id'] : 0;
    $source_post_type = sanitize_key($payload['source_post_type'] ?? '');

    $tag = $payload_tag;
    if ($source_post_id > 0) {
        $resolved = strtoupper(sanitize_text_field((string) get_post_meta($source_post_id, 'calendar_tag', true)));
        if ($resolved === '' && function_exists('iss_calendar_resolve_tag_for_source_post_id')) {
            $resolved = iss_calendar_resolve_tag_for_source_post_id($source_post_id);
        }

        if ($tag === '' && $resolved !== '') {
            $tag = $resolved;
        } elseif ($tag !== '' && $resolved !== '' && $tag !== $resolved) {
            return new WP_REST_Response(['ok' => false, 'error' => 'Ungültige Zuordnung.'], 400);
        }
    }

    $errors = [];
    if ($name === '') { $errors[] = 'Name fehlt.'; }
    if (!is_email($email)) { $errors[] = 'Ungültige E-Mail.'; }
    if ($tickets < 1) { $errors[] = 'Bitte mindestens 1 Ticket.'; }
    if ($slot_id === '') { $errors[] = 'Slot fehlt.'; }
    if (!in_array($payment, ['onsite', 'mollie'], true)) { $errors[] = 'Ungültige Zahlungsart.'; }
    if ($tag === '' && $source_post_id <= 0) { $errors[] = 'Keine Zuordnung vorhanden.'; }

    if ($tag !== '') {
        $slots = is_tours_get_cached_slots_by_tag($tag);
        if (empty($slots) && function_exists('iss_calendar_get_slots_fallback_for_tag')) {
            $fallback_slots = iss_calendar_get_slots_fallback_for_tag($tag);
            if (!empty($fallback_slots)) {
                // Prime cache from CPT fallback so the rest of the code path can reuse it.
                is_tours_set_cached_slots_by_tag($tag, $fallback_slots, 60 * 10);
                is_tours_set_cached_source_by_tag($tag, 'cpt', 60 * 10);
                $slots = $fallback_slots;
            }
        }

        $found = null;
        foreach ($slots as $slot) {
            if (is_array($slot) && (string) ($slot['id'] ?? '') === (string) $slot_id) {
                $found = $slot;
                break;
            }
        }

        if ($found !== null) {
            if (array_key_exists('available', $found) && $found['available'] !== null && (int) $found['available'] <= 0) {
                $errors[] = 'Slot ist ausgebucht.';
            }
        } else {
            // If the live API/cache is empty (e.g. API down), allow CPT-backed slots too.
            $settings = is_saas_get_settings();
            $source_calendar = is_saas_get_schedule_path($settings);
            if ($source_calendar === '') {
                $source_calendar = (string) ($settings['schedule_id'] ?? '');
            }

            if (function_exists('iss_calendar_find_item_post_id') && $source_calendar !== '') {
                $slot_post_id = iss_calendar_find_item_post_id($slot_id, $source_calendar);
                if ($slot_post_id) {
                    if ($source_post_id > 0) {
                        $slot_source_post_id = (int) get_post_meta($slot_post_id, 'source_post_id', true);
                        if ($slot_source_post_id > 0 && $slot_source_post_id !== $source_post_id) {
                            $errors[] = 'Ungültiger Slot.';
                        }
                    }
                    $avail_raw = get_post_meta($slot_post_id, 'capacity_available', true);
                    if ($avail_raw !== '' && $avail_raw !== null && (int) $avail_raw <= 0) {
                        $errors[] = 'Slot ist ausgebucht.';
                    }
                }
            }
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
        'source_post_id' => $source_post_id,
        'source_post_type' => $source_post_type,
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
