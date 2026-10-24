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
    <span class="pvdak-account-hero-mark" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M32 8v48M11 18h42M18 19 8 42h20L18 19Zm28 0L36 42h20L46 19ZM21 55h22"/></svg></span>
  </header>
  <nav class="pvdak-dashboard-quicklinks" aria-label="دسترسی سریع حساب">
    <a href="#pvdak-dashboard"><span aria-hidden="true">◈</span><strong>نمای کلی</strong><small>وضعیت حساب و درخواست‌ها</small></a>
    <?php if ( $profile_url ): ?><a href="<?php echo esc_url( $profile_url ); ?>"><span aria-hidden="true">◉</span><strong>پروفایل</strong><small>ویرایش اطلاعات نمایشی</small></a><?php endif; ?>
    <?php if ( $has_support ): ?><a href="#pvdak-support"><span aria-hidden="true">✉</span><strong>پشتیبانی</strong><small>پیام‌ها و پیگیری تیکت‌ها</small></a><?php endif; ?>
  </nav>
  <div class="pvdak-account-layout">
    <aside class="pvdak-account-sidebar" aria-label="منوی حساب">
      <div class="pvdak-account-identity">
        <span class="pvdak-account-avatar" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-5 3-8 8-8s8 3 8 8"/></svg></span>
        <div><strong><?php echo esc_html( $user->display_name ); ?></strong>
          <small><?php echo esc_html( $user->user_email ); ?></small>
        </div>
      </div>
      <nav class="pvdak-account-menu" aria-label="بخش‌های پنل">
        <a href="#pvdak-dashboard" aria-current="page"><span aria-hidden="true">▦</span> نمای کلی حساب</a>
        <?php if ( $has_professional ): ?><a href="#pvdak-professional"><span aria-hidden="true">⚖</span> وضعیت همکاری</a><?php endif; ?>
        <?php if ( $has_support ): ?><a href="#pvdak-support"><span aria-hidden="true">✉</span> پشتیبانی و تیکت‌ها</a><?php endif; ?>
        <?php if ( $profile_url ): ?><a href="<?php echo esc_url( $profile_url ); ?>"><span aria-hidden="true">◉</span> ویرایش پروفایل</a><?php endif; ?>
        <?php if ( $recovery_url ): ?><a href="<?php echo esc_url( $recovery_url ); ?>"><span aria-hidden="true">◇</span> امنیت و بازیابی رمز</a><?php endif; ?>
      </nav>
      <p class="pvdak-account-sidebar-note"><?php echo $intake_off ? 'ثبت درخواست حقوقی جدید فعلاً غیرفعال است؛ سوابق حساب در دسترس شما می‌ماند.' : 'برای ثبت درخواست جدید از خدمات فعال داخل پنل استفاده کنید.'; ?></p>
      <a class="pvdak-account-signout" href="<?php echo esc_url( wp_logout_url( pvdak_auth_login_url() ) ); ?>">خروج امن از حساب ←</a>
    </aside>
    <div class="pvdak-account-main">
      <section id="pvdak-dashboard" class="pvdak-account-panel" aria-label="نمای کلی حساب">
        <?php if ( $has_dashboard ): ?>
          <?php echo do_shortcode( '[pvdak_account_dashboard]' ); ?>
        <?php else: ?>
          <div class="pvdak-static-body"><h2>نمای کلی حساب</h2><p>شما وارد حساب خود شده‌اید؛ خلاصه درخواست‌ها در حال حاضر در دسترس نیست.</p><p class="pvdak-static-note">وضعیت فعال‌بودن خدمات و درخواست‌ها باید جداگانه بررسی شود.</p></div>
        <?php endif; ?>
      </section>
      <?php if ( $has_professional ): ?>
        <section id="pvdak-professional" class="pvdak-account-panel" aria-label="وضعیت همکاری">
          <?php echo do_shortcode( '[pvdak_professional_dashboard]' ); ?>
          <p class="pvdak-panel-return"><a href="#pvdak-dashboard">↑ بازگشت به نمای کلی حساب</a></p>
        </section>
      <?php endif; ?>
      <?php if ( $has_support ): ?>
        <div class="pvdak-account-panel pvdak-account-support">
          <?php echo do_shortcode( '[pvdak_support_tickets]' ); ?>
          <p class="pvdak-panel-return"><a href="#pvdak-dashboard">↑ بازگشت به نمای کلی حساب</a></p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php get_footer(); ?>
