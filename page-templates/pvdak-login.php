<?php
/**
 * Template Name: ورود اختصاصی PVDak
 * Template Post Type: page
 */
if(!defined('ABSPATH'))exit;
$admin_preview = is_user_logged_in() && current_user_can('manage_options') &&
    isset($_GET['preview']) && '1' === (string) $_GET['preview'];
if(is_user_logged_in() && !$admin_preview){wp_safe_redirect(pvdak_auth_account_url());exit;}

$auth_value=isset($_GET['auth']) && is_string($_GET['auth']) ?
    sanitize_key(wp_unslash($_GET['auth'])) : '';
$state=isset($_GET['state']) && is_string($_GET['state']) ?
    sanitize_key(wp_unslash($_GET['state'])) : '';
$sms_status=isset($_GET['sms_status']) && is_string($_GET['sms_status']) ?
    sanitize_key(wp_unslash($_GET['sms_status'])) : '';
$register_selected=in_array($auth_value,array('register-email','sms-register'),true) ||
    (''===$auth_value && ''!==$state);
$sms_selected='sms-login'===$auth_value ||
    (''===$auth_value && !$register_selected && ''!==$sms_status);
$sms_register_selected='sms-register'===$auth_value ||
    (''===$auth_value && $register_selected && ''!==$sms_status);
$login_error=isset($_GET['pvdak_login_error']) && is_string($_GET['pvdak_login_error']) ?
    sanitize_key(wp_unslash($_GET['pvdak_login_error'])) : '';
if (!$login_error && isset($_GET['error']) && is_string($_GET['error'])) {
    $login_error='slow' === sanitize_key(wp_unslash($_GET['error'])) ? 'slow' : 'invalid';
}
$login_error=in_array($login_error,array('invalid','slow'),true)?$login_error:'';
$sms_ready=function_exists('pvdak_sms_auth_ready') && pvdak_sms_auth_ready();
$registration_open=pvdak_registration_open();
$terms=pvdak_public_page_url('terms-of-use');
$privacy=pvdak_public_page_url('privacy-policy');
$email_states=array(
    'received'=>'حساب شما ساخته شد. برای تکمیل ثبت‌نام، ایمیل خود را تأیید کنید.',
    'email_issue'=>'حساب ایجاد شد، ولی ارسال ایمیل تأیید انجام نشد؛ از گزینه ارسال دوباره لینک تأیید استفاده کنید.',
    'verified'=>'ایمیل شما تأیید شد. حالا می‌توانید وارد حساب شوید.',
    'resent'=>'اگر حساب شما در انتظار تأیید باشد، ایمیل تازه ارسال می‌شود.',
    'invalid_token'=>'لینک تأیید معتبر نیست یا منقضی شده است.',
    'invalid'=>'اطلاعات واردشده را بررسی کنید و دوباره تلاش کنید.',
    'duplicate'=>'ساخت حساب با این اطلاعات امکان‌پذیر نیست. اگر قبلاً ثبت‌نام کرده‌اید، از ورود یا بازیابی رمز عبور استفاده کنید.',
    'slow'=>'لطفاً کمی بعد دوباره تلاش کنید.',
    'unavailable'=>'ثبت‌نام در حال حاضر در دسترس نیست.',
);
$sms_states=array(
    'sent'=>'اگر شماره قابل استفاده باشد، کد تأیید ارسال شده است.',
    'invalid'=>'شماره یا کد معتبر نیست. دوباره تلاش کنید.',
    'slow'=>'لطفاً کمی بعد دوباره تلاش کنید.',
    'unavailable'=>'ثبت‌نام پیامکی در حال حاضر در دسترس نیست.',
    'closed'=>'ثبت‌نام در حال حاضر در دسترس نیست.',
);
get_header(); ?>
<main id="main-content" class="container pvdak-static pvdak-login-page" tabindex="-1">
<nav class="pvdak-account-breadcrumbs" aria-label="مسیر صفحه"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a><span aria-hidden="true"> / </span><span aria-current="page">ورود به حساب</span></nav>
<div class="pvdak-login-grid">
<section class="pvdak-static-body">
<span class="pvdak-static-label pvdak-auth-eyebrow">حساب کاربری PVDak</span>
<h1 data-pvdak-auth-copy="login" <?php echo $register_selected?'hidden':''; ?>>ورود به پنل</h1>
<h1 data-pvdak-auth-copy="register" <?php echo $register_selected?'':'hidden'; ?>>ایجاد حساب کاربری</h1>
<p class="pvdak-auth-subheading" data-pvdak-auth-copy="login" <?php echo $register_selected?'hidden':''; ?>>با حساب خود وارد فضای یکپارچه مدیریت درخواست‌ها و ارتباط با پشتیبانی شوید.</p>
<p class="pvdak-auth-subheading" data-pvdak-auth-copy="register" <?php echo $register_selected?'':'hidden'; ?>>روش ثبت‌نام خود را انتخاب کنید. پس از ثبت‌نام ایمیلی، تأیید نشانی ایمیل برای ورود لازم است.</p>

<div data-pvdak-auth-picker>
  <div class="pvdak-auth-mode-picker pvdak-login-tabs" data-pvdak-auth-group="login" role="tablist" aria-label="روش ورود" <?php echo $register_selected?'hidden':''; ?>>
    <button type="button" id="pvdak-login-password-tab" data-pvdak-auth-tab="password" role="tab" aria-controls="pvdak-auth-password" aria-selected="<?php echo $sms_selected?'false':'true'; ?>" tabindex="<?php echo $sms_selected?'-1':'0'; ?>">ورود</button>
    <button type="button" id="pvdak-login-sms-tab" data-pvdak-auth-tab="sms-login" role="tab" aria-controls="pvdak-auth-sms-login" aria-selected="<?php echo $sms_selected?'true':'false'; ?>" tabindex="<?php echo $sms_selected?'0':'-1'; ?>">ورود با پیامک</button>
  </div>
  <div class="pvdak-auth-mode-picker pvdak-register-tabs" data-pvdak-auth-group="register" role="tablist" aria-label="روش ثبت‌نام" <?php echo $register_selected?'':'hidden'; ?>>
    <button type="button" id="pvdak-register-email-tab" data-pvdak-auth-tab="register-email" role="tab" aria-controls="pvdak-auth-register-email" aria-selected="<?php echo $sms_register_selected?'false':'true'; ?>" tabindex="<?php echo $sms_register_selected?'-1':'0'; ?>">ثبت‌نام با ایمیل</button>
    <button type="button" id="pvdak-register-sms-tab" data-pvdak-auth-tab="sms-register" role="tab" aria-controls="pvdak-auth-sms-register" aria-selected="<?php echo $sms_register_selected?'true':'false'; ?>" tabindex="<?php echo $sms_register_selected?'0':'-1'; ?>">ثبت‌نام با پیامک</button>
  </div>
</div>
<section id="pvdak-auth-password" data-pvdak-auth-pane="password" role="tabpanel" aria-labelledby="pvdak-login-password-tab" <?php echo ($register_selected || $sms_selected)?'hidden':''; ?>>
<?php if($login_error): ?>
<div id="pvdak-login-result" tabindex="-1" class="pvdak-static-error pvdak-login-error-notice" role="alert" aria-live="assertive">
  <strong>ورود ناموفق بود</strong>
  <p><?php echo 'slow'===$login_error ? 'تعداد تلاش‌های ورود زیاد بوده است. چند دقیقه بعد دوباره امتحان کنید.' : 'نام کاربری یا رمز عبور اشتباه است. اطلاعات را بررسی کرده و دوباره تلاش کنید.'; ?></p>
</div>
<?php endif; ?>
<form method="post" class="pvdak-forms pvdak-auth-form" data-pvdak-single-submit action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
<input type="hidden" name="action" value="pvdak_login"><?php wp_nonce_field('pvdak_login','_pvdak_login_nonce'); ?>
<label for="pvdak-identity"><span aria-hidden="true">◉</span> نام کاربری یا ایمیل</label><input id="pvdak-identity" name="login_identity" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="250" <?php if('invalid'===$login_error): ?>aria-invalid="true" aria-describedby="pvdak-login-result"<?php endif; ?> required>
<label for="pvdak-password"><span aria-hidden="true">♢</span> رمز عبور</label><div class="pvdak-password-field"><input id="pvdak-password" type="password" name="login_password" autocomplete="current-password" <?php if('invalid'===$login_error): ?>aria-invalid="true" aria-describedby="pvdak-login-result"<?php endif; ?> required><button type="button" class="pvdak-password-toggle" data-pvdak-password-toggle="pvdak-password" aria-controls="pvdak-password" aria-pressed="false">نمایش</button></div>
<label class="pvdak-check"><input type="checkbox" name="login_remember"><span>مرا به خاطر بسپار</span></label>
<button type="submit"><span aria-hidden="true">←</span> ورود به حساب</button></form>
<?php if(isset($_GET['status']) && 'reset' === (string) $_GET['status']):?><p id="pvdak-login-reset-result" tabindex="-1" class="pvdak-static-note" role="status">رمز عبور تغییر کرد؛ با اطلاعات جدید وارد شوید.</p><?php endif; ?>
<p class="pvdak-login-help pvdak-auth-help"><a href="<?php echo esc_url(pvdak_account_tool_url('account-recovery')); ?>">رمز عبور را فراموش کرده‌ام</a></p>
</section>
<section id="pvdak-auth-sms-login" data-pvdak-auth-pane="sms-login" role="tabpanel" aria-labelledby="pvdak-login-sms-tab" <?php echo (!$register_selected && $sms_selected)?'':'hidden'; ?>>
  <div class="pvdak-login-sms-content">
    <?php
    $sms_status=isset($_GET['sms_status'])&&is_string($_GET['sms_status'])?sanitize_key(wp_unslash($_GET['sms_status'])):'';
    $messages=array('sent'=>'اگر شماره ثبت شده باشد، کد ورود ارسال شده است.','invalid'=>'شماره یا کد معتبر نیست. دوباره امتحان کنید.','slow'=>'لطفاً کمی بعد دوباره تلاش کنید.','unavailable'=>'ورود پیامکی در حال حاضر در دسترس نیست.');
    if($sms_status&&isset($messages[$sms_status])): ?><p id="pvdak-sms-login-result" tabindex="-1" class="pvdak-static-note" role="<?php echo 'sent' === $sms_status ? 'status' : 'alert'; ?>"><?php echo esc_html($messages[$sms_status]); ?></p><?php endif; ?>
    <?php if(!$sms_ready): ?><p class="pvdak-auth-method-description" role="status">ورود پیامکی به‌زودی در دسترس قرار می‌گیرد.</p><?php endif; ?>
    <form class="pvdak-forms pvdak-auth-form" data-pvdak-single-submit method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="pvdak_sms_send"><input type="hidden" name="sms_intent" value="login">
      <?php wp_nonce_field('pvdak_sms_send','_pvdak_sms_nonce'); ?>
      <label for="pvdak-login-sms-phone">شماره موبایل</label>
      <input id="pvdak-login-sms-phone" name="sms_phone" type="tel" dir="ltr" inputmode="tel" autocomplete="tel-national" placeholder="09123456789" maxlength="20" required>
      <button type="submit" <?php disabled(!$sms_ready,true); ?>>دریافت کد</button>
    </form>
    <?php if($sms_ready && $sms_status==='sent'): ?>
    <form class="pvdak-forms pvdak-auth-form" data-pvdak-single-submit method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="pvdak_sms_verify"><input type="hidden" name="sms_intent" value="login">
      <?php wp_nonce_field('pvdak_sms_verify','_pvdak_sms_verify_nonce'); ?>
      <label for="pvdak-login-sms-verify-phone">شماره موبایل</label>
      <input id="pvdak-login-sms-verify-phone" name="sms_phone" type="tel" dir="ltr" inputmode="tel" placeholder="09123456789" maxlength="20" required>
      <label for="pvdak-login-sms-code">کد تأیید</label>
      <input id="pvdak-login-sms-code" name="sms_code" type="text" dir="ltr" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9۰-۹]{6}" maxlength="6" required>
      <button type="submit">ورود با کد</button>
    </form>
    <?php endif; ?>
  </div>
</section>
    <section id="pvdak-auth-register-email" role="tabpanel" aria-labelledby="pvdak-register-email-tab" data-pvdak-auth-pane="register-email" <?php echo (!$register_selected || $sms_register_selected) ? 'hidden' : ''; ?>>
      <?php if($state&&isset($email_states[$state])): ?>
        <p id="pvdak-register-email-result" tabindex="-1" class="pvdak-auth-feedback" role="<?php echo in_array($state,array('received','verified','resent'),true) ? 'status' : 'alert'; ?>"><?php echo esc_html($email_states[$state]); ?></p>
      <?php endif; ?>
      <?php if($registration_open): ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="pvdak-forms pvdak-auth-form" data-pvdak-single-submit>
          <input type="hidden" name="action" value="pvdak_register">
          <?php wp_nonce_field('pvdak_register','_pvdak_register_nonce'); ?>
          <div class="pvdak-honeypot" aria-hidden="true"><input name="register_site" tabindex="-1" autocomplete="off"></div>
          <label for="register-user">نام کاربری</label>
          <input id="register-user" name="register_username" minlength="3" maxlength="50" autocomplete="username" required>
          <label for="register-email">آدرس ایمیل</label>
          <input id="register-email" name="register_email" type="email" autocomplete="email" maxlength="254" required>
          <label for="register-password">رمز عبور (حداقل ۱۲ نویسه)</label>
          <div class="pvdak-password-field"><input id="register-password" type="password" name="register_password" minlength="12" maxlength="256" autocomplete="new-password" required><button type="button" class="pvdak-password-toggle" data-pvdak-password-toggle="register-password" aria-controls="register-password" aria-pressed="false">نمایش</button></div>
          <label for="register-role">نوع حساب</label>
          <select id="register-role" name="register_role" required>
            <option value="client">مراجعه‌کننده</option>
            <option value="lawyer">متقاضی همکاری وکیل</option>
            <option value="trainee">متقاضی همکاری کارآموز</option>
            <option value="student">دانشجوی حقوق</option>
          </select>
          <label class="pvdak-check"><input type="checkbox" name="register_consent" required><span>با <?php
            // The same published policies are linked from both registration methods.
            if($terms): ?><a href="<?php echo esc_url($terms); ?>">شرایط استفاده</a><?php else: ?>شرایط استفاده<?php endif; ?>
            و <?php if($privacy): ?><a href="<?php echo esc_url($privacy); ?>">حریم خصوصی</a><?php else: ?>حریم خصوصی<?php endif; ?> موافقم.</span></label>
          <button type="submit">ایجاد حساب</button>
        </form>
      <?php else: ?>
        <p class="pvdak-auth-feedback" role="status">ثبت‌نام در حال حاضر در دسترس نیست.</p>
      <?php endif; ?>
        <details class="pvdak-register-resend">
          <summary>ایمیل تأیید را دریافت نکرده‌ام</summary>
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="pvdak-forms" data-pvdak-single-submit>
            <input type="hidden" name="action" value="pvdak_verify_resend">
            <?php wp_nonce_field('pvdak_verify_resend','_pvdak_verify_nonce'); ?>
            <label for="pvdak-resend-email">آدرس ایمیل</label>
            <input id="pvdak-resend-email" name="verify_email" type="email" autocomplete="email" required>
            <button type="submit">ارسال دوباره لینک تأیید</button>
          </form>
        </details>
    </section>

    <section id="pvdak-auth-sms-register" role="tabpanel" aria-labelledby="pvdak-register-sms-tab" data-pvdak-auth-pane="sms-register" <?php echo ($register_selected && $sms_register_selected) ? '' : 'hidden'; ?>>
      <?php if($sms_status&&isset($sms_states[$sms_status])): ?>
        <p id="pvdak-register-sms-result" tabindex="-1" class="pvdak-auth-feedback" role="<?php echo 'sent' === $sms_status ? 'status' : 'alert'; ?>"><?php echo esc_html($sms_states[$sms_status]); ?></p>
      <?php endif; ?>
      <?php if($sms_ready&&$registration_open): ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="pvdak-forms pvdak-auth-form" data-pvdak-single-submit>
          <input type="hidden" name="action" value="pvdak_sms_send">
          <input type="hidden" name="sms_intent" value="register">
          <?php wp_nonce_field('pvdak_sms_send','_pvdak_sms_nonce'); ?>
          <label for="pvdak-register-phone">شماره موبایل</label>
          <input id="pvdak-register-phone" name="sms_phone" type="tel" dir="ltr" inputmode="tel" autocomplete="tel-national" placeholder="09123456789" maxlength="20" required>
          <label for="pvdak-sms-role">نوع حساب</label>
          <select id="pvdak-sms-role" name="sms_role" required>
            <option value="client">مراجعه‌کننده</option>
            <option value="lawyer">متقاضی همکاری وکیل</option>
            <option value="trainee">متقاضی همکاری کارآموز</option>
            <option value="student">دانشجوی حقوق</option>
          </select>
          <label class="pvdak-check"><input type="checkbox" name="sms_consent" required><span>با <?php if($terms): ?><a href="<?php echo esc_url($terms); ?>">شرایط استفاده</a><?php else: ?>شرایط استفاده<?php endif; ?> و <?php if($privacy): ?><a href="<?php echo esc_url($privacy); ?>">حریم خصوصی</a><?php else: ?>حریم خصوصی<?php endif; ?> موافقم.</span></label>
          <button type="submit">دریافت کد تأیید</button>
        </form>
        <details class="pvdak-register-resend" <?php echo 'sent'===$sms_status ? 'open' : ''; ?>>
          <summary>کد تأیید را دریافت کرده‌ام</summary>
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="pvdak-forms pvdak-auth-form" data-pvdak-single-submit>
            <input type="hidden" name="action" value="pvdak_sms_verify">
            <input type="hidden" name="sms_intent" value="register">
            <?php wp_nonce_field('pvdak_sms_verify','_pvdak_sms_verify_nonce'); ?>
            <label for="pvdak-register-verify-phone">شماره موبایل</label>
            <input id="pvdak-register-verify-phone" name="sms_phone" type="tel" dir="ltr" inputmode="tel" placeholder="09123456789" maxlength="20" required>
            <label for="pvdak-register-sms-code">کد شش‌رقمی</label>
            <input id="pvdak-register-sms-code" name="sms_code" type="text" dir="ltr" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9۰-۹]{6}" maxlength="6" required>
            <button type="submit">تأیید و ایجاد حساب</button>
          </form>
        </details>
      <?php else: ?>
        <p class="pvdak-auth-feedback" role="status">ثبت‌نام پیامکی به‌زودی در دسترس قرار می‌گیرد.</p>
      <?php endif; ?>
    </section>

<p class="pvdak-login-signup" data-pvdak-auth-switch="register" <?php echo $register_selected?'hidden':''; ?>>حساب کاربری ندارید؟ <a data-pvdak-auth-go="register-email" href="<?php echo esc_url(pvdak_registration_result_url()); ?>">ثبت‌نام در PVDak</a></p>
<p class="pvdak-register-back" data-pvdak-auth-switch="login" <?php echo $register_selected?'':'hidden'; ?>>قبلاً حساب ساخته‌اید؟ <a data-pvdak-auth-go="password" href="<?php echo esc_url(pvdak_auth_login_url()); ?>">ورود به حساب</a></p>
</section>
<aside class="pvdak-login-art pvdak-auth-visual"><span class="pvdak-auth-scale" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M32 7v47M11 18h42M18 19 7 43h22L18 19Zm28 0L35 43h22L46 19ZM18 55h28"/></svg></span><span class="pvdak-auth-art-label">PVDAK • خدمات حقوقی</span><h2>مسیر روشن پیگیری</h2><p>پروفایل شخصی، سوابق درخواست و گفت‌وگوهای پشتیبانی در یک فضای منسجم.</p><ul class="pvdak-login-benefits"><li>دسترسی به اطلاعات حساب</li><li>پیگیری پیام‌های خصوصی</li><li>مدیریت وضعیت همکاری</li></ul></aside>
</div></main><?php get_footer(); ?>
