<?php
/**
 * Title: بخش محتوای ساده
 * Slug: pvdak/content-section
 * Categories: pvdak, text
 * Inserter: true
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
<!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo esc_html__( 'عنوان بخش', 'pvdak' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html__( 'متن این بخش را با محتوای خود جایگزین کنید. می‌توانید این الگو را در هر برگه استفاده کنید.', 'pvdak' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
