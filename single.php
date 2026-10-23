<?php
/**
 * Single editorial post template: full reading layout, related posts and WP comments.
 * Neither legal intake nor user-profile forms are enabled here.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<main id="main-content" class="site-main container pvdak-article-page" tabindex="-1">
<?php while ( have_posts() ) : the_post(); ?>
    <?php get_template_part( 'template-parts/content', 'single' ); ?>
    <?php get_template_part( 'template-parts/related-posts' ); ?>
    <div class="pvdak-article-post-navigation"><?php
        the_post_navigation( array(
            'prev_text' => '<span class="pvdak-post-nav-label">مقاله قبلی</span><strong>%title</strong>',
            'next_text' => '<span class="pvdak-post-nav-label">مقاله بعدی</span><strong>%title</strong>',
        ) );
    ?></div>
    <?php if ( comments_open() || get_comments_number() ) : ?>
        <?php comments_template(); ?>
    <?php else : ?>
        <section class="pvdak-article-comments-closed" aria-label="وضعیت نظرات">
            <h2>دیدگاه‌ها</h2>
            <p>ثبت دیدگاه برای این مقاله در حال حاضر غیرفعال است.</p>
        </section>
    <?php endif; ?>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>
