<?php
/**
 * Template Name: پروفایل کاربری PVDak
 * Template Post Type: page
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! is_user_logged_in() ) { wp_safe_redirect( pvdak_auth_login_url() ); exit; }
$user = wp_get_current_user();
$status = isset( $_GET['status'] ) && is_string( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
$account_url = pvdak_auth_account_url();
$recovery_url = pvdak_public_page_url( 'account-recovery' );
$published_posts = count_user_posts( $user->ID, 'post', true );
$member_since = mysql2date( get_option( 'date_format' ), $user->user_registered );
$account_role = current_user_can( 'manage_options' ) ? 'مدیر سایت' : 'عضو پویش';
add_filter( 'body_class', function( $classes ) { $classes[] = 'pvdak-profile-screen'; return $classes; } );
get_header(); ?>
<main id="main-content" class="container pvdak-static pvdak-profile-page pvdak-profile-redesign" tabindex="-1">
  <nav class="pvdak-account-breadcrumbs pvdak-profile-crumbs" aria-label="مسیر صفحه">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">صفحه اصلی</a><span aria-hidden="true">›</span>
    <a href="<?php echo esc_url( $account_url ); ?>">پنل من</a><span aria-hidden="true">›</span>
    <span aria-current="page">پروفایل من</span>
  </nav>

  <header class="pvdak-profile-hero">
    <span class="pvdak-profile-hero-glow" aria-hidden="true"></span>
    <div class="pvdak-profile-hero-text">
      <span class="pvdak-profile-eyebrow">مرکز مدیریت حساب کاربری</span>
      <h1>پروفایل من</h1>
      <p>اطلاعات حساب خود را به‌روز نگه دارید و از همین بخش به تنظیمات و خدمات حساب دسترسی داشته باشید.</p>
    </div>
    <img class="pvdak-profile-hero-photo" src="<?php echo esc_url( get_template_directory_uri() . '/assets/icons/pvdak-profile-hero.svg' ); ?>" alt="" width="900" height="360" decoding="async">
    <span class="pvdak-profile-hero-badge" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-5 3-8 8-8s8 3 8 8"/></svg>
    </span>
  </header>

  <div class="pvdak-profile-workspace">
    <section class="pvdak-profile-editor" aria-labelledby="pvdak-profile-editor-title">
      <div class="pvdak-profile-card-heading">
        <span class="pvdak-profile-heading-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path d="M19 13a7 7 0 0 0 0-2l2-1-2-4-2 1a7 7 0 0 0-2-1l-1-3h-4L9 6a7 7 0 0 0-2 1L5 6l-2 4 2 1a7 7 0 0 0 0 2l-2 1 2 4 2-1a7 7 0 0 0 2 1l1 3h4l1-3a7 7 0 0 0 2-1l2 1 2-4-2-1Z"/></svg>
        </span>
        <div>
          <span class="pvdak-profile-section-kicker">تنظیمات شخصی</span>
          <h2 id="pvdak-profile-editor-title">اطلاعات حساب کاربری</h2>
          <p>نام نمایشی را ویرایش کنید؛ سایر مشخصات هویتی اینجا فقط برای مشاهده هستند.</p>
        </div>
      </div>

      <div class="pvdak-profile-editor-body">
        <?php if('saved'===$status):?><p id="pvdak-profile-result" tabindex="-1" role="status" class="pvdak-static-note">نام نمایشی با موفقیت ذخیره شد.</p>
        <?php elseif('unchanged'===$status):?><p id="pvdak-profile-result" tabindex="-1" role="status" class="pvdak-static-note">نام نمایشی تغییری نکرده است.</p>
        <?php elseif('invalid'===$status):?><p id="pvdak-profile-result" tabindex="-1" role="alert" class="pvdak-static-error">نام نمایشی باید بین ۱ تا ۱۲۰ نویسه باشد.</p>
        <?php elseif($status):?><p id="pvdak-profile-result" tabindex="-1" role="alert" class="pvdak-static-error">ذخیره اطلاعات انجام نشد؛ دوباره تلاش کنید.</p><?php endif; ?>

        <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="pvdak-forms pvdak-profile-form" data-pvdak-profile-form data-pvdak-single-submit>
          <input type="hidden" name="action" value="pvdak_profile_save">
          <?php wp_nonce_field( 'pvdak_profile_save', '_pvdak_profile_nonce' ); ?>
          <div class="pvdak-profile-field">
            <label for="account-display-name">
              <span class="pvdak-profile-field-icon" aria-hidden="true">✎</span>نام نمایشی
              <span class="pvdak-profile-editable-tag">قابل ویرایش</span>
            </label>
            <input id="account-display-name" name="display_name" type="text" value="<?php echo esc_attr( $user->display_name ); ?>" maxlength="120" autocomplete="nickname" aria-describedby="pvdak-profile-help pvdak-profile-count" required>
            <div class="pvdak-profile-field-meta">
              <p id="pvdak-profile-help" class="pvdak-field-help">این نام در پنل و بخش‌های مختلف سایت نمایش داده می‌شود؛ اطلاعات محرمانه یا شماره پرونده وارد نکنید.</p>
              <span id="pvdak-profile-count" class="pvdak-field-count" data-pvdak-count-for="account-display-name" aria-live="off"></span>
            </div>
            <p class="pvdak-form-change-status" data-pvdak-profile-status role="status" aria-live="polite"></p>
          </div>

          <div class="pvdak-profile-details" aria-label="مشخصات ثابت حساب">
            <div class="pvdak-profile-readonly">
              <strong><span aria-hidden="true">@</span> نام کاربری</strong>
              <span dir="auto"><?php echo esc_html( $user->user_login ); ?></span>
              <small>شناسه ورود شما قابل تغییر نیست.</small>
            </div>
            <div class="pvdak-profile-readonly">
              <strong><span aria-hidden="true">✉</span> ایمیل حساب</strong>
              <span dir="ltr"><?php echo esc_html( $user->user_email ); ?></span>
              <small>برای تغییر ایمیل با مدیر سایت تماس بگیرید.</small>
            </div>
          </div>

          <div class="pvdak-form-actions pvdak-profile-actions">
            <button type="submit" data-pvdak-profile-save><span aria-hidden="true">✓</span> ذخیره تغییرات</button>
            <button type="reset" class="pvdak-secondary-button"><span aria-hidden="true">↻</span> بازگرداندن مقدار قبلی</button>
          </div>
        </form>

        <div class="pvdak-profile-bottom">
        <div class="pvdak-profile-security">
          <span class="pvdak-profile-security-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
          </span>
          <div>
            <strong>امنیت حساب</strong>
            <?php if ( $recovery_url ): ?>
              <a href="<?php echo esc_url( $recovery_url ); ?>">بازیابی یا تغییر رمز عبور با ایمیل ←</a>
            <?php else: ?>
              <span>بازیابی رمز عبور از این صفحه در دسترس نیست.</span>
            <?php endif; ?>
          </div>
        </div>
          <a class="pvdak-profile-back" href="<?php echo esc_url( $account_url ); ?>">← بازگشت به پنل من</a>
        </div>
        <span class="pvdak-profile-shield-art" aria-hidden="true">
          <svg viewBox="0 0 140 150" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="69" cy="83" r="63" fill="#e2f6ff"/><circle cx="100" cy="112" r="32" fill="#c9f4e9"/><circle cx="28" cy="109" r="22" fill="#def2fc"/><path d="M70 10 117 27v44c0 34-23 58-47 70C46 129 23 105 23 71V27L70 10Z" fill="#1a6bbb" stroke="white" stroke-width="6"/><path d="M70 20 106 33v36c0 28-17 48-36 59-19-11-36-31-36-59V33l36-13Z" fill="#2084cf"/><path d="m46 70 15 16 32-37" stroke="#fff" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </div>
    </section>

    <aside class="pvdak-profile-rail" aria-label="اطلاعات و دسترسی حساب">
      <section class="pvdak-profile-person-card" aria-label="خلاصه حساب شما">
        <div class="pvdak-profile-avatar" aria-hidden="true">
          <svg viewBox="0 0 64 64" fill="none"><circle cx="32" cy="23" r="12" fill="currentColor" opacity=".82"/><path d="M10 58c1-14 9-22 22-22s21 8 22 22" fill="currentColor" opacity=".82"/></svg>
          <span class="pvdak-profile-online"></span>
        </div>
        <h2><?php echo esc_html( $user->display_name ); ?></h2>
        <span class="pvdak-profile-username" dir="auto"><?php echo esc_html( $user->user_login ); ?></span>
        <span class="pvdak-profile-account-tag"><span aria-hidden="true">●</span> حساب واردشده</span>
        <div class="pvdak-profile-stat-grid">
          <div><span class="pvdak-profile-stat-icon" aria-hidden="true">♢</span><small>سطح حساب</small><strong><?php echo esc_html( $account_role ); ?></strong></div>
          <div><span class="pvdak-profile-stat-icon" aria-hidden="true">▦</span><small>عضویت از</small><strong><?php echo esc_html( $member_since ); ?></strong></div>
          <div><span class="pvdak-profile-stat-icon" aria-hidden="true">▤</span><small>نوشته‌های منتشرشده</small><strong><?php echo esc_html( number_format_i18n( (int) $published_posts ) ); ?></strong></div>
        </div>
      </section>
      <section class="pvdak-profile-nav-card" aria-label="منوی حساب">
        <h2>منوی حساب کاربری</h2>
        <nav class="pvdak-profile-nav" aria-label="بخش‌های حساب">
          <a href="<?php echo esc_url( $account_url ); ?>"><span class="pvdak-profile-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="8" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/><rect x="13" y="13" width="8" height="8" rx="2"/></svg></span> پنل من <span class="pvdak-profile-nav-arrow" aria-hidden="true">←</span></a>
          <a href="<?php echo esc_url( pvdak_account_tool_url( 'account-profile' ) ); ?>" aria-current="page"><span class="pvdak-profile-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="7" r="4"/><path d="M4 21c0-5 3-8 8-8s8 3 8 8"/></svg></span> پروفایل من <span class="pvdak-profile-nav-arrow" aria-hidden="true">←</span></a>
          <?php if ( shortcode_exists( 'pvdak_support_tickets' ) ): ?>
            <a href="<?php echo esc_url( $account_url . '#pvdak-support' ); ?>"><span class="pvdak-profile-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M20 15a4 4 0 0 1-4 4H8l-4 3V7a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4z"/><path d="M8 9h8M8 13h5"/></svg></span> پیام‌ها و پشتیبانی <span class="pvdak-profile-nav-arrow" aria-hidden="true">←</span></a>
          <?php endif; ?>
          <?php if ( $recovery_url ): ?>
            <a href="<?php echo esc_url( $recovery_url ); ?>"><span class="pvdak-profile-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span> امنیت و بازیابی رمز <span class="pvdak-profile-nav-arrow" aria-hidden="true">←</span></a>
          <?php endif; ?>
        </nav>
        <a class="pvdak-profile-signout" href="<?php echo esc_url( wp_logout_url( pvdak_auth_login_url() ) ); ?>">خروج امن از حساب <span aria-hidden="true">↗</span></a>
      </section>
      <p class="pvdak-profile-privacy-hint"><span aria-hidden="true">✓</span> فقط مشخصات حساب خودتان در این صفحه نمایش داده می‌شود.</p>
    </aside>
  </div>
</main>
<?php get_footer(); ?>
