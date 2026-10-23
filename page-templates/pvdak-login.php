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
<div class="pvdak-login-grid">
<section class="pvdak-static-body">
<span class="pvdak-static-label">حساب کاربری پویش</span><h1>ورود به پنل</h1>
<p>برای ورود از اطلاعات حساب موجود استفاده کنید.</p>
<?php if($admin_preview): ?><p class="pvdak-static-note">حالت پیش‌نمایش مدیر فعال است. برای آزمایش ورود واقعی از حساب غیرمدیر در محیط آزمایشی استفاده کنید.</p><?php endif; ?>
<?php if(isset($_GET['error'])): ?><p class="pvdak-static-error" role="alert">نام کاربری یا رمز عبور درست نیست.</p><?php endif; ?>
<form method="post" class="pvdak-forms" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
<input type="hidden" name="action" value="pvdak_login"><?php wp_nonce_field('pvdak_login','_pvdak_login_nonce'); ?>
<label for="pvdak-identity">نام کاربری یا ایمیل</label><input id="pvdak-identity" name="login_identity" autocomplete="username" required>
<label for="pvdak-password">رمز عبور</label><input id="pvdak-password" type="password" name="login_password" autocomplete="current-password" required>
<label class="pvdak-check"><input type="checkbox" name="login_remember"><span>مرا به خاطر بسپار</span></label>
<button type="submit">ورود به حساب ←</button></form>
<?php if(isset($_GET['status']) && 'reset' === (string) $_GET['status']):?><p class="pvdak-static-note" role="status">رمز عبور تغییر کرد؛ با اطلاعات جدید وارد شوید.</p><?php endif; ?>
<p class="pvdak-login-help"><a href="<?php echo esc_url(pvdak_account_tool_url('account-recovery')); ?>">رمز عبور را فراموش کرده‌ام</a> · ثبت‌نام و ورود پیامکی فعلاً فعال نیست. برای مشکل دسترسی از <a href="<?php echo esc_url(pvdak_public_page_url('contact-us')?:home_url('/contact-us/')); ?>">تماس با ما</a> استفاده کنید.</p>
</section>
<aside class="pvdak-login-art"><span>PV</span><h2>مسیر روشن پیگیری</h2><p>محیط اختصاصی و فارسی برای مشاهده امکانات فعال حساب کاربری پویش.</p></aside>
</div></main><?php get_footer(); ?>
