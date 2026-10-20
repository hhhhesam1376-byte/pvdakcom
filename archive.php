<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<main id="main-content" class="site-main container" tabindex="-1">
<header class="archive-header">
	<?php the_archive_title( '<h1>', '</h1>' ); ?>
	<?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
</header>
<?php get_template_part( 'template-parts/loop' ); ?>
</main>
<?php get_footer(); ?>
