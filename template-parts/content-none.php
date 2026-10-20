<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<section class="no-results">
	<h2><?php esc_html_e( 'محتوایی پیدا نشد', 'pvdak' ); ?></h2>
	<p><?php esc_html_e( 'می‌توانید عبارت دیگری را جست‌وجو کنید.', 'pvdak' ); ?></p>
	<?php get_search_form(); ?>
</section>
