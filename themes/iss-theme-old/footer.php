<?php
if (!defined('ABSPATH')) {
	exit;
}

if (function_exists('block_template_part')) {
	block_template_part('footer');
}
?>
<?php wp_footer(); ?>
</body>
</html>
