<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$published_categories = 'post' === get_post_type() ? get_the_category() : array();
$published_category = $published_categories ? $published_categories[0] : null;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card pvdak-editorial-tile' ); ?>>
  <?php if ( has_post_thumbnail() && ! post_password_required() ) : ?>
    <a class="card-image" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1"><?php the_post_thumbnail( 'medium_large', array( 'alt' => '' ) ); ?></a>
  <?php else : ?>
    <a class="pvdak-card-art" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
      <svg viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true"><path d="M40 11v57M18 24h44M24 26 12 49h24L24 26Zm32 0L44 49h24L56 26ZM27 70h26"/><circle cx="40" cy="11" r="3" fill="currentColor"/></svg>
      <span aria-hidden="true">آگاهی حقوقی</span>
    </a>
  <?php endif; ?>
  <div class="pvdak-card-inner">
    <?php if ( $published_category ) : ?><span class="pvdak-card-topic"><?php echo esc_html( $published_category->name ); ?></span><?php endif; ?>
    <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ? get_the_title() : __( 'بدون عنوان', 'pvdak' ) ); ?></a></h2>
    <?php if ( 'post' === get_post_type() ) : ?>
      <time class="entry-meta" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
    <?php endif; ?>
    <div class="entry-summary"><?php the_excerpt(); ?></div>
    <a class="read-more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'ادامه مطلب', 'pvdak' ); ?><span aria-hidden="true"> ←</span><span class="screen-reader-text">: <?php echo esc_html( get_the_title() ); ?></span></a>
  </div>
</article>
