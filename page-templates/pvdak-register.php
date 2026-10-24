<?php
/**
 * Template Name: ثبت‌نام PVDak
 * Template Post Type: page
 */
if(!defined('ABSPATH'))exit;
get_header();
$state=isset($_GET['state'])&&is_string($_GET['state'])?sanitize_key(wp_unslash($_GET['state'])):'';
?>
<main id="main-content" class="container pvdak-static pvdak-register-page" tabindex="-1">
<nav class="pvdak-account-breadcrumbs" aria-label="مسیر صفحه"><a href="<?php echo esc_url( pvdak_auth_login_url() ); ?>">ورود به حساب</a><span aria-hidden="true"> / </span><span aria-current="page">ثبت‌نام</span></nav>
<header class="pvdak-static-head"><span>حساب تازه</span><h1>ثبت‌نام در پویش</h1><p>ایجاد حساب پس از فعال‌سازی رسمی ثبت‌نام امکان‌پذیر می‌شود.</p></header>
<div class="pvdak-static-body pvdak-recovery-panel pvdak-register-surface">
<?php if(!pvdak_registration_open()): ?>
  <div class="pvdak-register-closed" role="status">
    <span class="pvdak-register-closed-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
    <div><strong>ثبت‌نام هنوز آغاز نشده است</strong><p>این امکان پس از راه‌اندازی رسمی فعال می‌شود؛ در حال حاضر هیچ حسابی از این صفحه ایجاد نخواهد شد.</p></div>
  </div>
  <h2 class="pvdak-register-guide-heading">مسیر عضویت در پویش</h2>
  <ol class="pvdak-register-steps" aria-label="مراحل عضویت پس از فعال‌سازی">
    <li><strong>ایجاد حساب</strong><small>انتخاب اطلاعات ورود و نوع عضویت پس از فعال‌سازی</small></li>
    <li><strong>تأیید ایمیل</strong><small>تأیید مالکیت ایمیل قبل از دسترسی به پنل</small></li>
    <li><strong>بررسی همکاری</strong><small>بررسی جداگانه مدارک و صلاحیت اعضای حرفه‌ای توسط مدیر</small></li>
  </ol>
  <div class="pvdak-register-actions">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">بازگشت به صفحه اصلی</a>
    <?php $about_url = pvdak_public_page_url( 'about-us' ); if ( $about_url ): ?><a href="<?php echo esc_url( $about_url ); ?>">آشنایی با پویش ←</a><?php endif; ?>
  </div>
<?php else: ?>
<?php if('received'===$state):?><p class="pvdak-static-note" role="status">حساب ایجاد شد. برای ورود باید ایمیل حساب را از طریق پیوند ارسال‌شده تأیید کنید؛ نوع همکاری اعلامی هنوز تأیید نشده است.</p>
<?php elseif('verified'===$state):?><p class="pvdak-static-note" role="status">ایمیل تأیید شد. اکنون می‌توانید وارد حساب شوید.</p>
<?php elseif('resent'===$state):?><p class="pvdak-static-note" role="status">اگر ایمیل متعلق به حساب تأییدنشده باشد، پیوند تازه ارسال خواهد شد.</p>
<?php elseif('invalid_token'===$state):?><p class="pvdak-static-error" role="alert">پیوند تأیید معتبر نیست یا منقضی شده است. پیوند تازه درخواست کنید.</p>
<?php elseif($state):?><p class="pvdak-static-error" role="alert">اطلاعات ثبت‌نام را بررسی کنید یا بعداً دوباره تلاش کنید.</p><?php endif; ?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="pvdak-forms">
  <input type="hidden" name="action" value="pvdak_register"><?php wp_nonce_field('pvdak_register','_pvdak_register_nonce'); ?>
  <div class="pvdak-honeypot" aria-hidden="true"><input name="register_site" tabindex="-1" autocomplete="off"></div>
  <label for="register-user">نام کاربری</label><input id="register-user" name="register_username" minlength="3" maxlength="50" autocomplete="username" required>
  <label for="register-email">ایمیل</label><input id="register-email" type="email" name="register_email" autocomplete="email" required>
  <label for="register-password">رمز عبور (حداقل ۱۲ نویسه)</label><div class="pvdak-password-field"><input id="register-password" type="password" name="register_password" minlength="12" autocomplete="new-password" required><button type="button" class="pvdak-password-toggle" data-pvdak-password-toggle="register-password" aria-controls="register-password" aria-pressed="false">نمایش</button></div>
  <label for="register-role">نوع استفاده موردنظر</label><select id="register-role" name="register_role" required>
    <option value="client">مراجعه‌کننده</option><option value="lawyer">متقاضی همکاری به‌عنوان وکیل</option>
    <option value="trainee">متقاضی همکاری کارآموز</option><option value="student">دانشجوی حقوق</option>
  </select>
  <p class="pvdak-static-note">انتخاب عنوان وکیل یا کارآموز صرفاً اظهار علاقه است؛ تأیید رسمی و دسترسی پرونده‌ها بعداً توسط مدیر بررسی می‌شود.</p>
  <label class="pvdak-check"><input type="checkbox" name="register_consent" required><span>با ساخت حساب و شرایط اعلام‌شده موافقم. <?php
    $terms_url = pvdak_public_page_url( 'terms-of-use' );
    $privacy_url = pvdak_public_page_url( 'privacy-policy' );
    if ( $terms_url ): ?><a href="<?php echo esc_url( $terms_url ); ?>">شرایط استفاده</a><?php endif; ?>
    <?php if ( $privacy_url ): ?><a href="<?php echo esc_url( $privacy_url ); ?>">حریم خصوصی</a><?php endif; ?></span></label>
  <button type="submit">ایجاد حساب</button>
</form>
<hr>
<h2>دریافت دوباره پیوند تأیید ایمیل</h2>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="pvdak-forms">
<input type="hidden" name="action" value="pvdak_verify_resend"><?php wp_nonce_field('pvdak_verify_resend','_pvdak_verify_nonce'); ?>
<label for="pvdak-resend-email">ایمیل ثبت‌نام</label>
<input id="pvdak-resend-email" name="verify_email" type="email" autocomplete="email" required>
<button type="submit">ارسال مجدد پیوند تأیید</button>
</form>
<?php endif; ?>
<p><a href="<?php echo esc_url(pvdak_auth_login_url()); ?>">قبلاً حساب داشته‌ام</a></p>
</div></main><?php get_footer(); ?>
