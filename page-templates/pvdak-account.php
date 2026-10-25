<?php
/**
 * Template Name: پنل اختصاصی PVDak
 * Template Post Type: page
 * One private dashboard: overview, requests, profile, professional, support and security.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! is_user_logged_in() ) { wp_safe_redirect( pvdak_auth_login_url() ); exit; }
$user = wp_get_current_user();
$has_dashboard = shortcode_exists( 'pvdak_account_dashboard' );
$has_requests = shortcode_exists( 'pvdak_my_requests' );
$has_support = shortcode_exists( 'pvdak_support_tickets' );
$self_reported_category=(string)get_user_meta($user->ID,'_pvdak_self_reported_category',true);
$professional_interest=in_array($self_reported_category,array('lawyer','trainee','student'),true);
$professional_join_url='';
if ($professional_interest && function_exists('pvdak_core_member_intake_open') &&
    pvdak_core_member_intake_open() && function_exists('pvdak_core_published_page_link')) {
    $professional_join_url=pvdak_core_published_page_link(
        'join-legal-community','pvdak_professional_application');
}
$has_professional = shortcode_exists( 'pvdak_professional_dashboard' ) &&
    function_exists( 'pvdak_core_existing_application' ) &&
    (bool) pvdak_core_existing_application( get_current_user_id() );
$status = isset( $_GET['status'] ) && is_string( $_GET['status'] ) ?
    sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
$account_url = pvdak_auth_account_url();
$recovery_url = is_email( (string) $user->user_email ) ?
    pvdak_account_tool_url( 'account-recovery' ) : '';
$intake_off = ! function_exists( 'pvdak_core_intake_open' ) || ! pvdak_core_intake_open();
get_header(); ?>
<main id="main-content" class="container pvdak-static pvdak-account-page pvdak-unified-account" tabindex="-1">
  <nav class="pvdak-account-breadcrumbs pvdak-unified-crumbs" aria-label="مسیر صفحه">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">صفحه اصلی</a>
    <span aria-hidden="true">›</span><span aria-current="page">پنل من</span>
  </nav>
  <header class="pvdak-static-head pvdak-account-hero">
    <div>
      <span>مرکز مدیریت حساب کاربری</span>
      <h1>پنل من</h1>
      <p>خوش آمدید، <strong><?php echo esc_html( $user->display_name ); ?></strong>. درخواست‌ها، اطلاعات حساب و پشتیبانی را از یک پنل مدیریت کنید.</p>
    </div>
    <span class="pvdak-account-hero-mark" aria-hidden="true">
      <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M32 8v48M11 18h42M18 19 8 42h20L18 19Zm28 0L36 42h20L46 19ZM21 55h22"/></svg>
    </span>
  </header>
  <div class="pvdak-dashboard-overline"><span class="pvdak-dashboard-online"><span aria-hidden="true">●</span> وارد حساب شده‌اید</span><span>دسترسی شخصی شما به امکانات PVDak</span></div>
  <nav class="pvdak-dashboard-quicklinks" aria-label="دسترسی سریع حساب">
    <a href="#pvdak-dashboard" aria-current="location"><span aria-hidden="true">◈</span><strong>نمای کلی</strong><small>خلاصه وضعیت حساب</small><span class="pvdak-dashboard-card-arrow" aria-hidden="true">←</span></a>
    <a href="#pvdak-profile"><span aria-hidden="true">◉</span><strong>اطلاعات حساب</strong><small>ویرایش مشخصات شخصی</small><span class="pvdak-dashboard-card-arrow" aria-hidden="true">←</span></a>
    <?php if ( $has_requests ): ?><a href="#pvdak-requests"><span aria-hidden="true">⚖</span><strong>درخواست‌های من</strong><small>پیگیری درخواست‌ها</small><span class="pvdak-dashboard-card-arrow" aria-hidden="true">←</span></a><?php endif; ?>
    <?php if ( $has_support ): ?><a href="#pvdak-support"><span aria-hidden="true">✉</span><strong>پشتیبانی</strong><small>پیام‌ها و تیکت‌ها</small><span class="pvdak-dashboard-card-arrow" aria-hidden="true">←</span></a><?php endif; ?>
  </nav>
  <div class="pvdak-account-layout">
    <aside class="pvdak-account-sidebar" aria-label="منوی حساب">
      <div class="pvdak-account-identity">
        <span class="pvdak-account-avatar" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-5 3-8 8-8s8 3 8 8"/></svg>
        </span>
        <div><strong><?php echo esc_html( $user->display_name ); ?></strong>
          <small dir="auto"><?php echo esc_html( is_email( (string) $user->user_email )
            ? $user->user_email : 'حساب با شماره موبایل' ); ?></small>
        </div>
      </div>
      <div class="pvdak-account-identity-meta"><span>حساب کاربری واردشده</span><span aria-hidden="true">●</span></div>
      <nav class="pvdak-account-menu" aria-label="بخش‌های پنل">
        <a href="#pvdak-dashboard" aria-current="location"><span aria-hidden="true">▦</span> داشبورد <span class="pvdak-account-menu-arrow" aria-hidden="true">←</span></a>
        <a href="#pvdak-profile"><span aria-hidden="true">◉</span> اطلاعات حساب <span class="pvdak-account-menu-arrow" aria-hidden="true">←</span></a>
        <?php if ( $has_requests ): ?><a href="#pvdak-requests"><span aria-hidden="true">⚖</span> درخواست‌های حقوقی <span class="pvdak-account-menu-arrow" aria-hidden="true">←</span></a><?php endif; ?>
        <?php if ( $professional_interest && ! $has_professional ): ?><a href="#pvdak-professional-interest"><span aria-hidden="true">♧</span> درخواست همکاری <span class="pvdak-account-menu-arrow" aria-hidden="true">←</span></a><?php endif; ?>
        <?php if ( $has_professional ): ?><a href="#pvdak-professional"><span aria-hidden="true">♧</span> وضعیت همکاری <span class="pvdak-account-menu-arrow" aria-hidden="true">←</span></a><?php endif; ?>
        <?php if ( $has_support ): ?><a href="#pvdak-support"><span aria-hidden="true">✉</span> پشتیبانی و تیکت‌ها <span class="pvdak-account-menu-arrow" aria-hidden="true">←</span></a><?php endif; ?>
        <a href="#pvdak-security"><span aria-hidden="true">◇</span> امنیت حساب <span class="pvdak-account-menu-arrow" aria-hidden="true">←</span></a>
      </nav>
      <p class="pvdak-account-sidebar-note"><?php echo esc_html( $intake_off ? 'ثبت درخواست حقوقی جدید فعلاً غیرفعال است؛ سوابق خود را در پنل ببینید.' : 'خدمات فعال و وضعیت درخواست‌ها را از همین پنل پیگیری کنید.' ); ?></p>
      <a class="pvdak-account-signout" href="<?php echo esc_url( wp_logout_url( pvdak_auth_login_url() ) ); ?>">خروج امن از حساب ←</a>
    </aside>

    <div class="pvdak-account-main" data-pvdak-workspace>
      <section id="pvdak-dashboard" class="pvdak-account-panel" data-pvdak-panel aria-label="نمای کلی حساب" tabindex="-1">
        <div class="pvdak-dashboard-panel-heading"><span class="pvdak-panel-kicker">داشبورد من</span><span class="pvdak-panel-readonly"><span aria-hidden="true">◉</span> اطلاعات واقعی حساب</span></div>
        <?php if ( $has_dashboard ): ?>
          <?php echo do_shortcode( '[pvdak_account_dashboard]' ); ?>
        <?php else: ?>
          <div class="pvdak-static-body pvdak-unified-empty-dashboard" role="status"><span class="pvdak-dashboard-empty-icon" aria-hidden="true">⚖</span><h2>اطلاعات پنل فعلاً در دسترس نیست</h2><p>حساب شما وارد شده است؛ با فعال شدن بخش خلاصه، اطلاعات واقعی درخواست‌ها در همین قسمت نمایش داده خواهد شد.</p><a href="#pvdak-profile">مشاهده اطلاعات حساب ←</a></div>
        <?php endif; ?>
      </section>

      <?php if ($professional_interest && !$has_professional): ?>
        <div class="pvdak-account-panel pvdak-unified-section pvdak-professional-next-step"
          data-pvdak-panel id="pvdak-professional-interest" tabindex="-1">
          <header class="pvdak-unified-section-header">
            <span>همکاری حرفه‌ای</span>
            <h2>درخواست همکاری</h2>
            <p>انتخاب نوع حساب در ثبت‌نام، به معنی تأیید صلاحیت یا ارسال درخواست همکاری نیست.</p>
          </header>
          <?php if ($professional_join_url): ?>
            <p><a class="pvdak-secondary-button" href="<?php echo esc_url($professional_join_url); ?>">
              تکمیل درخواست بررسی همکاری
            </a></p>
          <?php else: ?>
            <p class="pvdak-static-note" role="status">زمان ثبت درخواست همکاری، فرم تکمیل اطلاعات از همین حساب در دسترس خواهد بود.</p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ( $has_requests ): ?>
        <section id="pvdak-requests" class="pvdak-account-panel pvdak-unified-section" data-pvdak-panel aria-labelledby="pvdak-requests-title" tabindex="-1">
          <header class="pvdak-unified-section-header"><span>پیگیری درخواست‌های ثبت‌شده</span><h2 id="pvdak-requests-title">درخواست‌های من</h2><p>سوابق درخواست‌های متعلق به حساب خودتان را مشاهده و پیگیری کنید.</p><span class="pvdak-unified-private-note">فقط برای شما قابل مشاهده</span></header>
          <?php if ( function_exists( 'pvdak_account_request_timelines' ) ): ?>
            <?php echo pvdak_account_request_timelines(); // Escaped, owner-only, read-only markup. ?>
          <?php endif; ?>
          <details class="pvdak-request-status-guide">
            <summary>راهنمای پیگیری و وضعیت درخواست‌ها</summary>
            <dl>
              <div><dt>ثبت‌شده</dt><dd>درخواست دریافت شده و منتظر بررسی است.</dd></div>
              <div><dt>در حال بررسی</dt><dd>کارشناسان مجاز اطلاعات ثبت‌شده را بررسی می‌کنند.</dd></div>
              <div><dt>انتساب داخلی ثبت‌شده</dt><dd>در درخواست‌های واجد شرایط، ارجاع داخلی توسط مدیر ثبت شده است.</dd></div>
              <div><dt>پاسخ داده‌شده</dt><dd>وضعیت پاسخ‌دهی توسط مدیر ثبت شده است.</dd></div>
              <div><dt>بسته‌شده</dt><dd>رسیدگی در سامانه خاتمه یافته است.</dd></div>
            </dl>
            <p>هر درخواست لزوماً از همه مراحل عبور نمی‌کند؛ وضعیت واقعی هر درخواست در کارت خودش نمایش داده می‌شود.</p>
          </details>
          <?php echo do_shortcode( '[pvdak_my_requests]' ); ?>
          <?php if ( $has_support ): ?>
            <p class="pvdak-request-help-link">در مورد دسترسی به حساب یا وضعیت نمایش درخواست مشکلی دارید؟
              <a href="#pvdak-support">ارتباط با پشتیبانی</a>
              <small>مدارک یا اطلاعات محرمانه پرونده را در تیکت پشتیبانی نفرستید.</small>
            </p>
          <?php endif; ?>
          <?php if ( current_user_can( 'edit_pvdak_requests' ) ): ?>
            <nav class="pvdak-request-manager-link" aria-label="مدیریت مجاز درخواست‌ها">
              <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pvdak_request' ) ); ?>">فهرست درخواست‌ها در پیشخوان</a>
              <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pvdak_request&page=pvdak-request-triage' ) ); ?>">صف بررسی و وضعیت پرونده‌ها</a>
            </nav>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <section id="pvdak-profile" class="pvdak-account-panel pvdak-unified-section pvdak-profile-redesign" data-pvdak-panel aria-label="اطلاعات حساب" tabindex="-1">
    <section class="pvdak-profile-editor" aria-labelledby="pvdak-profile-editor-title">
      <div class="pvdak-profile-card-heading">
        <span class="pvdak-profile-heading-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path d="M19 13a7 7 0 0 0 0-2l2-1-2-4-2 1a7 7 0 0 0-2-1l-1-3h-4L9 6a7 7 0 0 0-2 1L5 6l-2 4 2 1a7 7 0 0 0 0 2l-2 1 2 4 2-1a7 7 0 0 0 2 1l1 3h4l1-3a7 7 0 0 0 2-1l2 1 2-4-2-1Z"/></svg>
        </span>
        <div>
          <span class="pvdak-profile-section-kicker">تنظیمات شخصی • ویرایش امن</span>
          <h2 id="pvdak-profile-editor-title">اطلاعات حساب کاربری</h2>
          <p>نام و نام خانوادگی و نام نمایشی خود را ویرایش کنید؛ اطلاعات ورود و سطح دسترسی ثابت می‌مانند.</p>
        </div>
      </div>

      <div class="pvdak-profile-editor-body">
        <?php if('saved'===$status):?><p id="pvdak-profile-result" tabindex="-1" role="status" class="pvdak-static-note">اطلاعات حساب با موفقیت ذخیره شد.</p>
        <?php elseif('unchanged'===$status):?><p id="pvdak-profile-result" tabindex="-1" role="status" class="pvdak-static-note">اطلاعات حساب تغییری نکرده است.</p>
        <?php elseif('invalid'===$status):?><p id="pvdak-profile-result" tabindex="-1" role="alert" class="pvdak-static-error">نام نمایشی باید بین ۱ تا ۱۲۰ نویسه و نام و نام خانوادگی هر کدام حداکثر ۸۰ نویسه باشند.</p>
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

          <div class="pvdak-profile-details pvdak-profile-editable-details" aria-label="نام و نام خانوادگی">
            <div class="pvdak-profile-field">
              <label for="pvdak-first-name">نام</label>
              <input id="pvdak-first-name" name="first_name" type="text"
                value="<?php echo esc_attr( get_user_meta( $user->ID, 'first_name', true ) ); ?>"
                maxlength="80" autocomplete="given-name">
            </div>
            <div class="pvdak-profile-field">
              <label for="pvdak-last-name">نام خانوادگی</label>
              <input id="pvdak-last-name" name="last_name" type="text"
                value="<?php echo esc_attr( get_user_meta( $user->ID, 'last_name', true ) ); ?>"
                maxlength="80" autocomplete="family-name">
            </div>
          </div>

          <div class="pvdak-profile-details" aria-label="مشخصات ثابت حساب">
            <div class="pvdak-profile-readonly">
              <strong><span aria-hidden="true">@</span> نام کاربری</strong>
              <span dir="auto"><?php echo esc_html( $user->user_login ); ?></span>
              <small>شناسه ورود شما قابل تغییر نیست.</small>
            </div>
            <div class="pvdak-profile-readonly">
              <strong><span aria-hidden="true">✉</span> ایمیل حساب</strong>
              <span dir="auto"><?php echo esc_html( is_email( (string) $user->user_email )
                  ? $user->user_email : 'برای این حساب ایمیلی ثبت نشده است' ); ?></span>
              <small><?php echo is_email( (string) $user->user_email )
                  ? 'برای تغییر ایمیل با پشتیبانی تماس بگیرید.' : 'ورود و بازیابی حساب پیامکی پس از اتصال سرویس در دسترس است.'; ?></small>
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
              <span>این حساب ایمیل تأییدشده‌ای برای بازیابی رمز ندارد.</span>
            <?php endif; ?>
          </div>
        </div>
          <a class="pvdak-profile-back" href="#pvdak-dashboard">← نمای کلی حساب</a>
        </div>
        <span class="pvdak-profile-shield-art" aria-hidden="true">
          <svg viewBox="0 0 140 150" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="69" cy="83" r="63" fill="#e2f6ff"/><circle cx="100" cy="112" r="32" fill="#c9f4e9"/><circle cx="28" cy="109" r="22" fill="#def2fc"/><path d="M70 10 117 27v44c0 34-23 58-47 70C46 129 23 105 23 71V27L70 10Z" fill="#1a6bbb" stroke="white" stroke-width="6"/><path d="M70 20 106 33v36c0 28-17 48-36 59-19-11-36-31-36-59V33l36-13Z" fill="#2084cf"/><path d="m46 70 15 16 32-37" stroke="#fff" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </div>
    </section>


      </section>

      <?php if ( $has_professional ): ?>
        <section id="pvdak-professional" class="pvdak-account-panel pvdak-unified-section" data-pvdak-panel aria-label="وضعیت همکاری" tabindex="-1">
          <?php echo do_shortcode( '[pvdak_professional_dashboard]' ); ?>
          <p class="pvdak-panel-return"><a href="#pvdak-dashboard">↑ بازگشت به نمای کلی حساب</a></p>
        </section>
      <?php endif; ?>

      <?php if ( $has_support ): ?>
        <div class="pvdak-account-panel pvdak-account-support pvdak-unified-section" data-pvdak-panel aria-label="پشتیبانی و تیکت‌ها" tabindex="-1">
          <header class="pvdak-unified-section-header"><span>ارتباط با پشتیبانی • فقط حساب شما</span><h2>پیام‌های پشتیبانی من</h2><p>پیام‌ها و پاسخ‌های اختصاصی حساب خود را پیگیری کنید؛ اطلاعات محرمانه پرونده ارسال نکنید.</p></header>
          <?php echo do_shortcode( '[pvdak_support_tickets]' ); ?>
          <p class="pvdak-panel-return"><a href="#pvdak-dashboard">↑ بازگشت به نمای کلی حساب</a></p>
        </div>
      <?php endif; ?>

      <section id="pvdak-security" class="pvdak-account-panel pvdak-unified-section" data-pvdak-panel aria-labelledby="pvdak-security-title" tabindex="-1">
        <header class="pvdak-unified-section-header"><span>حریم خصوصی و دسترسی</span><h2 id="pvdak-security-title">امنیت حساب</h2><p>از حساب خود محافظت کنید و در صورت نیاز رمز عبور را بازیابی کنید.</p></header>
        <div class="pvdak-unified-security-card">
          <span class="pvdak-unified-security-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
          <div><h3>رمز عبور و بازیابی</h3><p>برای تغییر رمز عبور از فرایند امن بازیابی ایمیلی استفاده کنید.</p>
            <?php if ( $recovery_url ): ?><a href="<?php echo esc_url( $recovery_url ); ?>">ورود به بخش بازیابی رمز ←</a><?php else: ?><span>بازیابی رمز عبور فعلاً در دسترس نیست.</span><?php endif; ?>
          </div>
        </div>
        <div class="pvdak-unified-security-card pvdak-sms-bind-card">
          <div>
            <h3>اتصال شماره موبایل برای ورود پیامکی</h3>
            <?php
              $bound_mobile=(string)get_user_meta($user->ID,'_pvdak_phone_verified',true);
              $sms_status=isset($_GET['sms_status']) && is_string($_GET['sms_status']) ?
                  sanitize_key(wp_unslash($_GET['sms_status'])) : '';
              $sms_status_text=array('bound'=>'شماره موبایل شما تأیید و به این حساب متصل شد.',
                  'sent'=>'اگر شماره قابل استفاده باشد، کد ارسال شده است.',
                  'invalid'=>'کد یا شماره معتبر نیست یا منقضی شده است.',
                  'slow'=>'لطفاً کمی بعد تلاش کنید.',
                  'unavailable'=>'سرویس پیامکی آماده ارسال نیست.');
            ?>
            <p><?php echo $bound_mobile ? esc_html('شماره تأییدشده: *******'.substr($bound_mobile,-4)) :
                'هنوز شماره موبایل تأییدشده‌ای برای این حساب ثبت نشده است.'; ?></p>
            <?php if ($sms_status && isset($sms_status_text[$sms_status])): ?>
              <p class="pvdak-static-note" role="status"><?php echo esc_html($sms_status_text[$sms_status]); ?></p>
            <?php endif; ?>
            <?php if (current_user_can('manage_options')): ?>
              <p>ورود پیامکی برای حساب‌های مدیر غیرفعال است؛ از ورود امن اصلی استفاده کنید.</p>
            <?php elseif (!function_exists('pvdak_sms_auth_ready') || !pvdak_sms_auth_ready()): ?>
              <p>اتصال پیامک هنوز فعال نشده است. پس از اتصال سرویس ارسال، شماره را در همین قسمت تأیید کنید.</p>
            <?php else: ?>
              <form method="post" class="pvdak-forms" data-pvdak-single-submit action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="pvdak_sms_send"><input type="hidden" name="sms_intent" value="bind">
                <?php wp_nonce_field('pvdak_sms_send','_pvdak_sms_nonce'); ?>
                <label for="pvdak-bind-phone">شماره موبایل</label>
                <input id="pvdak-bind-phone" name="sms_phone" type="tel" inputmode="tel" dir="ltr" maxlength="20" placeholder="09123456789" required>
                <button type="submit">ارسال کد اتصال شماره</button>
              </form>
              <form method="post" class="pvdak-forms" data-pvdak-single-submit action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="pvdak_sms_verify"><input type="hidden" name="sms_intent" value="bind">
                <?php wp_nonce_field('pvdak_sms_verify','_pvdak_sms_verify_nonce'); ?>
                <label for="pvdak-bind-verify-phone">همان شماره موبایل</label>
                <input id="pvdak-bind-verify-phone" name="sms_phone" type="tel" dir="ltr" maxlength="20" placeholder="09123456789" required>
                <label for="pvdak-bind-code">کد ۶ رقمی پیامک</label>
                <input id="pvdak-bind-code" name="sms_code" type="text" dir="ltr" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9۰-۹]{6}" required>
                <button type="submit">تأیید و اتصال شماره</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
        <p class="pvdak-profile-privacy-hint">اطلاعات این پنل فقط برای حساب واردشده نمایش داده می‌شود.</p>
      </section>
    </div>
  </div>
</main>
<?php get_footer(); ?>
