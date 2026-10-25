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
<p>با ایمیل و رمز ثابت وارد شوید یا روش پیامکی و ثبت‌نام را انتخاب کنید.</p>
<p class="pvdak-login-trust-note"><span aria-hidden="true">✓</span> چهار مسیر در یک حساب کاربری وردپرس؛ ارسال پیامک فقط پس از اتصال سرویس معتبر.</p>
<div class="pvdak-auth-mode-picker" data-pvdak-auth-picker role="tablist" aria-label="روش ورود و ثبت‌نام">
  <button type="button" data-pvdak-auth-tab="password" aria-controls="pvdak-auth-password" aria-selected="true" role="tab">ورود با رمز ثابت</button>
  <button type="button" data-pvdak-auth-tab="sms-login" aria-controls="pvdak-auth-sms-login" aria-selected="false" role="tab">ورود با پیامک</button>
  <button type="button" data-pvdak-auth-tab="register-email" aria-controls="pvdak-auth-register-email" aria-selected="false" role="tab">ثبت‌نام با ایمیل</button>
  <button type="button" data-pvdak-auth-tab="sms-register" aria-controls="pvdak-auth-sms-register" aria-selected="false" role="tab">ثبت‌نام با پیامک</button>
</div>
<?php
$auth_status=isset($_GET['sms_status']) && is_string($_GET['sms_status']) ? sanitize_key(wp_unslash($_GET['sms_status'])) : '';
$email_state=isset($_GET['state']) && is_string($_GET['state']) ? sanitize_key(wp_unslash($_GET['state'])) : '';
$sms_ready=function_exists('pvdak_sms_auth_ready') && pvdak_sms_auth_ready();
$sms_messages=array(
  'sent'=>'اگر شماره واجد شرایط باشد، کد تأیید ارسال شده است. کد پنج دقیقه اعتبار دارد.',
  'slow'=>'درخواست‌های زیادی ثبت شده است؛ کمی بعد تلاش کنید.',
  'invalid'=>'کد یا شماره معتبر نیست یا زمان کد به پایان رسیده است.',
  'unavailable'=>'سرویس پیامکی هنوز متصل نیست یا ارسال پیامک انجام نشد.',
  'closed'=>'ثبت‌نام در حال حاضر غیرفعال است.',
);
?>
<?php if($admin_preview): ?><p class="pvdak-static-note">حالت پیش‌نمایش مدیر فعال است. برای آزمایش ورود واقعی از حساب غیرمدیر در محیط آزمایشی استفاده کنید.</p><?php endif; ?>
<section id="pvdak-auth-password" data-pvdak-auth-pane="password" aria-label="ورود با ایمیل و رمز ثابت">
<h2 class="pvdak-auth-pane-heading">ورود با ایمیل و رمز عبور</h2>
<?php if(isset($_GET['error'])): ?>
<p id="pvdak-login-result" tabindex="-1" class="pvdak-static-error" role="alert"><?php echo isset($_GET['error']) && is_string($_GET['error']) && 'slow' === sanitize_key(wp_unslash($_GET['error'])) ? 'تلاش‌های ورود بیش از حد بوده است. چند دقیقه بعد دوباره امتحان کنید.' : 'اطلاعات ورود تأیید نشد. نام کاربری و رمز عبور را بررسی کنید.'; ?></p>
<?php endif; ?>
<form method="post" class="pvdak-forms pvdak-auth-form" data-pvdak-single-submit action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
<input type="hidden" name="action" value="pvdak_login"><?php wp_nonce_field('pvdak_login','_pvdak_login_nonce'); ?>
<label for="pvdak-identity"><span aria-hidden="true">◉</span> نام کاربری یا ایمیل</label><input id="pvdak-identity" name="login_identity" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="250" <?php if(isset($_GET['error']) && is_string($_GET['error']) && 'slow' !== sanitize_key(wp_unslash($_GET['error']))): ?>aria-invalid="true" aria-describedby="pvdak-login-result"<?php endif; ?> required>
<label for="pvdak-password"><span aria-hidden="true">♢</span> رمز عبور</label><div class="pvdak-password-field"><input id="pvdak-password" type="password" name="login_password" autocomplete="current-password" <?php if(isset($_GET['error']) && is_string($_GET['error']) && 'slow' !== sanitize_key(wp_unslash($_GET['error']))): ?>aria-invalid="true" aria-describedby="pvdak-login-result"<?php endif; ?> required><button type="button" class="pvdak-password-toggle" data-pvdak-password-toggle="pvdak-password" aria-controls="pvdak-password" aria-pressed="false">نمایش</button></div>
<label class="pvdak-check"><input type="checkbox" name="login_remember"><span>مرا به خاطر بسپار</span></label>
<button type="submit"><span aria-hidden="true">←</span> ورود به حساب</button></form></section>
<section id="pvdak-auth-register-email" data-pvdak-auth-pane="register-email" aria-label="ثبت‌نام ایمیلی">
  <h2 class="pvdak-auth-pane-heading">ساخت حساب با ایمیل</h2>
  <?php if ($email_state): ?>
    <p class="pvdak-static-note" role="status"><?php
      echo esc_html('received'===$email_state?'حساب ساخته شد؛ برای ورود پیوند تأیید ایمیل را بررسی کنید.':
        ('resent'===$email_state?'اگر حساب در انتظار تأیید باشد، ایمیل ارسال می‌شود.':'اطلاعات را بررسی کنید و دوباره تلاش کنید.'));
    ?></p>
  <?php endif; ?>
  <?php if (!pvdak_registration_open()): ?>
    <p class="pvdak-static-note" role="status">ثبت‌نام هنوز توسط مدیر فعال نشده است. می‌توانید فرم را ببینید؛ امکان ارسال پس از فعال‌سازی در تنظیمات عمومی در دسترس قرار می‌گیرد.</p>
  <?php endif; ?>
    <form class="pvdak-forms pvdak-auth-form" data-pvdak-single-submit method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <fieldset class="pvdak-auth-form-fields" <?php disabled(!pvdak_registration_open(),true); ?>>
      <input type="hidden" name="action" value="pvdak_register">
      <input type="hidden" name="register_source" value="login">
      <?php wp_nonce_field('pvdak_register','_pvdak_register_nonce'); ?>
      <div class="pvdak-honeypot" aria-hidden="true"><input name="register_site" tabindex="-1" autocomplete="off"></div>
      <label for="pvdak-signup-name">نام کاربری</label><input id="pvdak-signup-name" name="register_username" minlength="3" maxlength="50" autocomplete="username" required>
      <label for="pvdak-signup-email">ایمیل</label><input id="pvdak-signup-email" name="register_email" type="email" autocomplete="email" required>
      <label for="pvdak-signup-pass">رمز عبور ثابت (حداقل ۱۲ نویسه)</label>
      <div class="pvdak-password-field"><input id="pvdak-signup-pass" name="register_password" type="password" minlength="12" maxlength="256" autocomplete="new-password" required><button class="pvdak-password-toggle" type="button" data-pvdak-password-toggle="pvdak-signup-pass" aria-controls="pvdak-signup-pass" aria-pressed="false">نمایش</button></div>
      <label for="pvdak-signup-role">نوع حساب</label><select id="pvdak-signup-role" name="register_role" required>
        <option value="client">مراجعه‌کننده</option><option value="lawyer">متقاضی همکاری وکیل</option>
        <option value="trainee">متقاضی کارآموزی</option><option value="student">دانشجوی حقوق</option>
      </select>
      <p class="pvdak-static-note">نوع حساب حرفه‌ای تنها درخواست بررسی است و دسترسی وکیل ایجاد نمی‌کند.</p>
      <label class="pvdak-check"><input name="register_consent" type="checkbox" required><span>با شرایط استفاده و حریم خصوصی PVDak موافقم.</span></label>
      <button type="submit">ساخت حساب و دریافت ایمیل تأیید</button>
    </fieldset>
    </form>
</section>
<?php foreach (array('login'=>'sms-login','register'=>'sms-register') as $intent=>$pane): ?>
<section id="pvdak-auth-<?php echo esc_attr($pane); ?>" data-pvdak-auth-pane="<?php echo esc_attr($pane); ?>" aria-label="<?php echo esc_attr('login'===$intent?'ورود با کد پیامکی':'ثبت‌نام با کد پیامکی'); ?>">
  <h2 class="pvdak-auth-pane-heading"><?php echo esc_html('login'===$intent?'ورود با کد پیامکی':'ساخت حساب با پیامک'); ?></h2>
  <?php if ($auth_status && isset($sms_messages[$auth_status]) &&
    isset($_GET['auth']) && sanitize_key(wp_unslash($_GET['auth']))===$pane): ?>
    <p class="pvdak-static-note" role="status"><?php echo esc_html($sms_messages[$auth_status]); ?></p>
  <?php endif; ?>
  <?php if (!$sms_ready): ?>
    <p class="pvdak-static-note" role="status">اتصال به سرویس ارسال پیامک هنوز انجام نشده است؛ فرم‌ها پیش‌نمایش هستند و دکمه ارسال کد تا اتصال سرویس فعال نمی‌شود.</p>
  <?php elseif ('register'===$intent && !pvdak_registration_open()): ?>
    <p class="pvdak-static-note">ثبت‌نام هنوز توسط مدیر سایت فعال نشده است.</p>
  <?php endif; ?>
    <p class="pvdak-auth-method-description">شماره موبایل ایران را وارد کنید. برای ورود، شماره باید قبلاً در همین سامانه تأیید شده باشد.</p>
    <form class="pvdak-forms pvdak-auth-form" data-pvdak-single-submit method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="pvdak_sms_send"><input type="hidden" name="sms_intent" value="<?php echo esc_attr($intent); ?>">
      <?php wp_nonce_field('pvdak_sms_send','_pvdak_sms_nonce'); ?>
      <label for="pvdak-sms-send-<?php echo esc_attr($intent); ?>">شماره موبایل</label>
      <input id="pvdak-sms-send-<?php echo esc_attr($intent); ?>" type="tel" name="sms_phone" inputmode="tel" dir="ltr" autocomplete="tel-national" placeholder="09123456789" maxlength="20" required>
      <?php if ('register'===$intent): ?>
      <label for="pvdak-sms-role">نوع حساب</label><select id="pvdak-sms-role" name="sms_role" required>
        <option value="client">مراجعه‌کننده</option><option value="lawyer">متقاضی همکاری وکیل</option>
        <option value="trainee">متقاضی کارآموزی</option><option value="student">دانشجوی حقوق</option>
      </select>
      <label class="pvdak-check"><input type="checkbox" name="sms_consent" required><span>با شرایط استفاده و حریم خصوصی PVDak موافقم.</span></label>
      <?php endif; ?>
      <button type="submit" <?php disabled(!$sms_ready || ('register'===$intent && !pvdak_registration_open()),true); ?>>دریافت کد تأیید</button>
    </form>
    <div class="pvdak-auth-otp-verification">
      <h3>کد را دریافت کرده‌ام</h3>
      <form class="pvdak-forms pvdak-auth-form" data-pvdak-single-submit method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="pvdak_sms_verify"><input type="hidden" name="sms_intent" value="<?php echo esc_attr($intent); ?>">
        <?php wp_nonce_field('pvdak_sms_verify','_pvdak_sms_verify_nonce'); ?>
        <label for="pvdak-sms-verify-phone-<?php echo esc_attr($intent); ?>">شماره موبایل</label>
        <input id="pvdak-sms-verify-phone-<?php echo esc_attr($intent); ?>" type="tel" name="sms_phone" dir="ltr" autocomplete="tel-national" placeholder="09123456789" maxlength="20" required>
        <label for="pvdak-sms-code-<?php echo esc_attr($intent); ?>">کد ۶ رقمی</label>
        <input id="pvdak-sms-code-<?php echo esc_attr($intent); ?>" type="text" name="sms_code" dir="ltr" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9۰-۹]{6}" maxlength="6" required>
        <button type="submit" <?php disabled(!$sms_ready || ('register'===$intent && !pvdak_registration_open()),true); ?>>تأیید کد و <?php echo esc_html('login'===$intent?'ورود':'ساخت حساب'); ?></button>
      </form>
    </div>
</section>
<?php endforeach; ?>

<?php if(isset($_GET['status']) && 'reset' === (string) $_GET['status']):?><p class="pvdak-static-note" role="status">رمز عبور تغییر کرد؛ با اطلاعات جدید وارد شوید.</p><?php endif; ?>
<p class="pvdak-login-help pvdak-auth-help" aria-label="راه‌های دسترسی به حساب"><a href="<?php echo esc_url(pvdak_account_tool_url('account-recovery')); ?>">رمز عبور را فراموش کرده‌ام</a> · <a href="<?php echo esc_url(add_query_arg('auth','register-email',pvdak_auth_login_url())); ?>">ثبت‌نام با ایمیل</a> · <a href="<?php echo esc_url(add_query_arg('auth','sms-register',pvdak_auth_login_url())); ?>">ثبت‌نام با پیامک</a>. برای مشکل دسترسی از <a href="<?php echo esc_url(pvdak_public_page_url('contact-us')?:home_url('/contact-us/')); ?>">تماس با ما</a> استفاده کنید.</p>
</section>
<aside class="pvdak-login-art pvdak-auth-visual"><span class="pvdak-auth-scale" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M32 7v47M11 18h42M18 19 7 43h22L18 19Zm28 0L35 43h22L46 19ZM18 55h28"/></svg></span><span class="pvdak-auth-art-label">PVDAK • PVDak وکلای دادگستری</span><h2>مسیر روشن پیگیری</h2><p>پروفایل شخصی، سوابق درخواست و گفت‌وگوهای پشتیبانی در یک فضای منسجم.</p><ul class="pvdak-login-benefits"><li>دسترسی به اطلاعات حساب</li><li>پیگیری پیام‌های خصوصی</li><li>مدیریت وضعیت همکاری</li></ul></aside>
</div></main><?php get_footer(); ?>
