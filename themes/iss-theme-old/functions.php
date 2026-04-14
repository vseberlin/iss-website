<?php
if (!defined('ABSPATH')) {
	exit;
}

add_action('after_setup_theme', function () {
    add_editor_style('style.css');
    remove_theme_support('core-block-patterns');
});

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
		'iss-theme',
		get_stylesheet_uri(),
		array(),
		(string) filemtime($theme_path . '/style.css')
	);

    // Gutenberg override removal: stop enqueuing asset CSS bundles that targeted
    // core block selectors. All front-end styles are consolidated in style.css
    // using opt-in custom classes.
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

add_filter('template_include', function (string $template): string {
	if (is_singular('fuehrung')) {
		$single_template = get_stylesheet_directory() . '/single-fuehrung.php';

		if (file_exists($single_template)) {
			return $single_template;
		}
	}

	return $template;
}, 20);

// Ensure theme patterns are registered even if auto-discovery is unavailable
add_action('init', function () {
	if (!function_exists('register_block_pattern')) {
		return;
	}
	$slug = 'iss-theme/tours-sections';
	if (!class_exists('WP_Block_Patterns_Registry') || !WP_Block_Patterns_Registry::get_instance()->is_registered($slug)) {
		$pattern_file = get_stylesheet_directory() . '/patterns/tours-sections.php';
		if (file_exists($pattern_file)) {
			ob_start();
			include $pattern_file;
			$content = (string) ob_get_clean();
			register_block_pattern($slug, array(
				'title' => 'Tours Sections (Featured, Regular, Group, Individual, Special)',
				'categories' => array('query'),
				'content' => $content,
			));
		}
	}
});
