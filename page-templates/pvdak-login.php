<?php
/**
 * Template Name: ورود اختصاصی PVDak
 * Template Post Type: page
 */
if(!defined('ABSPATH'))exit;
$admin_preview = is_user_logged_in() && current_user_can('manage_options') &&
    isset($_GET['preview']) && '1' === (string) $_GET['preview'];
if(is_user_logged_in() && !$admin_preview){wp_safe_redirect(pvdak_auth_account_url());exit;}
get_header(); ?>
<main id="main-content" class="container pvdak-static pvdak-login-page" tabindex="-1">
<nav class="pvdak-account-breadcrumbs" aria-label="مسیر صفحه"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a><span aria-hidden="true"> / </span><span aria-current="page">ورود به حساب</span></nav>
<div class="pvdak-login-grid">
<section class="pvdak-static-body">
<span class="pvdak-static-label pvdak-auth-eyebrow">حساب کاربری PVDak</span><h1>ورود به پنل</h1>
<p class="pvdak-auth-subheading">با حساب خود وارد فضای یکپارچه مدیریت درخواست‌ها و ارتباط با پشتیبانی شوید.</p>



<?php if(isset($_GET['error'])): ?>
<p id="pvdak-login-result" tabindex="-1" class="pvdak-static-error" role="alert"><?php echo isset($_GET['error']) && is_string($_GET['error']) && 'slow' === sanitize_key(wp_unslash($_GET['error'])) ? 'تلاش‌های ورود بیش از حد بوده است. چند دقیقه بعد دوباره امتحان کنید.' : 'اطلاعات ورود تأیید نشد. نام کاربری و رمز عبور را بررسی کنید.'; ?></p>
<?php endif; ?>
<form method="post" class="pvdak-forms pvdak-auth-form" data-pvdak-single-submit action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
<input type="hidden" name="action" value="pvdak_login"><?php wp_nonce_field('pvdak_login','_pvdak_login_nonce'); ?>
<label for="pvdak-identity"><span aria-hidden="true">◉</span> نام کاربری یا ایمیل</label><input id="pvdak-identity" name="login_identity" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="250" <?php if(isset($_GET['error']) && is_string($_GET['error']) && 'slow' !== sanitize_key(wp_unslash($_GET['error']))): ?>aria-invalid="true" aria-describedby="pvdak-login-result"<?php endif; ?> required>
<label for="pvdak-password"><span aria-hidden="true">♢</span> رمز عبور</label><div class="pvdak-password-field"><input id="pvdak-password" type="password" name="login_password" autocomplete="current-password" <?php if(isset($_GET['error']) && is_string($_GET['error']) && 'slow' !== sanitize_key(wp_unslash($_GET['error']))): ?>aria-invalid="true" aria-describedby="pvdak-login-result"<?php endif; ?> required><button type="button" class="pvdak-password-toggle" data-pvdak-password-toggle="pvdak-password" aria-controls="pvdak-password" aria-pressed="false">نمایش</button></div>
<label class="pvdak-check"><input type="checkbox" name="login_remember"><span>مرا به خاطر بسپار</span></label>
<button type="submit"><span aria-hidden="true">←</span> ورود به حساب</button></form>
<?php if(isset($_GET['status']) && 'reset' === (string) $_GET['status']):?><p class="pvdak-static-note" role="status">رمز عبور تغییر کرد؛ با اطلاعات جدید وارد شوید.</p><?php endif; ?>
<?php $sms_ready = function_exists('pvdak_sms_auth_ready') && pvdak_sms_auth_ready(); ?>
<p class="pvdak-login-help pvdak-auth-help"><a href="<?php echo esc_url(pvdak_account_tool_url('account-recovery')); ?>">رمز عبور را فراموش کرده‌ام</a></p>
<details class="pvdak-login-sms-panel" <?php echo isset($_GET['auth']) && 'sms-login'===sanitize_key(wp_unslash($_GET['auth'])) ? 'open' : ''; ?>>
  <summary>ورود با کد پیامکی</summary>
  <div class="pvdak-login-sms-content">
    <?php
    $sms_status=isset($_GET['sms_status'])&&is_string($_GET['sms_status'])?sanitize_key(wp_unslash($_GET['sms_status'])):'';
    $messages=array('sent'=>'اگر شماره ثبت شده باشد، کد ورود ارسال شده است.','invalid'=>'شماره یا کد معتبر نیست. دوباره امتحان کنید.','slow'=>'لطفاً کمی بعد دوباره تلاش کنید.','unavailable'=>'ورود پیامکی در حال حاضر در دسترس نیست.');
    if($sms_status&&isset($messages[$sms_status])): ?><p class="pvdak-static-note" role="status"><?php echo esc_html($messages[$sms_status]); ?></p><?php endif; ?>
    <?php if($sms_ready): ?>
    <form class="pvdak-forms pvdak-auth-form" data-pvdak-single-submit method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="pvdak_sms_send"><input type="hidden" name="sms_intent" value="login">
      <?php wp_nonce_field('pvdak_sms_send','_pvdak_sms_nonce'); ?>
      <label for="pvdak-login-sms-phone">شماره موبایل</label>
      <input id="pvdak-login-sms-phone" name="sms_phone" type="tel" dir="ltr" inputmode="tel" autocomplete="tel-national" placeholder="09123456789" required>
      <button type="submit">دریافت کد</button>
    </form>
    <form class="pvdak-forms pvdak-auth-form" data-pvdak-single-submit method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="pvdak_sms_verify"><input type="hidden" name="sms_intent" value="login">
      <?php wp_nonce_field('pvdak_sms_verify','_pvdak_sms_verify_nonce'); ?>
      <label for="pvdak-login-sms-verify-phone">شماره موبایل</label>
      <input id="pvdak-login-sms-verify-phone" name="sms_phone" type="tel" dir="ltr" inputmode="tel" placeholder="09123456789" required>
      <label for="pvdak-login-sms-code">کد تأیید</label>
      <input id="pvdak-login-sms-code" name="sms_code" type="text" dir="ltr" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9۰-۹]{6}" maxlength="6" required>
      <button type="submit">ورود با کد</button>
    </form>
    <?php else: ?><p class="pvdak-auth-method-description">ورود پیامکی به‌زودی در دسترس قرار می‌گیرد.</p><?php endif; ?>
  </div>
</details>
<?php $register_url=pvdak_public_template_page_url('account-register','page-templates/pvdak-register.php'); ?>
<?php if($register_url): ?>
<p class="pvdak-login-signup">حساب کاربری ندارید؟ <a href="<?php echo esc_url($register_url); ?>">ثبت‌نام در PVDak</a></p>
<?php endif; ?>
</section>
<aside class="pvdak-login-art pvdak-auth-visual"><span class="pvdak-auth-scale" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M32 7v47M11 18h42M18 19 7 43h22L18 19Zm28 0L35 43h22L46 19ZM18 55h28"/></svg></span><span class="pvdak-auth-art-label">PVDAK • خدمات حقوقی</span><h2>مسیر روشن پیگیری</h2><p>پروفایل شخصی، سوابق درخواست و گفت‌وگوهای پشتیبانی در یک فضای منسجم.</p><ul class="pvdak-login-benefits"><li>دسترسی به اطلاعات حساب</li><li>پیگیری پیام‌های خصوصی</li><li>مدیریت وضعیت همکاری</li></ul></aside>
</div></main><?php get_footer(); ?>
