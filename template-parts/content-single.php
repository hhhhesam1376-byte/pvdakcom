<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-entry' ); ?>>
	<header class="entry-header">
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		<time class="entry-meta" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
	</header>
	<?php if ( has_post_thumbnail() && ! post_password_required() ) { the_post_thumbnail( 'large', array( 'class' => 'featured-image' ) ); } ?>
	<div class="entry-content">
		<?php the_content(); ?>
		<?php wp_link_pages( array( 'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'صفحه‌های محتوا', 'pvdak' ) . '">', 'after' => '</nav>' ) ); ?>
	</div>
	<footer class="entry-taxonomies"><?php the_category( '، ' ); ?> <?php the_tags( '<p>', '، ', '</p>' ); ?></footer>
</article>
