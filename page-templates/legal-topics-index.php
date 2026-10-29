<?php
/**
 * Template Name: فهرست لندینگ‌های حقوقی
 * Template Post Type: page
 * Description: Paginated hub of published, public child landing pages.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post();
    $topic_request = pvdak_legal_index_request();
    $topic_window = pvdak_legal_index_window( get_the_ID(), $topic_request['token'], $topic_request['direction'] );
    $children = $topic_window['pages'];
    $topic_base_url = get_permalink( get_the_ID() );
    $topic_prev_url = $topic_window['prev_cursor'] ?
        add_query_arg( 'pvdak_topics_before', $topic_window['prev_cursor'], $topic_base_url ) : '';
    $topic_next_url = $topic_window['next_cursor'] ?
        add_query_arg( 'pvdak_topics_after', $topic_window['next_cursor'], $topic_base_url ) : '';
?>
<main id="main-content" class="pvdak-legal-landing pvdak-legal-index container" tabindex="-1">
    <nav class="pvdak-legal-breadcrumbs" aria-label="مسیر صفحه">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">صفحه اصلی</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page"><?php the_title(); ?></span>
    </nav>
    <header class="pvdak-legal-hero">
        <div class="pvdak-legal-hero-content">
            <span class="pvdak-legal-label">PVDak</span>
            <h1><?php the_title(); ?></h1>
            <?php if ( has_excerpt() ) : ?><p><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
        </div>
    </header>
    <?php if ( '' !== trim( get_the_content() ) ): ?>
    <div class="pvdak-legal-article entry-content"><?php the_content(); ?></div>
    <?php endif; ?>
    <?php if ( $children ): ?>
    <section class="pvdak-legal-index-items" aria-label="لندینگ‌های حقوقی">
        <p role="status"><?php echo esc_html( number_format_i18n( count( $children ) ) . ' موضوع حقوقی در این بخش' ); ?></p>
        <div class="pvdak-home-topics">
            <?php foreach ( $children as $child ): ?>
                <a class="pvdak-home-topic" href="<?php echo esc_url( get_permalink( $child->ID ) ); ?>">
                    <?php echo pvdak_legal_topic_svg( pvdak_legal_topic_icon( $child->ID ) ); ?>
                    <span><?php echo esc_html( get_the_title( $child->ID ) ); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php elseif ( $topic_window['active'] ) : ?>
        <p role="status">در این بخش موضوع منتشرشده‌ای پیدا نشد. می‌توانید فهرست را از ابتدا ببینید.</p>
    <?php elseif ( current_user_can( 'edit_pages' ) ) : ?>
        <p class="pvdak-legal-admin-note">هنوز برگه فرزند منتشرشده‌ای برای این صفحه نداریم. از بخش برگه‌ها، لندینگ‌های مربوط را بسازید.</p>
    <?php else : ?>
        <p role="status">هنوز موضوع حقوقی منتشرشده‌ای در این بخش وجود ندارد.</p>
    <?php endif; ?>
    <?php if ( $topic_prev_url || $topic_next_url ) : ?>
    <nav class="pvdak-topic-search-pagination" aria-label="صفحه‌بندی موضوعات حقوقی">
        <?php if ( $topic_prev_url ) : ?>
            <a href="<?php echo esc_url( $topic_prev_url ); ?>" rel="prev">← صفحه قبل</a>
        <?php endif; ?>
        <span aria-current="page">بخش موضوعات حقوقی</span>
        <?php if ( $topic_next_url ) : ?>
            <a href="<?php echo esc_url( $topic_next_url ); ?>" rel="next">صفحه بعد ←</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
    <?php if ( $topic_window['active'] ) : ?>
        <p><a href="<?php echo esc_url( $topic_base_url ); ?>">بازگشت به ابتدای فهرست</a></p>
    <?php endif; ?>
</main>
<?php
endwhile;
get_footer();
