<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$article_cats = get_the_category();
$article_category = $article_cats ? $article_cats[0] : null;
$article_plain = wp_strip_all_tags( strip_shortcodes( get_the_content() ) );
$article_words = preg_split( '/\s+/u', trim( $article_plain ), -1, PREG_SPLIT_NO_EMPTY );
$article_minutes = max( 1, (int) ceil( ( is_array( $article_words ) ? count( $article_words ) : 0 ) / 200 ) );

/* Build a short native Table of Contents from the already-rendered public post.
 * Keep existing heading IDs, and only add IDs for headings without one.
 * No extra database query or front-end JavaScript is required. */
$article_toc = array();
$article_rendered = apply_filters( 'the_content', get_the_content() );
$article_heading_number = 0;
$article_toc_rendered = preg_replace_callback(
    '/<h([23])([^>]*)>(.*?)<\/h\1>/is',
    static function( $heading ) use ( &$article_toc, &$article_heading_number ) {
        $plain = trim( wp_strip_all_tags( $heading[3] ) );
        if ( '' === $plain || count( $article_toc ) >= 12 ) {
            return $heading[0];
        }
        $attributes = $heading[2];
        $id = '';
        if ( preg_match( '/\bid\s*=\s*(["\'])(.*?)\1/i', $attributes, $existing ) ) {
            $id = $existing[2];
        } else {
            ++$article_heading_number;
            $id = 'pvdak-article-section-' . $article_heading_number;
            $attributes .= ' id="' . esc_attr( $id ) . '"';
        }
        if ( '' !== $id ) {
            $article_toc[] = array( 'id' => $id, 'label' => $plain, 'level' => (int) $heading[1] );
        }
        return '<h' . $heading[1] . $attributes . '>' . $heading[3] . '</h' . $heading[1] . '>';
    },
    $article_rendered
);
if ( null !== $article_toc_rendered ) {
    $article_rendered = $article_toc_rendered;
} else {
    $article_toc = array();
}

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
        <span class="pvdak-article-eyebrow">مجله حقوقی</span>
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
            <?php if ( comments_open() ): ?>
                <span class="pvdak-article-dot" aria-hidden="true"></span>
                <a class="pvdak-article-comments-link" href="#respond">ثبت دیدگاه</a>
            <?php endif; ?>
        </div>
        <nav class="pvdak-article-jumpnav" aria-label="دسترسی سریع به بخش‌های مقاله">
          <a href="#pvdak-article-text">شروع مطالعه <span aria-hidden="true">↓</span></a>
          <?php if ( comments_open() ): ?><a href="#respond">دیدگاه‌ها <span aria-hidden="true">↓</span></a><?php endif; ?>
        </nav>
    </header>
    <?php if ( has_post_thumbnail() && ! post_password_required() ): ?>
        <figure class="pvdak-article-cover">
            <?php the_post_thumbnail( 'large', array( 'class' => 'featured-image', 'loading' => 'eager', 'fetchpriority' => 'high', 'alt' => '' ) ); ?>
        </figure>
    <?php endif; ?>
    <div id="pvdak-article-text" class="pvdak-article-reading" tabindex="-1">
        <?php if ( count( $article_toc ) >= 2 ): ?>
          <nav class="pvdak-article-toc" aria-label="فهرست مطالب این مقاله">
            <div class="pvdak-article-toc-heading"><span aria-hidden="true">☷</span><strong>در این مقاله می‌خوانید</strong><span><?php echo esc_html( number_format_i18n( count( $article_toc ) ) ); ?> بخش</span></div>
            <ol>
              <?php foreach ( $article_toc as $entry ): ?>
                <li class="<?php echo 3 === $entry['level'] ? 'is-subsection' : ''; ?>"><a href="#<?php echo esc_attr( $entry['id'] ); ?>"><?php echo esc_html( $entry['label'] ); ?></a></li>
              <?php endforeach; ?>
            </ol>
          </nav>
        <?php endif; ?>
        <div class="entry-content">
            <?php echo str_replace( ']]>', ']]&gt;', $article_rendered ); // Filtered the_content output, same trust boundary as the_content(). ?>
            <?php wp_link_pages( array( 'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'صفحه‌های محتوا', 'pvdak' ) . '">', 'after' => '</nav>' ) ); ?>
        </div>
        <div class="pvdak-article-service-note" role="note">
          <span aria-hidden="true">ⓘ</span><p>این مطلب برای آگاهی عمومی تهیه شده است و جایگزین بررسی اختصاصی یک پرونده حقوقی نیست.</p>
        </div>
        <footer class="entry-taxonomies">
            <strong>دسته‌بندی مطلب</strong>
            <div class="pvdak-article-taxonomy-links"><?php the_category( ' ' ); ?></div>
            <?php if ( has_tag() ): ?><div class="pvdak-article-tags"><?php the_tags( '<strong>برچسب‌ها</strong><div>', ' ', '</div>' ); ?></div><?php endif; ?>
        </footer>
    </div>
</article>
