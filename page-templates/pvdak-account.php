<?php
/**
 * Template Name: پنل اختصاصی PVDak
 * Template Post Type: page
 */
if(!defined('ABSPATH'))exit;
if(!is_user_logged_in()){wp_safe_redirect(pvdak_auth_login_url());exit;}
$user=wp_get_current_user();
get_header(); ?>
<main id="main-content" class="container pvdak-static" tabindex="-1">
<header class="pvdak-static-head"><span>حساب کاربری</span><h1>پنل پویش</h1><p>خوش آمدید، <?php echo esc_html($user->display_name); ?></p></header>
<nav class="pvdak-account-shortcuts" aria-label="دسترسی‌های حساب">
  <a href="<?php echo esc_url(pvdak_account_tool_url('account-profile')); ?>">ویرایش پروفایل</a>
  <a href="<?php echo esc_url(pvdak_account_tool_url('account-recovery')); ?>">بازیابی رمز</a>
  <?php if(shortcode_exists('pvdak_support_tickets')): ?><a href="#pvdak-support">پشتیبانی</a><?php endif; ?>
</nav>
<div class="pvdak-static-body"><?php if(shortcode_exists('pvdak_account_dashboard')): ?>
<?php echo do_shortcode('[pvdak_account_dashboard]'); ?>
<?php else: ?><p>ورود شما موفق بوده است؛ اما افزونه PVDak Core در حال حاضر داشبورد درخواست‌ها را فراهم نمی‌کند.</p><p class="pvdak-static-note">امکان ثبت درخواست حقوقی تا زمان راه‌اندازی رسمی غیرفعال است.</p><?php endif; ?></div>
<?php if(shortcode_exists('pvdak_professional_dashboard') && function_exists('pvdak_core_existing_application') && pvdak_core_existing_application(get_current_user_id())): ?>
  <div class="pvdak-account-more"><?php echo do_shortcode('[pvdak_professional_dashboard]'); ?></div>
<?php endif; ?>
<div class="pvdak-account-more"><?php echo do_shortcode('[pvdak_support_tickets]'); ?></div>
<p><a href="<?php echo esc_url(wp_logout_url(pvdak_auth_login_url())); ?>">خروج از حساب</a></p>
</main><?php get_footer(); ?>
