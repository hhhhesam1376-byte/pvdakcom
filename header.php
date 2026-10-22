<?php
/** PVDak classic theme header. Navigation is managed under Appearance > Menus. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$auth_label = is_user_logged_in() ? 'پنل کاربری' : 'ورود به حساب';
$auth_url = is_user_logged_in() ? admin_url() : wp_login_url( home_url( '/' ) );
if ( is_user_logged_in() && function_exists( 'pvdak_core_account_url' ) ) {
    $portal_url = pvdak_core_account_url();
    if ( $portal_url ) { $auth_url = $portal_url; }
}
?>
<!doctype html>
<html lang="fa-IR" dir="rtl">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e( 'رفتن به محتوا', 'pvdak' ); ?></a>
<div class="pvdak-topbar">
    <div class="container"><?php echo esc_html( get_bloginfo( 'description' ) ?: 'پویش وکلای دادگستری کشور • همراه مسیر حقوقی شما' ); ?></div>
</div>
<header class="site-header">
    <div class="container header-inner">
        <div class="site-branding">
            <?php if ( has_custom_logo() ) { the_custom_logo(); } ?>
            <div class="pvdak-site-brand">
                <a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>">PVDAK</a>
                <span class="site-description">پویش وکلای دادگستری کشور</span>
            </div>
        </div>
        <nav class="primary-navigation" aria-label="<?php esc_attr_e( 'منوی اصلی', 'pvdak' ); ?>">
            <?php if ( has_nav_menu( 'primary' ) ) :
                wp_nav_menu( array( 'theme_location'=>'primary', 'container'=>false, 'menu_class'=>'menu', 'fallback_cb'=>false ) );
            else : ?>
                <ul class="menu">
                    <li><a href="<?php echo esc_url( home_url('/#services') ); ?>">خدمات حقوقی</a></li>
                    <li><a href="<?php echo esc_url( home_url('/#topics') ); ?>">موضوعات</a></li>
                    <li><a href="<?php echo esc_url( home_url('/#magazine') ); ?>">مجله</a></li>
                    <li><a href="<?php echo esc_url( home_url('/#forum') ); ?>">انجمن وکلا</a></li>
                    <li><a href="<?php echo esc_url( home_url('/#community') ); ?>">همکاری با ما</a></li>
                </ul>
            <?php endif; ?>
        </nav>
        <div class="pvdak-header-actions">
            <a class="pvdak-login-button" href="<?php echo esc_url( $auth_url ); ?>"><?php echo esc_html( $auth_label ); ?></a>
            <details class="pvdak-mobile-menu">
                <summary aria-label="بازکردن فهرست سایت">فهرست <span aria-hidden="true">☰</span></summary>
                <nav aria-label="منوی موبایل">
                    <?php if ( has_nav_menu( 'primary' ) ) :
                        wp_nav_menu( array( 'theme_location'=>'primary', 'container'=>false, 'menu_class'=>'pvdak-mobile-links', 'fallback_cb'=>false ) );
                    else : ?>
                        <ul class="pvdak-mobile-links">
                            <li><a href="<?php echo esc_url( home_url('/#services') ); ?>">خدمات حقوقی</a></li>
                            <li><a href="<?php echo esc_url( home_url('/#topics') ); ?>">موضوعات</a></li>
                            <li><a href="<?php echo esc_url( home_url('/#magazine') ); ?>">مجله</a></li>
                            <li><a href="<?php echo esc_url( home_url('/#forum') ); ?>">انجمن وکلا</a></li>
                            <li><a href="<?php echo esc_url( home_url('/#community') ); ?>">همکاری با ما</a></li>
                        </ul>
                    <?php endif; ?>
                </nav>
            </details>
        </div>
    </div>
</header>
