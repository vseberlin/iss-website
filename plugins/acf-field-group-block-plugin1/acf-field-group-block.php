<?php
/**
 * Plugin Name: ACF Field Group Block
 * Description: Adds a block that renders fields from the ACF field group "führung".
 * Version: 1.1.1
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
        array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor'),
        '1.1.1',
        true
    );

    $field_options = array();
    if (function_exists('acf_get_fields')) {
        $group = acf_field_group_block_find_group();
        if ($group && !empty($group['key'])) {
            $fields = acf_get_fields($group['key']);
            if (!empty($fields)) {
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
                    $field_options[] = array(
                        'label' => $label,
                        'value' => $field_name,
                    );
                }
            }
        }
    }

    wp_localize_script(
        $editor_script,
        'acfFieldGroupBlockData',
        array('fields' => $field_options)
    );

    register_block_type(__DIR__ . '/block.json', array(
        'render_callback' => 'acf_field_group_block_render',
        'editor_script'   => $editor_script,
    ));
}
add_action('init', 'acf_field_group_block_register');

function acf_field_group_block_normalize_title($title) {
    $title = function_exists('mb_strtolower') ? mb_strtolower($title) : strtolower($title);
    $title = str_replace(array('ä', 'ö', 'ü', 'ß'), array('a', 'o', 'u', 'ss'), $title);
    return $title;
}

function acf_field_group_block_find_group() {
    if (!function_exists('acf_get_field_groups')) {
        return null;
    }

    $groups = acf_get_field_groups(array('title' => 'führung'));
    if (!empty($groups)) {
        return $groups[0];
    }

    $groups = acf_get_field_groups();
    if (empty($groups)) {
        return null;
    }

    $target = acf_field_group_block_normalize_title('führung');
    foreach ($groups as $group) {
        if (!isset($group['title'])) {
            continue;
        }
        if (acf_field_group_block_normalize_title($group['title']) === $target) {
            return $group;
        }
    }

    return null;
}

function acf_field_group_block_format_value($value) {
    if (is_bool($value)) {
        return $value
            ? esc_html__('Yes', 'acf-field-group-block')
            : esc_html__('No', 'acf-field-group-block');
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

function acf_field_group_block_get_layout($attributes) {
    $layout = isset($attributes['layout']) && is_string($attributes['layout']) ? $attributes['layout'] : 'list';

    if (!in_array($layout, array('list', 'definition'), true)) {
        $layout = 'list';
    }

    return $layout;
}

function acf_field_group_block_render_definition_items($items) {
    $labelled_items = array();
    $value_only_items = array();

    foreach ($items as $item) {
        if (!empty($item['label'])) {
            $labelled_items[] = $item;
        } else {
            $value_only_items[] = $item;
        }
    }

    $output = '';

    if (!empty($labelled_items)) {
        $output .= '<dl class="acf-field-group-block__list">';
        foreach ($labelled_items as $item) {
            $output .= '<dt>' . esc_html($item['label']) . '</dt>';
            $output .= '<dd>' . wp_kses_post($item['value']) . '</dd>';
        }
        $output .= '</dl>';
    }

    if (!empty($value_only_items)) {
        $output .= '<div class="acf-field-group-block__value-only-group">';
        foreach ($value_only_items as $item) {
            $output .= '<div class="acf-field-group-block__value-only">' . wp_kses_post($item['value']) . '</div>';
        }
        $output .= '</div>';
    }

    return $output;
}

function acf_field_group_block_render_list_items($items) {
    $output = '<ul class="acf-field-group-block__list">';
    foreach ($items as $item) {
        if ($item['label'] !== '') {
            $output .= '<li><strong>' . esc_html($item['label']) . ':</strong> ' . wp_kses_post($item['value']) . '</li>';
        } else {
            $output .= '<li class="acf-field-group-block__value-only">' . wp_kses_post($item['value']) . '</li>';
        }
    }
    $output .= '</ul>';

    return $output;
}

function acf_field_group_block_render($attributes, $content, $block) {
    if (!function_exists('get_field') || !function_exists('acf_get_fields')) {
        return '';
    }

    $group = acf_field_group_block_find_group();
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
    $layout = acf_field_group_block_get_layout($attributes);
    $selected_field = isset($attributes['fieldName']) ? (string) $attributes['fieldName'] : '';
    $selected_fields = acf_field_group_block_collect_selected_fields($attributes);

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
                    ? '<div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url($booking_url) . '">' . esc_html__('Buchen', 'acf-field-group-block') . '</a></div></div>'
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

    $output = $layout === 'definition'
        ? acf_field_group_block_render_definition_items($items)
        : acf_field_group_block_render_list_items($items);

    return '<div ' . $wrapper_attrs . '>' . $output . '</div>';
}
