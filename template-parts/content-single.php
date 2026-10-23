<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$article_cats = get_the_category();
$article_category = $article_cats ? $article_cats[0] : null;
$article_plain = wp_strip_all_tags( strip_shortcodes( get_the_content() ) );
$article_words = preg_split( '/\s+/u', trim( $article_plain ), -1, PREG_SPLIT_NO_EMPTY );
$article_minutes = max( 1, (int) ceil( ( is_array( $article_words ) ? count( $article_words ) : 0 ) / 200 ) );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-entry pvdak-editorial-article' ); ?>>
    <header class="pvdak-article-hero">
        <nav class="pvdak-article-breadcrumb" aria-label="مسیر صفحه">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">صفحه اصلی</a>
            <span aria-hidden="true">/</span>
            <?php if ( $article_category ): ?>
                <a href="<?php echo esc_url( get_category_link( $article_category->term_id ) ); ?>"><?php echo esc_html( $article_category->name ); ?></a>
                <span aria-hidden="true">/</span>
            <?php endif; ?>
            <span aria-current="page"><?php echo esc_html( get_the_title() ); ?></span>
        </nav>
        <span class="pvdak-article-eyebrow">از مجله حقوقی</span>
        <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
        <?php if ( has_excerpt() ): ?>
            <div class="pvdak-article-deck"><?php the_excerpt(); ?></div>
        <?php endif; ?>
        <div class="pvdak-article-byline">
            <time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
            <span class="pvdak-article-dot" aria-hidden="true"></span>
            <span>زمان مطالعه: <?php echo esc_html( $article_minutes ); ?> دقیقه</span>
            <?php if ( $article_category ): ?>
                <span class="pvdak-article-dot" aria-hidden="true"></span>
                <a href="<?php echo esc_url( get_category_link( $article_category->term_id ) ); ?>"><?php echo esc_html( $article_category->name ); ?></a>
            <?php endif; ?>
        </div>
    </header>
    <?php if ( has_post_thumbnail() && ! post_password_required() ): ?>
        <figure class="pvdak-article-cover">
            <?php the_post_thumbnail( 'large', array( 'class' => 'featured-image', 'loading' => 'eager', 'fetchpriority' => 'high', 'alt' => '' ) ); ?>
        </figure>
    <?php endif; ?>
    <div class="pvdak-article-reading">
        <div class="entry-content">
            <?php the_content(); ?>
            <?php wp_link_pages( array( 'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'صفحه‌های محتوا', 'pvdak' ) . '">', 'after' => '</nav>' ) ); ?>
        </div>
        <footer class="entry-taxonomies">
            <strong>دسته‌بندی مطلب</strong>
            <div class="pvdak-article-taxonomy-links"><?php the_category( ' ' ); ?></div>
            <?php if ( has_tag() ): ?><div class="pvdak-article-tags"><?php the_tags( '<strong>برچسب‌ها</strong><div>', ' ', '</div>' ); ?></div><?php endif; ?>
        </footer>
    </div>
</article>
