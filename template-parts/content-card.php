<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
	<?php if ( has_post_thumbnail() && ! post_password_required() ) : ?>
		<a class="card-image" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1"><?php the_post_thumbnail( 'medium_large', array( 'alt' => '' ) ); ?></a>
	<?php endif; ?>
	<h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ? get_the_title() : __( 'بدون عنوان', 'pvdak' ) ); ?></a></h2>
	<?php if ( 'post' === get_post_type() ) : ?>
		<time class="entry-meta" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
	<?php endif; ?>
	<div class="entry-summary"><?php the_excerpt(); ?></div>
	<a class="read-more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'ادامه مطلب', 'pvdak' ); ?><span class="screen-reader-text">: <?php echo esc_html( get_the_title() ); ?></span></a>
</article>
