<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<main id="main-content" class="site-main container" tabindex="-1">
<?php if ( 'page' === get_option( 'show_on_front' ) ) :
	while ( have_posts() ) : the_post();
		get_template_part( 'template-parts/content', 'page' );
	endwhile;
else : ?>
	<section class="hero">
		<p class="eyebrow">PVDAK</p>
		<h1><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
		<p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
	</section>
	<h2><?php esc_html_e( 'آخرین نوشته‌ها', 'pvdak' ); ?></h2>
	<?php get_template_part( 'template-parts/loop' ); ?>
<?php endif; ?>
</main>
<?php get_footer(); ?>
