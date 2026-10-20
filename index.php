<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<main id="main-content" class="site-main container" tabindex="-1">
<h1><?php esc_html_e( 'آخرین نوشته‌ها', 'pvdak' ); ?></h1>
<?php get_template_part( 'template-parts/loop' ); ?>
</main>
<?php get_footer(); ?>
