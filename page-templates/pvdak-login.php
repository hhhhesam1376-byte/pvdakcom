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
<span class="pvdak-static-label">حساب کاربری پویش</span><h1>ورود به پنل</h1>
<p>با نام کاربری یا ایمیل و رمز عبور حساب خود وارد شوید.</p>
<p class="pvdak-login-trust-note">ورود امن با حساب وردپرس؛ بدون نیاز به شماره موبایل.</p>
<?php if($admin_preview): ?><p class="pvdak-static-note">حالت پیش‌نمایش مدیر فعال است. برای آزمایش ورود واقعی از حساب غیرمدیر در محیط آزمایشی استفاده کنید.</p><?php endif; ?>
<?php if(isset($_GET['error'])): ?>
<p id="pvdak-login-result" tabindex="-1" class="pvdak-static-error" role="alert"><?php echo isset($_GET['error']) && is_string($_GET['error']) && 'slow' === sanitize_key(wp_unslash($_GET['error'])) ? 'تلاش‌های ورود بیش از حد بوده است. چند دقیقه بعد دوباره امتحان کنید.' : 'اطلاعات ورود تأیید نشد. نام کاربری و رمز عبور را بررسی کنید.'; ?></p>
<?php endif; ?>
<form method="post" class="pvdak-forms" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
<input type="hidden" name="action" value="pvdak_login"><?php wp_nonce_field('pvdak_login','_pvdak_login_nonce'); ?>
<label for="pvdak-identity">نام کاربری یا ایمیل</label><input id="pvdak-identity" name="login_identity" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="250" required>
<label for="pvdak-password">رمز عبور</label><div class="pvdak-password-field"><input id="pvdak-password" type="password" name="login_password" autocomplete="current-password" required><button type="button" class="pvdak-password-toggle" data-pvdak-password-toggle="pvdak-password" aria-controls="pvdak-password" aria-pressed="false">نمایش</button></div>
<label class="pvdak-check"><input type="checkbox" name="login_remember"><span>مرا به خاطر بسپار</span></label>
<button type="submit">ورود به حساب ←</button></form>
<?php if(isset($_GET['status']) && 'reset' === (string) $_GET['status']):?><p class="pvdak-static-note" role="status">رمز عبور تغییر کرد؛ با اطلاعات جدید وارد شوید.</p><?php endif; ?>
<p class="pvdak-login-help" aria-label="راه‌های دسترسی به حساب"><a href="<?php echo esc_url(pvdak_account_tool_url('account-recovery')); ?>">رمز عبور را فراموش کرده‌ام</a> · ورود پیامکی فعلاً فعال نیست. <a href="<?php echo esc_url(pvdak_account_tool_url('account-register')); ?>">وضعیت ثبت‌نام</a>. برای مشکل دسترسی از <a href="<?php echo esc_url(pvdak_public_page_url('contact-us')?:home_url('/contact-us/')); ?>">تماس با ما</a> استفاده کنید.</p>
</section>
<aside class="pvdak-login-art"><span aria-hidden="true">PV</span><h2>مسیر روشن پیگیری</h2><p>پروفایل شخصی، سوابق درخواست و گفت‌وگوهای پشتیبانی در یک فضای منسجم.</p><ul class="pvdak-login-benefits"><li>دسترسی به اطلاعات حساب</li><li>پیگیری پیام‌های خصوصی</li><li>مدیریت وضعیت همکاری</li></ul></aside>
</div></main><?php get_footer(); ?>
