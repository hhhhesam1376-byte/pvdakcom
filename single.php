<?php
/**
 * PVDak editorial article: text and actual comments first, suggested posts after.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<main id="main-content" class="site-main container pvdak-article-page" tabindex="-1">
<?php while ( have_posts() ) : the_post(); ?>
    <?php get_template_part( 'template-parts/content', 'single' ); ?>
    <?php // The native discussion form is near the article, not hidden after recommendations. ?>
    <?php comments_template(); ?>
    <?php get_template_part( 'template-parts/related-posts' ); ?>
    <div class="pvdak-article-post-navigation"><?php
        the_post_navigation( array(
            'prev_text' => '<span class="pvdak-post-nav-label">مقاله قبلی</span><strong>%title</strong>',
            'next_text' => '<span class="pvdak-post-nav-label">مقاله بعدی</span><strong>%title</strong>',
        ) );
    ?></div>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>
