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
<main id="main-content" class="container pvdak-static" tabindex="-1">
  <header class="pvdak-static-head"><span>اطلاعات حساب</span><h1>پروفایل من</h1><p>اطلاعات عمومی حساب شما</p></header>
  <div class="pvdak-static-body">
    <?php if('saved'===$status):?><p role="status" class="pvdak-static-note">اطلاعات حساب ذخیره شد.</p><?php elseif($status):?><p role="alert" class="pvdak-static-error">ذخیره اطلاعات انجام نشد.</p><?php endif;?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="pvdak-forms">
      <input type="hidden" name="action" value="pvdak_profile_save">
      <?php wp_nonce_field('pvdak_profile_save','_pvdak_profile_nonce'); ?>
      <label for="account-display-name">نام نمایشی</label>
      <input id="account-display-name" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" maxlength="120" required>
      <label>نام کاربری</label><p><?php echo esc_html($user->user_login); ?> (غیرقابل تغییر)</p>
      <label>ایمیل حساب</label><p><?php echo esc_html($user->user_email); ?> (برای تغییر ایمیل با مدیر تماس بگیرید)</p>
      <button type="submit">ذخیره تغییرات</button>
    </form>
    <p><a href="<?php echo esc_url(pvdak_account_tool_url('account-recovery')); ?>">بازیابی یا تغییر رمز عبور با ایمیل</a></p>
    <p><a href="<?php echo esc_url(pvdak_auth_account_url()); ?>">بازگشت به پنل</a></p>
  </div>
</main><?php get_footer(); ?>
