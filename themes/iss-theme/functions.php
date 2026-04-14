<?php
/**
 * ISS Theme functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme setup
 */
add_action( 'after_setup_theme', function () {
	// Let WordPress manage the document title.
	add_theme_support( 'title-tag' );

	// Featured images for hero covers and cards.
	add_theme_support( 'post-thumbnails' );

	// Core block theme features.
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );

	// Load editor stylesheet.
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
} );

/**
 * Optional editor cleanup.
 * Keep this only if you want to restrict editor controls later.
 */
add_filter( 'block_editor_settings_all', function ( $settings ) {
	// Example:
	// $settings['disableCustomColors'] = true;
	// $settings['disableCustomGradients'] = true;

	return $settings;
} );

/**
 * Frontend styles
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'iss-theme-style',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);
} );

/**
 * Register dynamic blocks.
 */
add_action( 'init', function () {
	register_block_type(
		'iss/post-header',
		array(
			'render_callback' => 'iss_render_post_header_block',
		)
	);
} );

/**
 * Render automatic post header:
 * - featured image present => split layout
 * - no featured image => simple layout
 */
function iss_render_post_header_block( $attributes = array(), $content = '', $block = null ) {
	if ( ! is_singular( 'post' ) ) {
		return '';
	}

	$post_id = get_the_ID();

	if ( ! $post_id ) {
		return '';
	}

	$title   = get_the_title( $post_id );
	$excerpt = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : '';

	$category_objects = get_the_category( $post_id );
	$filtered         = array();

	if ( ! empty( $category_objects ) ) {
		foreach ( $category_objects as $cat ) {
			if ( 'uncategorized' === strtolower( $cat->slug ) ) {
				continue;
			}

			$filtered[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_category_link( $cat->term_id ) ),
				esc_html( $cat->name )
			);
		}
	}

	$categories = implode( ' · ', $filtered );

	ob_start();

	if ( has_post_thumbnail( $post_id ) ) :
		?>
		<div class="wp-block-group iss-post-split alignwide">
			<div class="wp-block-columns are-vertically-aligned-center iss-post-split__cols">

				<div class="wp-block-column is-vertically-aligned-center iss-post-split__content" style="flex-basis:56%">
					<?php if ( $categories ) : ?>
						<div class="iss-post-meta"><?php echo wp_kses_post( $categories ); ?></div>
					<?php endif; ?>

					<h1 class="iss-post-title"><?php echo esc_html( $title ); ?></h1>

					<?php if ( $excerpt ) : ?>
						<div class="iss-post-standfirst">
							<p><?php echo esc_html( $excerpt ); ?></p>
						</div>
					<?php endif; ?>
				</div>

				<div class="wp-block-column is-vertically-aligned-center iss-post-split__media" style="flex-basis:44%">
					<div class="iss-post-featured-image">
						<?php echo get_the_post_thumbnail( $post_id, 'large' ); ?>
					</div>
				</div>

			</div>
		</div>
		<?php
	else :
			$simple_classes = 'wp-block-group iss-post-head iss-post-head--simple';

	if ( empty( $excerpt ) ) {
		$simple_classes .= ' iss-post-head--noexcerpt';
	}
	?>
	<div class="<?php echo esc_attr( $simple_classes ); ?>">
		<div class="wp-block-group iss-post-head__inner">

			<?php if ( $categories ) : ?>
				<div class="iss-post-meta"><?php echo wp_kses_post( $categories ); ?></div>
			<?php endif; ?>

			<h1 class="iss-post-title"><?php echo esc_html( $title ); ?></h1>

			<?php if ( $excerpt ) : ?>
				<div class="iss-post-standfirst">
					<p><?php echo esc_html( $excerpt ); ?></p>
				</div>
			<?php endif; ?>

		</div>
	</div>
	<?php
endif;

	return ob_get_clean();
}