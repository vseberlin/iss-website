<?php
/**
 * Plugin Name: ACF Field Group Block
 * Description: Adds a block that renders fields from a selected ACF field group.
 * Version: 1.2.0
 * Author: Local
 * Text Domain: acf-field-group-block
 */

if (!defined('ABSPATH')) {
    exit;
}

function acf_field_group_block_register() {
    add_filter('block_categories_all', function ($categories, $editor_context) {
        foreach ($categories as $category) {
            if ($category['slug'] === 'acf') {
                return $categories;
            }
        }

        $categories[] = array(
            'slug'  => 'acf',
            'title' => 'ACF',
        );

        return $categories;
    }, 10, 2);

    $editor_script = 'acf-field-group-block-editor';
    wp_register_script(
        $editor_script,
        plugins_url('editor.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor', 'wp-api-fetch'),
        '1.2.0',
        true
    );

    $field_options = array();

    wp_localize_script(
        $editor_script,
        'acfFieldGroupBlockData',
        array(
            'fields' => $field_options,
            'restPath' => '/acf-field-group-block/v1/fields',
            'groupsPath' => '/acf-field-group-block/v1/groups',
            'nonce' => wp_create_nonce('wp_rest'),
        )
    );

    register_block_type(__DIR__ . '/block.json', array(
        'render_callback' => 'acf_field_group_block_render',
        'editor_script'   => $editor_script,
    ));
}
add_action('init', 'acf_field_group_block_register');

function acf_field_group_block_register_routes() {
    register_rest_route('acf-field-group-block/v1', '/fields', array(
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
        'callback' => 'acf_field_group_block_rest_fields',
        'args' => array(
            'groupKey' => array(
                'type' => 'string',
                'required' => false,
            ),
            'groupTitle' => array(
                'type' => 'string',
                'required' => false,
            ),
        ),
    ));

    register_rest_route('acf-field-group-block/v1', '/groups', array(
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
        'callback' => 'acf_field_group_block_rest_groups',
    ));
}
add_action('rest_api_init', 'acf_field_group_block_register_routes');

function acf_field_group_block_normalize_price($value) {
    if ($value === null || $value === '') {
        return null;
    }

    if (function_exists('wc_format_decimal')) {
        $formatted = wc_format_decimal($value, wc_get_price_decimals());
        if ($formatted === '') {
            return null;
        }
        return (float) $formatted;
    }

    $raw = is_string($value) ? $value : (string) $value;
    $raw = preg_replace('/[^\d,.\-]/', '', $raw);
    if ($raw === '' || $raw === '-' || $raw === '.' || $raw === ',') {
        return null;
    }

    $has_comma = strpos($raw, ',') !== false;
    $has_dot = strpos($raw, '.') !== false;

    if ($has_comma && $has_dot) {
        $last_comma = strrpos($raw, ',');
        $last_dot = strrpos($raw, '.');
        if ($last_comma > $last_dot) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } else {
            $raw = str_replace(',', '', $raw);
        }
    } elseif ($has_comma) {
        $raw = str_replace(',', '.', $raw);
    }

    return is_numeric($raw) ? (float) $raw : null;
}

function acf_field_group_block_sync_price_to_product($value, $post_id, $field) {
    if (!function_exists('wc_get_product')) {
        return $value;
    }

    $post_id = is_numeric($post_id) ? (int) $post_id : 0;
    if (!$post_id) {
        return $value;
    }

    $old_value = get_post_meta($post_id, $field['name'], true);
    $old_price = acf_field_group_block_normalize_price($old_value);
    $new_price = acf_field_group_block_normalize_price($value);

    if ($old_price === $new_price) {
        return $value;
    }

    $product = function_exists('get_field') ? get_field('product', $post_id) : null;
    $product_id = 0;
    if (is_object($product) && isset($product->ID)) {
        $product_id = (int) $product->ID;
    } elseif (is_numeric($product)) {
        $product_id = (int) $product;
    }

    if (!$product_id) {
        return $value;
    }

    $wc_product = wc_get_product($product_id);
    if (!$wc_product) {
        return $value;
    }

    if ($new_price === null) {
        return $value;
    }

    $wc_product->set_regular_price((string) $new_price);

    $sale_price = $wc_product->get_sale_price('edit');
    if ($sale_price !== '' && $sale_price !== null) {
        $wc_product->set_price($sale_price);
    } else {
        $wc_product->set_price((string) $new_price);
    }

    $wc_product->save();

    return $value;
}

add_filter('acf/update_value/name=price', 'acf_field_group_block_sync_price_to_product', 10, 3);

function acf_field_group_block_normalize_title($title) {
    $title = function_exists('mb_strtolower') ? mb_strtolower($title) : strtolower($title);
    $title = str_replace(array('ä', 'ö', 'ü', 'ß'), array('a', 'o', 'u', 'ss'), $title);
    return $title;
}

function acf_field_group_block_find_group() {
    if (!function_exists('acf_get_field_groups')) {
        return null;
    }

    $groups = acf_get_field_groups();
    if (empty($groups)) {
        return null;
    }
    return $groups[0];
}

function acf_field_group_block_get_group($attributes) {
    if (!function_exists('acf_get_field_groups')) {
        return null;
    }

    $group_key = isset($attributes['groupKey']) && is_string($attributes['groupKey']) ? trim($attributes['groupKey']) : '';
    if ($group_key !== '' && function_exists('acf_get_field_group')) {
        $by_key = acf_get_field_group($group_key);
        if (!empty($by_key)) {
            return $by_key;
        }
    }

    $group_title = isset($attributes['groupTitle']) && is_string($attributes['groupTitle'])
        ? trim($attributes['groupTitle'])
        : '';
    if ($group_title !== '') {
        $groups = acf_get_field_groups(array('title' => $group_title));
        if (!empty($groups)) {
            return $groups[0];
        }

        $groups = acf_get_field_groups();
        if (!empty($groups)) {
            $target = acf_field_group_block_normalize_title($group_title);
            foreach ($groups as $group) {
                if (!isset($group['title'])) {
                    continue;
                }
                if (acf_field_group_block_normalize_title($group['title']) === $target) {
                    return $group;
                }
            }
        }
    }

    return null;
}

function acf_field_group_block_build_field_options($group) {
    if (empty($group) || empty($group['key']) || !function_exists('acf_get_fields')) {
        return array();
    }

    $fields = acf_get_fields($group['key']);
    if (empty($fields)) {
        return array();
    }

    $options = array();
    foreach ($fields as $field) {
        if (empty($field['name'])) {
            continue;
        }

        $field_name = $field['name'];
        $normalized_name = strtolower($field_name);
        if ($normalized_name === 'booking_type') {
            continue;
        }

        $label = isset($field['label']) && $field['label'] !== '' ? $field['label'] : $field_name;
        $options[] = array(
            'label' => $label,
            'value' => $field_name,
        );
    }

    return $options;
}

function acf_field_group_block_rest_fields($request) {
    if (!function_exists('acf_get_fields') || !function_exists('acf_get_field_groups')) {
        return rest_ensure_response(array());
    }

    $attributes = array(
        'groupKey' => $request->get_param('groupKey'),
        'groupTitle' => $request->get_param('groupTitle'),
    );
    $group = acf_field_group_block_get_group($attributes);
    if (empty($group)) {
        return rest_ensure_response(array());
    }
    $options = acf_field_group_block_build_field_options($group);

    return rest_ensure_response($options);
}

function acf_field_group_block_rest_groups() {
    if (!function_exists('acf_get_field_groups')) {
        return rest_ensure_response(array());
    }

    $groups = acf_get_field_groups();
    if (empty($groups)) {
        return rest_ensure_response(array());
    }

    $options = array();
    foreach ($groups as $group) {
        if (empty($group['key'])) {
            continue;
        }
        $label = isset($group['title']) && $group['title'] !== '' ? $group['title'] : $group['key'];
        $options[] = array(
            'label' => $label,
            'value' => $group['key'],
        );
    }

    return rest_ensure_response($options);
}

function acf_field_group_block_format_value($value) {
    if (is_bool($value)) {
        return $value ? 'Yes' : 'No';
    }

    if (is_array($value)) {
        $flat = true;
        foreach ($value as $item) {
            if (is_array($item) || is_object($item)) {
                $flat = false;
                break;
            }
        }

        if ($flat) {
            return implode(', ', array_map('strval', $value));
        }

        return '<pre>' . esc_html(wp_json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
    }

    if (is_object($value)) {
        return '<pre>' . esc_html(wp_json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
    }

    return (string) $value;
}

function acf_field_group_block_collect_selected_fields($attributes) {
    $selected_fields = array();

    if (isset($attributes['fieldNames']) && is_array($attributes['fieldNames'])) {
        foreach ($attributes['fieldNames'] as $name) {
            if (!is_string($name) || $name === '') {
                continue;
            }
            $selected_fields[] = $name;
        }
    }

    return $selected_fields;
}

function acf_field_group_block_build_wrapper_classes($attributes) {
    $variant = isset($attributes['variant']) && is_string($attributes['variant']) ? $attributes['variant'] : 'default';
    $accent = isset($attributes['accent']) && is_string($attributes['accent']) ? $attributes['accent'] : 'none';
    $columns = isset($attributes['columns']) ? (int) $attributes['columns'] : 1;

    if (!in_array($variant, array('default', 'card', 'minimal'), true)) {
        $variant = 'default';
    }

    if (!in_array($accent, array('none', 'highlight'), true)) {
        $accent = 'none';
    }

    if (!in_array($columns, array(1, 2), true)) {
        $columns = 1;
    }

    return array(
        'acf-field-group-block',
        'is-' . sanitize_html_class($variant),
        'cols-' . $columns,
        'accent-' . sanitize_html_class($accent),
    );
}

function acf_field_group_block_render($attributes, $content, $block) {
    if (!function_exists('get_field') || !function_exists('acf_get_fields')) {
        return '';
    }

    $group = acf_field_group_block_get_group($attributes);
    if (!$group || empty($group['key'])) {
        return '';
    }

    $fields = acf_get_fields($group['key']);
    if (empty($fields)) {
        return '';
    }

    $post_id = 0;
    if (is_object($block) && isset($block->context['postId'])) {
        $post_id = (int) $block->context['postId'];
    }
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    if (!$post_id) {
        return '';
    }

    $show_empty = !empty($attributes['showEmpty']);
    $layout = isset($attributes['layout']) ? $attributes['layout'] : 'list';
    $selected_field = isset($attributes['fieldName']) ? (string) $attributes['fieldName'] : '';
    $selected_fields = acf_field_group_block_collect_selected_fields($attributes);
    $selection_mode = isset($attributes['selectionMode']) ? (string) $attributes['selectionMode'] : 'single';

    if ($selection_mode === 'multi' && empty($selected_fields)) {
        return '';
    }

    $items = array();
    foreach ($fields as $field) {
        if (empty($field['name'])) {
            continue;
        }

        $field_name = $field['name'];
        if (!empty($selected_fields)) {
            if (!in_array($field_name, $selected_fields, true)) {
                continue;
            }
        } elseif ($selected_field !== '' && $selected_field !== $field_name) {
            continue;
        }

        $normalized_name = strtolower($field_name);
        if ($normalized_name === 'booking_type') {
            continue;
        }

        if ($normalized_name === 'product') {
            $product = get_field($field_name, $post_id);
            $booking_url = '';

            if (is_object($product) && isset($product->ID)) {
                $booking_url = get_permalink($product->ID);
            } elseif (is_numeric($product)) {
                $booking_url = get_permalink((int) $product);
            } elseif (is_string($product) && filter_var($product, FILTER_VALIDATE_URL)) {
                $booking_url = $product;
            }

            if (!$booking_url && !$show_empty) {
                continue;
            }

            $items[] = array(
                'label' => '',
                'value' => $booking_url
                    ? '<div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url($booking_url) . '">' . esc_html('Buchen') . '</a></div></div>'
                    : '',
            );
            continue;
        }

        $raw_value = get_field($field_name, $post_id, false);
        if (!$show_empty && ($raw_value === null || $raw_value === '' || $raw_value === array())) {
            continue;
        }

        $formatted = function_exists('acf_format_value')
            ? acf_format_value($raw_value, $post_id, $field)
            : $raw_value;

        $display = acf_field_group_block_format_value($formatted);
        if (!$show_empty && $display === '') {
            continue;
        }

        $label = isset($field['label']) ? $field['label'] : $field_name;
        $items[] = array(
            'label' => $label,
            'value' => $display,
        );
    }

    if (empty($items)) {
        return '';
    }

    $wrapper_attrs = get_block_wrapper_attributes(array(
        'class' => implode(' ', acf_field_group_block_build_wrapper_classes($attributes)),
    ));

    if ($layout === 'definition') {
        $output = '<dl class="acf-field-group-block__list">';
        foreach ($items as $item) {
            if ($item['label'] !== '') {
                $output .= '<dt>' . esc_html($item['label']) . '</dt>';
                $output .= '<dd>' . wp_kses_post($item['value']) . '</dd>';
            } else {
                $output .= '<dd class="acf-field-group-block__value-only">' . wp_kses_post($item['value']) . '</dd>';
            }
        }
        $output .= '</dl>';
    } else {
        $output = '<ul class="acf-field-group-block__list">';
        foreach ($items as $item) {
            if ($item['label'] !== '') {
                $output .= '<li><strong>' . esc_html($item['label']) . ':</strong> ' . wp_kses_post($item['value']) . '</li>';
            } else {
                $output .= '<li class="acf-field-group-block__value-only">' . wp_kses_post($item['value']) . '</li>';
            }
        }
        $output .= '</ul>';
    }

    return '<div ' . $wrapper_attrs . '>' . $output . '</div>';
}
