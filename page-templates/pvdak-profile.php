<?php
/**
 * Template Name: پروفایل کاربری PVDak
 * Template Post Type: page
 */
if(!defined('ABSPATH'))exit;
if(!is_user_logged_in()){wp_safe_redirect(pvdak_auth_login_url());exit;}
$user=wp_get_current_user();
$status=isset($_GET['status'])&&is_string($_GET['status'])?sanitize_key(wp_unslash($_GET['status'])):'';
get_header(); ?>
<main id="main-content" class="container pvdak-static pvdak-profile-page" tabindex="-1">
<nav class="pvdak-account-breadcrumbs" aria-label="مسیر صفحه"><a href="<?php echo esc_url( pvdak_auth_account_url() ); ?>">پنل من</a><span aria-hidden="true"> / </span><span aria-current="page">پروفایل</span></nav>
  <header class="pvdak-static-head"><span>اطلاعات حساب</span><h1>پروفایل من</h1><p>نام نمایشی خود را تغییر دهید؛ نام کاربری و ایمیل این فرم قابل ویرایش نیستند.</p></header>
  <div class="pvdak-static-body">
    <?php if('saved'===$status):?><p id="pvdak-profile-result" tabindex="-1" role="status" class="pvdak-static-note">نام نمایشی ذخیره شد.</p>
    <?php elseif('unchanged'===$status):?><p id="pvdak-profile-result" tabindex="-1" role="status" class="pvdak-static-note">نام نمایشی تغییری نکرده است.</p>
    <?php elseif('invalid'===$status):?><p id="pvdak-profile-result" tabindex="-1" role="alert" class="pvdak-static-error">نام نمایشی باید بین ۱ تا ۱۲۰ نویسه باشد.</p>
    <?php elseif($status):?><p id="pvdak-profile-result" tabindex="-1" role="alert" class="pvdak-static-error">ذخیره اطلاعات انجام نشد؛ دوباره تلاش کنید.</p><?php endif;?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="pvdak-forms" data-pvdak-profile-form data-pvdak-single-submit>
      <input type="hidden" name="action" value="pvdak_profile_save">
      <?php wp_nonce_field('pvdak_profile_save','_pvdak_profile_nonce'); ?>
      <label for="account-display-name">نام نمایشی</label>
      <input id="account-display-name" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" maxlength="120" autocomplete="nickname" aria-describedby="pvdak-profile-help pvdak-profile-count" required>
      <p id="pvdak-profile-help" class="pvdak-field-help">این نام در پنل شما نمایش داده می‌شود؛ اطلاعات محرمانه یا شماره پرونده وارد نکنید.</p>
      <span id="pvdak-profile-count" class="pvdak-field-count" data-pvdak-count-for="account-display-name" aria-live="off"></span>
      <p class="pvdak-form-change-status" data-pvdak-profile-status role="status" aria-live="polite"></p>
      <div class="pvdak-profile-details" aria-label="مشخصات ثابت حساب"><div><strong>نام کاربری</strong><span dir="auto"><?php echo esc_html( $user->user_login ); ?></span><small>غیرقابل تغییر</small></div><div><strong>ایمیل حساب</strong><span dir="ltr"><?php echo esc_html( $user->user_email ); ?></span><small>برای تغییر ایمیل با مدیر تماس بگیرید.</small></div></div>
      <div class="pvdak-form-actions"><button type="submit" data-pvdak-profile-save>ذخیره تغییرات</button><button type="reset" class="pvdak-secondary-button">بازگرداندن مقدار قبلی</button></div>
    </form>
    <p><a href="<?php echo esc_url(pvdak_account_tool_url('account-recovery')); ?>">بازیابی یا تغییر رمز عبور با ایمیل</a></p>
    <p><a href="<?php echo esc_url(pvdak_auth_account_url()); ?>">بازگشت به پنل</a></p>
  </div>
</main><?php get_footer(); ?>
