<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<main id="main-content" class="site-main container" tabindex="-1">
<h1><?php
/* translators: %s: search terms. */
printf( esc_html__( 'نتایج جست‌وجو برای: %s', 'pvdak' ), esc_html( get_search_query( false ) ) );
?></h1>
<?php get_search_form(); ?>
<?php get_template_part( 'template-parts/loop' ); ?>
</main>
<?php get_footer(); ?>
