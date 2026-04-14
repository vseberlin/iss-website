<?php
get_header();

function industriesalon_get_booking_url($booking_type, $product) {
	if (!$booking_type || !$product) {
		return '';
	}

	if (is_object($product) && isset($product->ID)) {
		return get_permalink($product->ID);
	}

	if (is_numeric($product)) {
		return get_permalink((int) $product);
	}

	if (is_string($product) && filter_var($product, FILTER_VALIDATE_URL)) {
		return $product;
	}

	return '';
}
?>

<main class="wp-block-group iss-page-main iss-page-main--fuehrung-archive">
	<div class="wp-block-group alignwide fuehrung-layout iss-fuehrung-layout">
		<header class="fuehrung-archive-header">
			<h1><?php post_type_archive_title(); ?></h1>
		</header>

		<?php if (have_posts()) : ?>
			<div class="fuehrung-grid">
				<?php while (have_posts()) : the_post();

					$dauer        = get_field('dauer');
					$treffpunkt   = get_field('treffpunkt');
					$kurztext     = get_field('kurztext');
					$booking_type = get_field('booking_type');
					$product      = get_field('product');
					$image        = get_field('image');

					$booking_url = industriesalon_get_booking_url($booking_type, $product);

					$image_html = '';

					if (is_array($image) && !empty($image['ID'])) {
						$image_html = wp_get_attachment_image($image['ID'], 'large');
					} elseif (is_numeric($image)) {
						$image_html = wp_get_attachment_image((int) $image, 'large');
					} elseif (has_post_thumbnail()) {
						$image_html = get_the_post_thumbnail(get_the_ID(), 'large');
					}
					?>
					<article class="fuehrung-card">
						<?php if ($image_html) : ?>
							<a class="fuehrung-card-image" href="<?php the_permalink(); ?>">
								<?php echo $image_html; ?>
							</a>
						<?php endif; ?>

						<div class="fuehrung-card-content">
							<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

							<?php if ($kurztext) : ?>
								<p><?php echo esc_html($kurztext); ?></p>
							<?php elseif (get_the_excerpt()) : ?>
								<p><?php echo esc_html(get_the_excerpt()); ?></p>
							<?php endif; ?>

							<?php if ($dauer || $treffpunkt) : ?>
								<div class="fuehrung-meta">
									<?php if ($dauer) : ?>
										<p><strong>Dauer:</strong> <?php echo esc_html($dauer); ?></p>
									<?php endif; ?>

									<?php if ($treffpunkt) : ?>
										<p><strong>Treffpunkt:</strong> <?php echo esc_html($treffpunkt); ?></p>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<div class="iss-tour-actions">
								<div class="iss-tour-action iss-tour-action--secondary">
									<a class="iss-button iss-button--secondary" href="<?php the_permalink(); ?>">
										Mehr erfahren
									</a>
								</div>

								<?php if ($booking_url) : ?>
									<div class="iss-tour-action">
										<a class="iss-button" href="<?php echo esc_url($booking_url); ?>">
											Termin buchen
										</a>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</article>
				<?php endwhile; ?>
			</div>

			<?php the_posts_pagination(); ?>

		<?php else : ?>
			<p>Zurzeit sind keine Führungen eingetragen.</p>
		<?php endif; ?>
	</div>
</main>

<?php get_footer(); ?>
