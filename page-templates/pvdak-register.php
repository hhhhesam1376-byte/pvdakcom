<?php
/**
 * Template Name: ثبت‌نام PVDak
 * Template Post Type: page
 */
if(!defined('ABSPATH'))exit;
get_header();
$state=isset($_GET['state'])&&is_string($_GET['state'])?sanitize_key(wp_unslash($_GET['state'])):'';
?>
<main id="main-content" class="container pvdak-static" tabindex="-1">
<header class="pvdak-static-head"><span>حساب تازه</span><h1>ثبت‌نام در پویش</h1><p>ایجاد حساب پس از فعال‌سازی رسمی ثبت‌نام امکان‌پذیر می‌شود.</p></header>
<div class="pvdak-static-body pvdak-recovery-panel">
<?php if(!pvdak_registration_open()): ?>
  <div class="pvdak-static-note" role="status">ثبت‌نام در حال حاضر غیرفعال است. تا اعلام راه‌اندازی رسمی، هیچ حساب جدیدی از این فرم ساخته نمی‌شود.</div>
<?php else: ?>
<?php if('received'===$state):?><p class="pvdak-static-note" role="status">درخواست ثبت‌نام دریافت شد. نوع همکاری انتخابی شما هنوز بررسی یا تأیید نشده است.</p><?php elseif($state):?><p class="pvdak-static-error" role="alert">اطلاعات را بررسی کنید؛ ممکن است نام کاربری/ایمیل تکراری باشد یا محدودیت ارسال فعال باشد.</p><?php endif; ?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="pvdak-forms">
  <input type="hidden" name="action" value="pvdak_register"><?php wp_nonce_field('pvdak_register','_pvdak_register_nonce'); ?>
  <div class="pvdak-honeypot" aria-hidden="true"><input name="register_site" tabindex="-1" autocomplete="off"></div>
  <label for="register-user">نام کاربری</label><input id="register-user" name="register_username" minlength="3" maxlength="50" autocomplete="username" required>
  <label for="register-email">ایمیل</label><input id="register-email" type="email" name="register_email" autocomplete="email" required>
  <label for="register-password">رمز عبور (حداقل ۱۲ نویسه)</label><input id="register-password" type="password" name="register_password" minlength="12" autocomplete="new-password" required>
  <label for="register-role">نوع استفاده موردنظر</label><select id="register-role" name="register_role" required>
    <option value="client">مراجعه‌کننده</option><option value="lawyer">متقاضی همکاری به‌عنوان وکیل</option>
    <option value="trainee">متقاضی همکاری کارآموز</option><option value="student">دانشجوی حقوق</option>
  </select>
  <p class="pvdak-static-note">انتخاب عنوان وکیل یا کارآموز صرفاً اظهار علاقه است؛ تأیید رسمی و دسترسی پرونده‌ها بعداً توسط مدیر بررسی می‌شود.</p>
  <label class="pvdak-check"><input type="checkbox" name="register_consent" required><span>قوانین و حریم خصوصی اولیه را خوانده‌ام و با ساخت حساب موافقم.</span></label>
  <button type="submit">ایجاد حساب</button>
</form><?php endif; ?>
<p><a href="<?php echo esc_url(pvdak_auth_login_url()); ?>">قبلاً حساب داشته‌ام</a></p>
</div></main><?php get_footer(); ?>
