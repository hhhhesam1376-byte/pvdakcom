<?php
/** PVDak classic theme header. Navigation is managed under Appearance > Menus. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$auth_label = is_user_logged_in() ? 'پنل کاربری' : 'ورود به حساب';
$auth_url = is_user_logged_in() ? pvdak_auth_account_url() : pvdak_auth_login_url();
$account_layout = is_page_template( 'page-templates/pvdak-account.php' );
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
<a class="skip-link screen-reader-text" href="#main-content" title="پرش به بخش اصلی صفحه"><?php esc_html_e( 'رفتن به محتوا', 'pvdak' ); ?></a>
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
                <svg class="pvdak-profile-brand-scale" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.3" aria-hidden="true"><path d="M20 4v31M6 10h28M9 11 4 23h10L9 11Zm22 0-5 12h10l-5-12ZM13 35h14M17 30h6"/><circle cx="20" cy="5" r="2" fill="currentColor"/></svg>
            </div>
        </div>
        <nav class="primary-navigation" aria-label="<?php esc_attr_e( 'منوی اصلی', 'pvdak' ); ?>">
            <?php if ( has_nav_menu( 'primary' ) ) :
                wp_nav_menu( array( 'theme_location'=>'primary', 'container'=>false, 'menu_class'=>'menu', 'fallback_cb'=>false ) );
            else : ?>
                <ul class="menu">
                    <?php foreach ( pvdak_default_navigation_links() as $link ): ?>
                    <li><a href="<?php echo esc_url( $link['url'] ); ?>"<?php if ( ( ! empty( $link['slug'] ) && is_page( $link['slug'] ) ) || ( ! empty( $link['home'] ) && is_front_page() ) ): ?> aria-current="page"<?php endif; ?>><?php echo esc_html( $link['label'] ); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </nav>
        <form class="pvdak-site-search pvdak-profile-header-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
            <label class="screen-reader-text" for="pvdak-profile-header-query">جست‌وجو در مطالب سایت</label>
            <input id="pvdak-profile-header-query" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="جست‌وجو در مطالب و موضوعات حقوقی..." autocomplete="off" maxlength="100" required>
            <button type="submit" aria-label="جست‌وجو">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/></svg>
            </button>
          </form>
        <div class="pvdak-header-actions">
            <?php if ( is_user_logged_in() && shortcode_exists( 'pvdak_support_tickets' ) ): ?>
              <a class="pvdak-profile-header-messages" href="<?php echo esc_url( pvdak_auth_account_url() . '#pvdak-support' ); ?>" aria-label="پیام‌ها و پشتیبانی" title="پیام‌ها و پشتیبانی">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 15a4 4 0 0 1-4 4H8l-4 3V7a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4z"/><path d="M8 9h8M8 13h5"/></svg>
              </a>
            <?php endif; ?>
            <a class="pvdak-login-button" href="<?php echo esc_url( $auth_url ); ?>"><?php echo esc_html( $account_layout && is_user_logged_in() ? 'حساب من' : $auth_label ); ?></a>
            <details class="pvdak-mobile-menu">
                <summary aria-label="باز و بسته کردن فهرست سایت">فهرست <span class="pvdak-mobile-menu-icon" aria-hidden="true">☰</span></summary>
                <nav aria-label="منوی موبایل">
                    <?php if ( has_nav_menu( 'primary' ) ) :
                        wp_nav_menu( array( 'theme_location'=>'primary', 'container'=>false, 'menu_class'=>'pvdak-mobile-links', 'fallback_cb'=>false ) );
                    else : ?>
                        <ul class="pvdak-mobile-links">
                            <?php foreach ( pvdak_default_navigation_links() as $link ): ?>
                            <li><a href="<?php echo esc_url( $link['url'] ); ?>"<?php if ( ( ! empty( $link['slug'] ) && is_page( $link['slug'] ) ) || ( ! empty( $link['home'] ) && is_front_page() ) ): ?> aria-current="page"<?php endif; ?>><?php echo esc_html( $link['label'] ); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </nav>
            </details>
        </div>
    </div>
</header>
