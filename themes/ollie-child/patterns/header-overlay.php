<?php
/**
 * Title: Header Overlay
 * Slug: ollie-child/header-overlay
 * Description: Transparent header for pages with a hero image.
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: false
 */
?>
<!-- wp:group {"metadata":{"name":"Header Overlay"},"align":"full","className":"site-header-overlay","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium","right":"var:preset|spacing|medium","left":"var:preset|spacing|medium"}}},"layout":{"inherit":true,"type":"constrained"}} -->
<div class="wp-block-group alignfull site-header-overlay" style="padding-top:var(--wp--preset--spacing--medium);padding-right:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium);padding-left:var(--wp--preset--spacing--medium)">
	<!-- wp:group {"align":"wide","className":"site-header-overlay__inner","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center"}} -->
	<div class="wp-block-group alignwide site-header-overlay__inner">
		<!-- wp:site-logo {"width":220} /-->
		<!-- wp:navigation {"openSubmenusOnClick":true,"icon":"menu","style":{"spacing":{"blockGap":"var:preset|spacing|small"}},"fontSize":"small"} /-->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
