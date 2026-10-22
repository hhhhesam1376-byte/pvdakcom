<?php
/**
 * Dedicated archive for editorial post Categories (magazine, news, notices).
 * Legal-topic landings remain standard child PAGES, not category archives.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$term = get_queried_object();
if ( ! $term instanceof WP_Term || 'category' !== $term->taxonomy ) {
    get_template_part( 'archive' );
    return;
}
$category_id = (int) $term->term_id;
$related = array();
foreach ( array(
    'magazine_category' => 'مجله حقوقی',
    'notice_category'   => 'اطلاعیه‌ها',
    'news_category'     => 'اخبار پویش',
) as $field => $label ) {
    $id = pvdak_home_selected_category( $field );
    if ( ! $id || $id === $category_id || isset( $related[ $id ] ) ) { continue; }
    $link = get_category_link( $id );
    if ( ! is_wp_error( $link ) ) {
        $related[ $id ] = array( 'label' => $label, 'link' => $link );
    }
}
get_header();
?>
<main id="main-content" class="site-main container pvdak-archive" tabindex="-1">
    <header class="pvdak-archive-hero">
        <nav class="pvdak-archive-breadcrumb" aria-label="مسیر صفحه">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">صفحه اصلی</a>
            <span aria-hidden="true">←</span>
            <span aria-current="page"><?php echo esc_html( single_cat_title( '', false ) ); ?></span>
        </nav>
        <span class="pvdak-archive-eyebrow">مطالب و محتوای پویش</span>
        <h1><?php echo esc_html( single_cat_title( '', false ) ); ?></h1>
        <?php $description = term_description( $term, 'category' ); ?>
        <?php if ( $description ) : ?>
            <div class="pvdak-archive-description"><?php echo wp_kses_post( $description ); ?></div>
        <?php else: ?>
            <p>مطالب منتشرشده در این بخش را بخوانید و برای آشنایی بیشتر وارد صفحه هر مطلب شوید.</p>
        <?php endif; ?>
    </header>
    <?php if ( $related ) : ?>
        <nav class="pvdak-editorial-categories" aria-label="سایر بخش‌های محتوایی">
            <span>بخش‌های مرتبط:</span>
            <?php foreach ( $related as $item ) : ?>
                <a href="<?php echo esc_url( $item['link'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
    <div class="pvdak-archive-heading">
        <h2>آخرین مطالب</h2>
        <span>مرتب‌شده بر اساس تاریخ انتشار</span>
    </div>
    <?php get_template_part( 'template-parts/loop' ); ?>
</main>
<?php get_footer(); ?>
