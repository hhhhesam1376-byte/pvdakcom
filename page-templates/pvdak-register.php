<?php
/**
 * Template Name: ثبت‌نام PVDak
 * Template Post Type: page
 */
if(!defined('ABSPATH'))exit;
if(is_user_logged_in()){wp_safe_redirect(pvdak_auth_account_url());exit;}
$state=isset($_GET['state'])&&is_string($_GET['state'])?sanitize_key(wp_unslash($_GET['state'])):'';
$sms_status=isset($_GET['sms_status'])&&is_string($_GET['sms_status'])?sanitize_key(wp_unslash($_GET['sms_status'])):'';
$sms_ready=function_exists('pvdak_sms_auth_ready')&&pvdak_sms_auth_ready();
// Match the SMS handler's ?auth=sms-register redirect even without JavaScript.
$sms_selected=(isset($_GET['auth'])&&is_string($_GET['auth'])&&
    'sms-register'===sanitize_key(wp_unslash($_GET['auth']))) ||
    (!isset($_GET['auth'])&&''!==$sms_status);
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
get_header();
?>
<main id="main-content" class="container pvdak-static pvdak-register-page" tabindex="-1">
  <nav class="pvdak-account-breadcrumbs" aria-label="مسیر صفحه"><a href="<?php echo esc_url(pvdak_auth_login_url()); ?>">ورود به حساب</a><span aria-hidden="true"> / </span><span aria-current="page">ثبت‌نام</span></nav>
  <header class="pvdak-static-head">
    <span>حساب کاربری PVDak</span><h1>ایجاد حساب</h1>
    <p>روش ثبت‌نام خود را انتخاب کنید.</p>
  </header>
  <div class="pvdak-static-body pvdak-recovery-panel pvdak-register-surface">
    <div class="pvdak-auth-mode-picker pvdak-register-tabs" data-pvdak-auth-picker role="tablist" aria-label="روش ثبت‌نام">
      <button type="button" data-pvdak-auth-tab="register-email" role="tab" id="pvdak-register-email-tab" aria-controls="pvdak-auth-register-email" aria-selected="<?php echo $sms_selected ? 'false' : 'true'; ?>" tabindex="<?php echo $sms_selected ? '-1' : '0'; ?>">ثبت‌نام با ایمیل</button>
      <button type="button" data-pvdak-auth-tab="sms-register" role="tab" id="pvdak-register-sms-tab" aria-controls="pvdak-auth-sms-register" aria-selected="<?php echo $sms_selected ? 'true' : 'false'; ?>" tabindex="<?php echo $sms_selected ? '0' : '-1'; ?>">ثبت‌نام با پیامک</button>
    </div>

    <section id="pvdak-auth-register-email" role="tabpanel" aria-labelledby="pvdak-register-email-tab" data-pvdak-auth-pane="register-email" <?php echo $sms_selected ? 'hidden' : ''; ?>>
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

    <section id="pvdak-auth-sms-register" role="tabpanel" aria-labelledby="pvdak-register-sms-tab" data-pvdak-auth-pane="sms-register" <?php echo $sms_selected ? '' : 'hidden'; ?>>
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
    <p class="pvdak-register-back"><a href="<?php echo esc_url(pvdak_auth_login_url()); ?>">قبلاً حساب ساخته‌ام؛ ورود</a></p>
  </div>
</main>
<?php get_footer(); ?>
