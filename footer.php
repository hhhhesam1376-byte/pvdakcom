<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<footer class="site-footer">
	<div class="container footer-inner">
		<p><?php echo esc_html( get_bloginfo( 'name' ) ); ?> &copy; <?php echo esc_html( wp_date( 'Y' ) ); ?></p>
		<?php if ( has_nav_menu( 'footer' ) ) : ?>
			<nav aria-label="<?php esc_attr_e( 'منوی فوتر', 'pvdak' ); ?>">
				<?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'menu_class' => 'menu', 'fallback_cb' => false, 'depth' => 1 ) ); ?>
			</nav>
		<?php endif; ?>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
