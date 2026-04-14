<?php
/**
 * Front page template using plugin PHP calls instead of shortcodes.
 *
 * Keeps existing CSS hooks and markup structure.
 */

if (! defined('ABSPATH')) {
	exit;
}

$iss_control = class_exists('Industriesalon_Steuerung') ? Industriesalon_Steuerung::instance() : null;

$iss_render_field = static function (array $args = []) use ($iss_control): string {
	if (! $iss_control || ! method_exists($iss_control, 'render_field')) {
		return '';
	}
	return $iss_control->render_field($args);
};

$iss_render_hours = static function (string $type = 'public', string $title = '') use ($iss_control): string {
	if (! $iss_control || ! method_exists($iss_control, 'render_hours')) {
		return '';
	}
	return $iss_control->render_hours($type, $title);
};

$iss_render_contact = static function (string $title = '') use ($iss_control): string {
	if (! $iss_control || ! method_exists($iss_control, 'render_contact')) {
		return '';
	}
	return $iss_control->render_contact($title);
};

$iss_render_prices = static function (string $title = '') use ($iss_control): string {
	if (! $iss_control || ! method_exists($iss_control, 'render_prices')) {
		return '';
	}
	return $iss_control->render_prices($title);
};

$iss_render_faq = static function (string $title = '') use ($iss_control): string {
	if (! $iss_control || ! method_exists($iss_control, 'render_faq')) {
		return '';
	}
	return $iss_control->render_faq($title);
};

if (function_exists('block_template_part')) {
	block_template_part('header');
} else {
	get_header();
}
?>

<main class="wp-block-group site-main">
	<div class="wp-block-group iss-front-banner-slot">
		<div class="wp-block-group container">
			<?php
			if (function_exists('iss_render_notice')) {
				echo iss_render_notice('front_page_banner'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
	</div>

	<div class="wp-block-cover iss-front-hero iss-front-hero--with-banner" style="min-height:100vh">
		<span aria-hidden="true" class="wp-block-cover__background has-eerie-black-background-color has-background-dim-40 has-background-dim"></span>
		<img
			class="wp-block-cover__image-background"
			alt=""
			src="https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&amp;fit=crop&amp;w=1800&amp;q=80"
			data-object-fit="cover"
		/>
		<div class="wp-block-cover__inner-container">
			<div class="wp-block-group iss-front-hero__content">
				<p class="iss-kicker">Besucherzentrum für Industriegeschichte</p>

				<h1>Geschichte sehen. Orte verstehen. Schöneweide neu lesen.</h1>

				<?php
				echo $iss_render_field([
					'key' => 'general.tagline',
					'tag' => 'p',
				]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>

				<div class="wp-block-group iss-front-hero__meta">
					<?php
					echo $iss_render_field([
						'key' => 'general.street',
						'tag' => 'span',
					]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

					echo $iss_render_field([
						'key' => 'general.postal_code',
						'tag' => 'span',
					]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

					echo $iss_render_field([
						'key' => 'general.city',
						'tag' => 'span',
					]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>

				<div class="wp-block-buttons">
					<div class="wp-block-button">
						<a class="wp-block-button__link wp-element-button" href="#programm">Programm ansehen</a>
					</div>

					<div class="wp-block-button iss-button--ghost">
						<a class="wp-block-button__link wp-element-button" href="#besuch">Besuch planen</a>
					</div>

					<div class="wp-block-button iss-button--ghost">
						<a class="wp-block-button__link wp-element-button" href="#kontakt">Kontakt</a>
					</div>
				</div>
			</div>
		</div>
	</div>

	<section id="programm" class="wp-block-group section">
		<div class="wp-block-group container">
			<h2>Programm</h2>

			<div class="wp-block-columns">
				<div class="wp-block-column">
					<div class="wp-block-group iss-front-card">
						<h3>Führungen</h3>
						<p>Öffentliche Termine, Gruppenangebote und Sonderformate mit direktem Einstieg zur Buchung.</p>
						<div class="wp-block-buttons iss-front-card__actions">
							<div class="wp-block-button">
								<a class="wp-block-button__link wp-element-button" href="/fuehrungen/">Zu den Führungen</a>
							</div>
							<?php
							echo $iss_render_field([
								'key'   => 'contact.booking_url',
								'tag'   => 'div',
								'link'  => 'url',
								'label' => '',
							]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</div>
					</div>
				</div>

				<div class="wp-block-column">
					<div class="wp-block-group iss-front-card">
						<h3>Veranstaltungen</h3>
						<p>Kalender, einzelne Highlights und Formate für Besuchende, Nachbarschaft und Partner.</p>
						<div class="wp-block-buttons iss-front-card__actions">
							<div class="wp-block-button">
								<a class="wp-block-button__link wp-element-button" href="/veranstaltungen/">Zum Kalender</a>
							</div>
						</div>
					</div>
				</div>

				<div class="wp-block-column">
					<div class="wp-block-group iss-front-card">
						<h3>Archiv &amp; Mediathek</h3>
						<p>Kurzer Einstieg in Sammlungen, Themen und digitale Zugänge ohne Informationsüberladung.</p>
						<div class="wp-block-buttons iss-front-card__actions">
							<div class="wp-block-button">
								<a class="wp-block-button__link wp-element-button" href="/archiv/">Ins Archiv</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section id="besuch" class="wp-block-group section">
		<div class="wp-block-group container">
			<h2>Ihr Besuch</h2>

			<div class="wp-block-columns iss-front-grid">
				<div class="wp-block-column">
					<div class="wp-block-group iss-front-card iss-front-card--hours">
						<h3>Öffnungszeiten</h3>
						<?php
						echo $iss_render_hours('public'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
				</div>

				<div class="wp-block-column">
					<div class="wp-block-group iss-front-card iss-front-card--arrival">
						<h3>Anfahrt</h3>
						<?php
						echo $iss_render_field([
							'key' => 'general.street',
							'tag' => 'p',
						]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
						<div class="wp-block-group iss-front-card__address-row">
							<?php
							echo $iss_render_field([
								'key' => 'general.postal_code',
								'tag' => 'span',
							]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

							echo $iss_render_field([
								'key' => 'general.city',
								'tag' => 'span',
							]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</div>
						<?php
						echo $iss_render_field([
							'key' => 'general.arrival',
							'tag' => 'p',
						]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

						echo $iss_render_field([
							'key'  => 'maps.google_maps_url',
							'tag'  => 'p',
							'link' => 'url',
						]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
				</div>

				<div class="wp-block-column">
					<div class="wp-block-group iss-front-card iss-front-card--prices">
						<h3>Tickets &amp; Preise</h3>
						<?php
						echo $iss_render_prices(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
				</div>
			</div>

			<div class="wp-block-group iss-front-visit-note">
				<?php
				echo $iss_render_field([
					'key' => 'general.visit_note',
					'tag' => 'p',
				]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
		</div>
	</section>

	<section id="kontakt" class="wp-block-group section section--contact">
		<div class="wp-block-group container">
			<div class="wp-block-columns are-vertically-aligned-top">
				<div class="wp-block-column" style="flex-basis:55%">
					<h2>Kontakt</h2>
					<?php
					echo $iss_render_contact(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>

				<div class="wp-block-column" style="flex-basis:45%">
					<h2>Fragen vor dem Besuch</h2>
					<?php
					echo $iss_render_faq(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
			</div>
		</div>
	</section>
</main>

<?php
if (function_exists('block_template_part')) {
	block_template_part('footer');
} else {
	get_footer();
}
