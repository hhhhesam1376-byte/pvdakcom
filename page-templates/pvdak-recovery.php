<?php
/**
 * Template Name: بازیابی رمز PVDak
 * Template Post Type: page
 */
if(!defined('ABSPATH'))exit;
// Backwards compatibility for links issued before the login-only recovery.
// No second recovery interface when the canonical PVDak login Page is ready.
$canonical = pvdak_public_template_page_url('account-login', 'page-templates/pvdak-login.php');
if ($canonical) {
    $args = array('auth' => 'recover');
    if (isset($_GET['reset']) && '1' === (string) $_GET['reset']) {
        $args['reset'] = '1';
        $args['auth'] = 'recover-change';
        if (isset($_GET['key']) && is_string($_GET['key'])) {
            $args['key'] = sanitize_text_field(wp_unslash($_GET['key']));
        }
        if (isset($_GET['login']) && is_string($_GET['login'])) {
            $args['login'] = sanitize_text_field(wp_unslash($_GET['login']));
        }
    }
    if (isset($_GET['status']) && is_string($_GET['status'])) {
        $status = sanitize_key(wp_unslash($_GET['status']));
        if (in_array($status, array('sent','invalid','failed'), true)) {
            $args['status'] = $status;
        }
    }
    wp_safe_redirect(add_query_arg($args, $canonical), 302);
    exit;
}
$login=isset($_GET['login'])&&is_string($_GET['login'])?sanitize_text_field(wp_unslash($_GET['login'])):'';
$key=isset($_GET['key'])&&is_string($_GET['key'])?sanitize_text_field(wp_unslash($_GET['key'])):'';
$reset=isset($_GET['reset'])&&'1'===(string)$_GET['reset'];
$valid=$reset&&$login&&$key&&strlen($login)<=250&&strlen($key)<=64&&
    !is_wp_error(check_password_reset_key($key,$login));
$status=isset($_GET['status'])&&is_string($_GET['status'])?sanitize_key(wp_unslash($_GET['status'])):'';
get_header(); ?>
<main id="main-content" class="container pvdak-static pvdak-login-page" tabindex="-1">
<nav class="pvdak-account-breadcrumbs" aria-label="مسیر صفحه"><a href="<?php echo esc_url( pvdak_auth_login_url() ); ?>">ورود</a><span aria-hidden="true"> / </span><span aria-current="page">بازیابی رمز</span></nav>
<div class="pvdak-static-body pvdak-recovery-panel pvdak-recovery-surface">
  <div class="pvdak-recovery-heading">
    <span class="pvdak-recovery-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
    <div><span class="pvdak-auth-eyebrow">حفاظت از حساب کاربری</span><h1>بازیابی رمز عبور</h1></div>
  </div>
  <ol class="pvdak-recovery-steps" aria-label="مراحل بازیابی">
    <li class="<?php echo $valid ? 'is-complete' : 'is-active'; ?>">درخواست پیوند</li>
    <li class="<?php echo $valid ? 'is-active' : ''; ?>">انتخاب رمز جدید</li>
    <li>ورود دوباره</li>
  </ol>
  <?php if($status==='sent'):?><p id="pvdak-recovery-result" tabindex="-1" role="status" class="pvdak-static-note">اگر این حساب موجود باشد، راهنمای بازیابی رمز به ایمیل ثبت‌شده ارسال خواهد شد.</p><?php endif; ?>
  <?php if($status==='invalid'):?><p id="pvdak-recovery-result" tabindex="-1" role="alert" class="pvdak-static-error">پیوند بازیابی نامعتبر یا منقضی شده است؛ درخواست تازه‌ای ثبت کنید.</p><?php endif; ?>
  <?php if($status==='failed'):?><p id="pvdak-recovery-result" tabindex="-1" role="alert" class="pvdak-static-error">تغییر رمز عبور ثبت نشد. لطفاً پیوند بازیابی تازه‌ای درخواست کنید.</p><?php endif; ?>
  <?php if($valid): ?>
    <p class="pvdak-recovery-instruction">برای حساب خود رمز جدیدی انتخاب کنید. از رمز تکراری یا قابل حدس استفاده نکنید.</p>
    <form method="post" class="pvdak-forms" data-pvdak-single-submit data-pvdak-recovery-change action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="pvdak_recovery_change">
      <?php wp_nonce_field('pvdak_recovery_change','_pvdak_reset_nonce'); ?>
      <input type="hidden" name="login" value="<?php echo esc_attr($login); ?>">
      <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>">
      <label for="new-password">رمز عبور جدید (حداقل ۱۲ نویسه)</label>
      <div class="pvdak-password-field"><input id="new-password" type="password" name="new_password" minlength="12" maxlength="256" autocomplete="new-password" required><button type="button" class="pvdak-password-toggle" data-pvdak-password-toggle="new-password" aria-controls="new-password" aria-pressed="false">نمایش</button></div>
      <label for="confirm-password">تکرار رمز عبور</label>
      <div class="pvdak-password-field"><input id="confirm-password" type="password" name="confirm_password" minlength="12" maxlength="256" autocomplete="new-password" aria-describedby="pvdak-password-match" required><button type="button" class="pvdak-password-toggle" data-pvdak-password-toggle="confirm-password" aria-controls="confirm-password" aria-pressed="false">نمایش</button></div><p id="pvdak-password-match" class="pvdak-field-help" role="status" aria-live="polite">رمز عبور و تکرار آن باید یکسان باشند.</p>
      <button type="submit">ثبت رمز جدید</button>
    </form>
  <?php else: ?>
    <?php if($reset):?><p class="pvdak-static-error">پیوند بازیابی معتبر نیست یا اعتبارش تمام شده است.</p><?php endif; ?>
    <p class="pvdak-recovery-instruction">نام کاربری یا ایمیل خود را وارد کنید. اگر حسابی وجود داشته باشد، راهنمای بازیابی ارسال می‌شود.</p>
    <form method="post" class="pvdak-forms" data-pvdak-single-submit action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="pvdak_recovery_request">
      <?php wp_nonce_field('pvdak_recovery_request','_pvdak_recovery_nonce'); ?>
      <label for="recovery-email">نام کاربری یا ایمیل</label>
      <input id="recovery-email" name="identity" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="250" required>
      <button type="submit">ارسال پیوند بازیابی</button>
    </form>
  <?php endif; ?>
  <p class="pvdak-recovery-return"><a href="<?php echo esc_url(pvdak_auth_login_url()); ?>">← بازگشت به صفحه ورود</a></p>
</div>
</main><?php get_footer(); ?>
