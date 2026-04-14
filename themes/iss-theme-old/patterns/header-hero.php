<?php
/**
 * Title: Header Hero
 * Slug: iss-theme/header-hero
 * Description: Overlay header for pages with a featured-image hero.
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: false
 */
?>
<!-- wp:group {"metadata":{"name":"Header Hero"},"align":"full","className":"iss-header iss-header--overlay site-header-overlay site-header-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","right":"var:preset|spacing|medium","left":"var:preset|spacing|medium"}}},"layout":{"inherit":true,"type":"constrained"}} -->
<div class="wp-block-group alignfull iss-header iss-header--overlay site-header-overlay site-header-hero" style="padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
	<!-- wp:group {"align":"wide","className":"iss-header__inner site-header-overlay__inner site-header-hero__inner","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center"}} -->
	<div class="wp-block-group alignwide iss-header__inner site-header-overlay__inner site-header-hero__inner">
		<!-- wp:site-logo {"width":220,"className":"iss-header__brand"} /-->
		<!-- wp:navigation {"openSubmenusOnClick":true,"icon":"menu","className":"iss-header__nav","style":{"spacing":{"blockGap":"var:preset|spacing|small"}},"fontSize":"small"} /-->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
