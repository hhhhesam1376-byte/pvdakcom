<?php
/**
 * Template Name: پنل اختصاصی PVDak
 * Template Post Type: page
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! is_user_logged_in() ) { wp_safe_redirect( pvdak_auth_login_url() ); exit; }
$user = wp_get_current_user();
$has_dashboard = shortcode_exists( 'pvdak_account_dashboard' );
$has_support = shortcode_exists( 'pvdak_support_tickets' );
$has_professional = shortcode_exists( 'pvdak_professional_dashboard' ) &&
    function_exists( 'pvdak_core_existing_application' ) &&
    (bool) pvdak_core_existing_application( get_current_user_id() );
$profile_url = pvdak_public_page_url( 'account-profile' );
$recovery_url = pvdak_public_page_url( 'account-recovery' );
$intake_off = ! function_exists( 'pvdak_core_intake_open' ) || ! pvdak_core_intake_open();
get_header(); ?>
<main id="main-content" class="container pvdak-static pvdak-account-page" tabindex="-1">
  <header class="pvdak-static-head pvdak-account-hero">
    <div>
      <span>حساب کاربری پویش</span>
      <h1>پنل اختصاصی من</h1>
      <p>خوش آمدید، <strong><?php echo esc_html( $user->display_name ); ?></strong>. از اینجا وضعیت حساب و پیام‌های پشتیبانی را پیگیری کنید.</p>
    </div>
    <span class="pvdak-account-hero-mark" aria-hidden="true">PV</span>
  </header>
  <div class="pvdak-account-layout">
    <aside class="pvdak-account-sidebar" aria-label="منوی حساب">
      <div class="pvdak-account-identity">
        <span class="pvdak-account-avatar" aria-hidden="true">PV</span>
        <div><strong><?php echo esc_html( $user->display_name ); ?></strong>
          <small><?php echo esc_html( $user->user_email ); ?></small>
        </div>
      </div>
      <nav class="pvdak-account-menu" aria-label="بخش‌های پنل">
        <a href="#pvdak-dashboard" aria-current="page">نمای کلی حساب</a>
        <?php if ( $has_professional ): ?><a href="#pvdak-professional">وضعیت همکاری</a><?php endif; ?>
        <?php if ( $has_support ): ?><a href="#pvdak-support">پشتیبانی و تیکت‌ها</a><?php endif; ?>
        <?php if ( $profile_url ): ?><a href="<?php echo esc_url( $profile_url ); ?>">ویرایش پروفایل</a><?php endif; ?>
        <?php if ( $recovery_url ): ?><a href="<?php echo esc_url( $recovery_url ); ?>">امنیت و بازیابی رمز</a><?php endif; ?>
      </nav>
      <p class="pvdak-account-sidebar-note"><?php echo $intake_off ? 'ثبت درخواست حقوقی جدید فعلاً غیرفعال است؛ سوابق حساب در دسترس شما می‌ماند.' : 'برای ثبت درخواست جدید از خدمات فعال داخل پنل استفاده کنید.'; ?></p>
      <a class="pvdak-account-signout" href="<?php echo esc_url( wp_logout_url( pvdak_auth_login_url() ) ); ?>">خروج امن از حساب ←</a>
    </aside>
    <div class="pvdak-account-main">
      <section id="pvdak-dashboard" class="pvdak-account-panel" aria-label="نمای کلی حساب">
        <?php if ( $has_dashboard ): ?>
          <?php echo do_shortcode( '[pvdak_account_dashboard]' ); ?>
        <?php else: ?>
          <div class="pvdak-static-body"><h2>نمای کلی حساب</h2><p>حساب شما فعال است، اما افزونه Core در حال حاضر خلاصه درخواست‌ها را نمایش نمی‌دهد.</p><p class="pvdak-static-note">ثبت درخواست حقوقی تا راه‌اندازی رسمی غیرفعال است.</p></div>
        <?php endif; ?>
      </section>
      <?php if ( $has_professional ): ?>
        <section id="pvdak-professional" class="pvdak-account-panel" aria-label="وضعیت همکاری">
          <?php echo do_shortcode( '[pvdak_professional_dashboard]' ); ?>
        </section>
      <?php endif; ?>
      <?php if ( $has_support ): ?>
        <div class="pvdak-account-panel pvdak-account-support">
          <?php echo do_shortcode( '[pvdak_support_tickets]' ); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php get_footer(); ?>
