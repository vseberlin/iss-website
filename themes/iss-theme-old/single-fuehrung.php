<?php
if (!defined('ABSPATH')) {
	exit;
}

get_header();

$post_id       = get_the_ID();
$post_slug     = (string) get_post_field('post_name', $post_id);
$featured_id   = (int) get_post_thumbnail_id($post_id);
$featured_html = $featured_id ? wp_get_attachment_image($featured_id, 'large', false, array('class' => 'iss-tour-story__image')) : '';
$summary       = (string) get_post_meta($post_id, 'kurztext', true);
$price         = trim((string) get_post_meta($post_id, 'price', true));
$product       = get_post_meta($post_id, 'product', true);
$booking_url   = '';

if (is_numeric($product)) {
	$booking_url = get_permalink((int) $product) ?: '';
} elseif (is_object($product) && isset($product->ID)) {
	$booking_url = get_permalink((int) $product->ID) ?: '';
}

$content        = (string) get_post_field('post_content', $post_id);
$column_matches = array();
$columns        = array();

if (preg_match_all('/\[col[^\]]*\](.*?)\[\/col\]/is', $content, $column_matches)) {
	$columns = array_map(
		static function (string $column_html): string {
			$column_html = trim($column_html);

			return $column_html === '' ? '' : wpautop($column_html);
		},
		$column_matches[1]
	);
}

$uses_story_layout = $post_slug === 'judische-unternehmen-im-historischen-berlin-bustour' && count($columns) >= 3;
?>

<main class="wp-block-group iss-page-main iss-tour-story-page">
	<?php if ($uses_story_layout) : ?>
		<section class="iss-tour-story iss-tour-story--hero">
			<div class="iss-tour-story__panel iss-tour-story__panel--accent">
				<div class="iss-tour-story__panel-inner">
					<p class="iss-tour-story__eyebrow">Historische Bustour</p>
					<h1 class="iss-tour-story__title"><?php the_title(); ?></h1>

					<?php if ($summary !== '') : ?>
						<p class="iss-tour-story__summary"><?php echo esc_html($summary); ?></p>
					<?php endif; ?>

					<div class="iss-tour-story__actions">
						<?php if ($booking_url) : ?>
							<a class="iss-button" href="<?php echo esc_url($booking_url); ?>">Bustour buchen</a>
						<?php endif; ?>
						<a class="iss-button iss-button--secondary" href="#iss-tour-story-details">Mehr erfahren</a>
					</div>
				</div>
			</div>

			<div class="iss-tour-story__media">
				<?php if ($featured_html) : ?>
					<?php echo $featured_html; ?>
				<?php endif; ?>
			</div>
		</section>

		<section id="iss-tour-story-details" class="iss-tour-story iss-tour-story--details">
			<div class="iss-tour-story__media iss-tour-story__media--soft">
				<?php if ($featured_html) : ?>
					<?php echo $featured_html; ?>
				<?php endif; ?>
			</div>

			<div class="iss-tour-story__panel iss-tour-story__panel--light">
				<div class="iss-tour-story__panel-inner iss-tour-story__richtext">
					<?php echo wp_kses_post($columns[0]); ?>
				</div>
			</div>
		</section>

		<section class="iss-tour-story-grid">
			<div class="iss-tour-story-card iss-tour-story-card--narrative iss-tour-story__richtext">
				<?php echo wp_kses_post($columns[1]); ?>
			</div>

			<aside class="iss-tour-story-card iss-tour-story-card--meta iss-tour-story__richtext">
				<?php echo wp_kses_post($columns[2]); ?>

				<?php if ($price !== '') : ?>
					<p class="iss-tour-story__price">Preis: <?php echo esc_html($price); ?> &euro;</p>
				<?php endif; ?>

				<?php if ($booking_url) : ?>
					<p class="iss-tour-story__booking">
						<a class="iss-button" href="<?php echo esc_url($booking_url); ?>">Zur Buchung</a>
					</p>
				<?php endif; ?>
			</aside>
		</section>
	<?php else : ?>
		<div class="iss-tour-default">
			<?php if ($featured_html) : ?>
				<div class="iss-tour-default__media"><?php echo $featured_html; ?></div>
			<?php endif; ?>

			<div class="iss-tour-default__body">
				<h1 class="iss-tour-default__title"><?php the_title(); ?></h1>

				<?php if ($summary !== '') : ?>
					<p class="iss-tour-default__summary"><?php echo esc_html($summary); ?></p>
				<?php endif; ?>

				<div class="iss-tour-default__content">
					<?php the_content(); ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</main>

<?php
get_footer();
