<?php
/**
 * Template Name: فهرست لندینگ‌های حقوقی
 * Template Post Type: page
 * Description: Landing hub displaying all published child pages.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post();
    $children = pvdak_legal_topic_pages( get_the_ID() );
?>
<main id="main-content" class="pvdak-legal-landing pvdak-legal-index container" tabindex="-1">
    <nav class="pvdak-legal-breadcrumbs" aria-label="مسیر صفحه">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">صفحه اصلی</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page"><?php the_title(); ?></span>
    </nav>
    <header class="pvdak-legal-hero">
        <div class="pvdak-legal-hero-content">
            <span class="pvdak-legal-label">پویش وکلای دادگستری کشور</span>
            <h1><?php the_title(); ?></h1>
            <?php if ( has_excerpt() ) : ?><p><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
        </div>
    </header>
    <?php if ( '' !== trim( get_the_content() ) ): ?>
    <div class="pvdak-legal-article entry-content"><?php the_content(); ?></div>
    <?php endif; ?>
    <?php if ( $children ): ?>
    <section class="pvdak-legal-index-items" aria-label="لندینگ‌های حقوقی">
        <div class="pvdak-home-topics">
            <?php foreach ( $children as $child ): ?>
                <a class="pvdak-home-topic" href="<?php echo esc_url( get_permalink( $child->ID ) ); ?>">
                    <?php echo pvdak_legal_topic_svg( pvdak_legal_topic_icon( $child->ID ) ); ?>
                    <span><?php echo esc_html( get_the_title( $child->ID ) ); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php elseif ( current_user_can( 'edit_pages' ) ) : ?>
        <p class="pvdak-legal-admin-note">هنوز برگه فرزند منتشرشده‌ای برای این صفحه نداریم. از بخش برگه‌ها، لندینگ‌های مربوط را بسازید.</p>
    <?php endif; ?>
</main>
<?php
endwhile;
get_footer();
