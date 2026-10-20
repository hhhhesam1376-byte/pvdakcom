<?php
/**
 * Template Name: تمام‌عرض
 * Template Post Type: page
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<main id="main-content" class="site-main container" tabindex="-1">
<?php while ( have_posts() ) : the_post();
	get_template_part( 'template-parts/content', 'page' );
endwhile; ?>
</main>
<?php get_footer(); ?>
