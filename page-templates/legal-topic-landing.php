<?php
/**
 * Template Name: لندینگ موضوع حقوقی
 * Template Post Type: page
 * Description: Dedicated content landing for a legal topic; editable with the default WordPress page editor.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post();
    $page_id = get_the_ID();
    $parent_id = wp_get_post_parent_id( $page_id );
    $intro = function_exists( 'get_field' ) ? get_field( 'pvdak_landing_intro', $page_id ) : '';
    $image = get_the_post_thumbnail_url( $page_id, 'full' );
?>
<main id="main-content" class="pvdak-legal-landing container" tabindex="-1">
    <nav class="pvdak-legal-breadcrumbs" aria-label="مسیر صفحه">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">صفحه اصلی</a>
        <span aria-hidden="true">/</span>
        <?php if ( $parent_id ) : ?>
            <a href="<?php echo esc_url( get_permalink( $parent_id ) ); ?>"><?php echo esc_html( get_the_title( $parent_id ) ); ?></a>
            <span aria-hidden="true">/</span>
        <?php endif; ?>
        <span aria-current="page"><?php the_title(); ?></span>
    </nav>
    <header class="pvdak-legal-hero<?php echo $image ? ' pvdak-legal-hero--image' : ''; ?>" <?php
        if ( $image ) {
            echo 'style="--pvdak-legal-photo:url(' . esc_url( $image ) . ')"';
        }
    ?>>
        <div class="pvdak-legal-hero-content">
            <span class="pvdak-legal-label">موضوع حقوقی</span>
            <h1><?php the_title(); ?></h1>
            <?php if ( is_string( $intro ) && '' !== trim( $intro ) ) : ?>
                <p><?php echo esc_html( $intro ); ?></p>
            <?php endif; ?>
        </div>
    </header>
    <article class="pvdak-legal-article">
        <div class="entry-content">
            <?php the_content(); ?>
            <?php wp_link_pages( array( 'before' => '<nav class="page-links">', 'after' => '</nav>' ) ); ?>
        </div>
    </article>
</main>
<?php
endwhile;
get_footer();
