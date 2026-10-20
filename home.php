<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<main id="main-content" class="site-main container" tabindex="-1">
<h1><?php
$posts_page = (int) get_option( 'page_for_posts' );
echo esc_html( $posts_page ? get_the_title( $posts_page ) : __( 'آخرین نوشته‌ها', 'pvdak' ) );
?></h1>
<?php get_template_part( 'template-parts/loop' ); ?>
</main>
<?php get_footer(); ?>
