<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<main id="main-content" class="site-main container" tabindex="-1">
<section class="hero">
	<p class="eyebrow">404</p>
	<h1><?php esc_html_e( 'این صفحه پیدا نشد', 'pvdak' ); ?></h1>
	<p><?php esc_html_e( 'از جست‌وجو استفاده کنید یا به صفحه اصلی برگردید.', 'pvdak' ); ?></p>
	<?php get_search_form(); ?>
	<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'صفحه اصلی', 'pvdak' ); ?></a>
</section>
</main>
<?php get_footer(); ?>
