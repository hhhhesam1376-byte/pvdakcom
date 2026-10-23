<?php
/**
 * Related editorial Posts, by shared WordPress categories.
 * Read-only, published content; never leaks draft posts.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$current_id = get_the_ID();
$category_ids = wp_get_post_categories( $current_id );
$related = $category_ids ? get_posts( array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'post__not_in' => array( $current_id ),
    'category__in' => array_map( 'absint', $category_ids ),
    'posts_per_page' => 3,
    'orderby' => 'date',
    'order' => 'DESC',
    'ignore_sticky_posts' => true,
    'suppress_filters' => false,
    'no_found_rows' => true,
) ) : array();
if ( ! $related ) { return; }
?>
<section class="pvdak-related-articles" aria-labelledby="pvdak-related-title">
    <div class="pvdak-related-heading">
        <div><h2 id="pvdak-related-title">مقالات مرتبط</h2></div>
        <span>مقاله‌های دیگر از همین موضوع</span>
    </div>
    <div class="pvdak-related-grid">
        <?php foreach ( $related as $item ): ?>
            <a class="pvdak-related-card" href="<?php echo esc_url( get_permalink( $item->ID ) ); ?>" aria-label="<?php echo esc_attr( 'مطالعه مقاله: ' . get_the_title( $item->ID ) ); ?>">
                <span class="pvdak-related-picture" aria-hidden="true">
                    <?php if ( has_post_thumbnail( $item->ID ) ): ?>
                        <?php echo get_the_post_thumbnail( $item->ID, 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
                    <?php else: ?><span class="pvdak-related-image-fallback"></span><?php endif; ?>
                </span>
                <span class="pvdak-related-card-body">
                    <span class="pvdak-related-date"><?php echo esc_html( get_the_date( '', $item->ID ) ); ?></span>
                    <strong><?php echo esc_html( get_the_title( $item->ID ) ); ?></strong>
                    <span class="pvdak-related-excerpt"><?php echo esc_html( pvdak_home_post_summary( $item, 18 ) ); ?></span>
                    <span class="pvdak-related-cta">مطالعه مقاله <span aria-hidden="true">←</span></span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
