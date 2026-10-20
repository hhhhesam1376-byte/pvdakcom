<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e( 'رفتن به محتوا', 'pvdak' ); ?></a>
<header class="site-header">
	<div class="container header-inner">
		<div class="site-branding">
			<?php the_custom_logo(); ?>
			<a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
			<?php if ( get_bloginfo( 'description' ) ) : ?>
				<p class="site-description"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
			<?php endif; ?>
		</div>
		<nav class="primary-navigation" aria-label="<?php esc_attr_e( 'منوی اصلی', 'pvdak' ); ?>">
			<?php if ( has_nav_menu( 'primary' ) ) :
				wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'menu', 'fallback_cb' => false ) );
			else : ?>
				<ul class="menu"><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'خانه', 'pvdak' ); ?></a></li></ul>
			<?php endif; ?>
		</nav>
	</div>
</header>
