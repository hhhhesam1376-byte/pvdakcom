<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( have_posts() ) : ?>
	<div class="post-grid">
	<?php while ( have_posts() ) : the_post();
		get_template_part( 'template-parts/content', 'card' );
	endwhile; ?>
	</div>
	<?php the_posts_pagination( array( 'prev_text' => __( 'قبلی', 'pvdak' ), 'next_text' => __( 'بعدی', 'pvdak' ) ) ); ?>
<?php else :
	get_template_part( 'template-parts/content', 'none' );
endif;
