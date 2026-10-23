<?php
/**
 * Template Name: بازیابی رمز PVDak
 * Template Post Type: page
 */
if(!defined('ABSPATH'))exit;
$login=isset($_GET['login'])&&is_string($_GET['login'])?sanitize_text_field(wp_unslash($_GET['login'])):'';
$key=isset($_GET['key'])&&is_string($_GET['key'])?sanitize_text_field(wp_unslash($_GET['key'])):'';
$reset=isset($_GET['reset'])&&'1'===(string)$_GET['reset'];
$valid=$reset&&$login&&$key&&!is_wp_error(check_password_reset_key($key,$login));
$status=isset($_GET['status'])&&is_string($_GET['status'])?sanitize_key(wp_unslash($_GET['status'])):'';
get_header(); ?>
<main id="main-content" class="container pvdak-static pvdak-login-page" tabindex="-1">
<div class="pvdak-static-body pvdak-recovery-panel">
  <h1>بازیابی رمز عبور</h1>
  <?php if($status==='sent'):?><p role="status" class="pvdak-static-note">اگر این حساب موجود باشد، راهنمای بازیابی رمز به ایمیل ثبت‌شده ارسال خواهد شد.</p><?php endif; ?>
  <?php if($status==='invalid'):?><p role="alert" class="pvdak-static-error">پیوند بازیابی نامعتبر یا منقضی شده است؛ درخواست تازه‌ای ثبت کنید.</p><?php endif; ?>
  <?php if($valid): ?>
    <p>برای حساب خود یک رمز عبور جدید انتخاب کنید.</p>
    <form method="post" class="pvdak-forms" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="pvdak_recovery_change">
      <?php wp_nonce_field('pvdak_recovery_change','_pvdak_reset_nonce'); ?>
      <input type="hidden" name="login" value="<?php echo esc_attr($login); ?>">
      <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>">
      <label for="new-password">رمز عبور جدید (حداقل ۱۲ نویسه)</label>
      <input id="new-password" type="password" name="new_password" minlength="12" autocomplete="new-password" required>
      <label for="confirm-password">تکرار رمز عبور</label>
      <input id="confirm-password" type="password" name="confirm_password" minlength="12" autocomplete="new-password" required>
      <button type="submit">ثبت رمز جدید</button>
    </form>
  <?php else: ?>
    <?php if($reset):?><p class="pvdak-static-error">پیوند بازیابی معتبر نیست یا اعتبارش تمام شده است.</p><?php endif; ?>
    <p>نام کاربری یا ایمیل خود را وارد کنید. در صورت وجود حساب، پیوند بازیابی به شما ایمیل می‌شود.</p>
    <form method="post" class="pvdak-forms" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="pvdak_recovery_request">
      <?php wp_nonce_field('pvdak_recovery_request','_pvdak_recovery_nonce'); ?>
      <label for="recovery-email">نام کاربری یا ایمیل</label>
      <input id="recovery-email" name="identity" autocomplete="username" required>
      <button type="submit">ارسال پیوند بازیابی</button>
    </form>
  <?php endif; ?>
  <p><a href="<?php echo esc_url(pvdak_auth_login_url()); ?>">بازگشت به صفحه ورود</a></p>
</div>
</main><?php get_footer(); ?>
