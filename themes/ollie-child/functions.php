<?php
if (!defined('ABSPATH')) {
	exit;
}

add_action('wp_enqueue_scripts', function () {
	$theme_uri  = get_stylesheet_directory_uri();
	$theme_path = get_stylesheet_directory();

	$enqueue_child_style = static function (string $handle, string $relative_path, array $deps = array()) use ($theme_uri, $theme_path): void {
		$file_path = $theme_path . '/' . $relative_path;

		if (!file_exists($file_path)) {
			return;
		}

		wp_enqueue_style(
			$handle,
			$theme_uri . '/' . $relative_path,
			$deps,
			(string) filemtime($file_path)
		);
	};

	wp_enqueue_style(
		'ollie-parent',
		get_template_directory_uri() . '/style.css',
		array(),
		wp_get_theme(get_template())->get('Version')
	);

	$enqueue_child_style('ollie-child-base', 'assets/css/base.css', array('ollie-parent'));
	$enqueue_child_style('ollie-child-header', 'assets/css/header.css', array('ollie-child-base'));
	$enqueue_child_style('ollie-child-hero', 'assets/css/hero.css', array('ollie-child-header'));
	$enqueue_child_style('ollie-child-fuehrungen', 'assets/css/fuehrungen.css', array('ollie-child-hero'));
});

add_action('init', function () {
	if (!function_exists('register_block_bindings_source')) {
		return;
	}

	register_block_bindings_source(
		'industriesalon/booking-url',
		array(
			'label'              => 'Booking URL from linked Woo product',
			'get_value_callback' => function (array $source_args, $block_instance, string $attribute_name) {
				$post_id = 0;

				if (is_object($block_instance) && isset($block_instance->context['postId'])) {
					$post_id = (int) $block_instance->context['postId'];
				}

				if (!$post_id) {
					$post_id = get_the_ID();
				}

				if (!$post_id || !function_exists('get_field')) {
					return null;
				}

				$field_name = !empty($source_args['field']) ? (string) $source_args['field'] : 'product';
				$product    = get_field($field_name, $post_id);

				$product_id = 0;

				if (is_object($product) && isset($product->ID)) {
					$product_id = (int) $product->ID;
				} elseif (is_numeric($product)) {
					$product_id = (int) $product;
				} elseif (is_array($product) && isset($product['ID'])) {
					$product_id = (int) $product['ID'];
				}

				if (!$product_id) {
					return null;
				}

				$url = get_permalink($product_id);

				return $url ? esc_url_raw($url) : null;
			},
		)
	);
});
