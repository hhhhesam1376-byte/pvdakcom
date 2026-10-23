<?php
/**
 * Template Name: تماس با PVDak
 * Template Post Type: page
 */
if(!defined('ABSPATH'))exit;
get_header();
$sent=isset($_GET['sent'])?sanitize_key(wp_unslash($_GET['sent'])):'';
$messages=array('1'=>'پیام شما برای تیم پویش ارسال شد.','invalid'=>'اطلاعات فرم را بررسی کنید.','slow'=>'کمی بعد دوباره امتحان کنید.','failed'=>'ارسال پیام ممکن نشد.');
?>
<main id="main-content" class="container pvdak-static pvdak-contact-page" tabindex="-1">
<nav class="pvdak-account-breadcrumbs" aria-label="مسیر صفحه"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a><span aria-hidden="true"> / </span><span aria-current="page">تماس با ما</span></nav>
<?php while(have_posts()):the_post(); ?>
<header class="pvdak-static-head"><span>در ارتباط باشید</span><h1><?php the_title(); ?></h1></header>
<div class="pvdak-contact-grid">
<article <?php post_class('pvdak-static-body'); ?>><h2>ارتباط با پویش</h2><?php the_content(); ?>
<p class="pvdak-static-note">این فرم برای ارسال مدارک و اطلاعات محرمانه پرونده‌ها نیست.</p></article>
<section class="pvdak-static-body" aria-label="فرم ارتباط عمومی"><h2>ارسال پیام</h2>
<?php if(isset($messages[$sent])): ?><p class="<?php echo '1' === $sent ? 'pvdak-static-note' : 'pvdak-static-error'; ?>" role="<?php echo '1' === $sent ? 'status' : 'alert'; ?>"><?php echo esc_html($messages[$sent]); ?></p><?php endif; ?>
<?php if(pvdak_contact_allowed()): ?>
<?php if(function_exists('pvdak_maintenance_enabled')&&pvdak_maintenance_enabled()): ?><p class="pvdak-static-note">این فرم فعلاً تنها برای آزمایش مدیر سایت فعال است.</p><?php endif; ?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="pvdak-forms">
<input type="hidden" name="action" value="pvdak_contact"><?php wp_nonce_field('pvdak_contact_form','_pvdak_contact_nonce'); ?>
<div class="pvdak-honeypot" aria-hidden="true"><label>وب‌سایت<input name="contact_website" tabindex="-1" autocomplete="off"></label></div>
<label for="pvdak-name">نام</label><input id="pvdak-name" name="contact_name" maxlength="100" required>
<label for="pvdak-email">ایمیل</label><input id="pvdak-email" type="email" name="contact_email" required>
<label for="pvdak-message">پیام</label><textarea id="pvdak-message" name="contact_message" minlength="10" maxlength="3500" rows="5" required></textarea>
<label class="pvdak-check"><input type="checkbox" name="contact_consent" required><span>می‌پذیرم پیام من برای پاسخ‌گویی ارسال شود و اطلاعات محرمانه در آن نیست. <?php $privacy = pvdak_public_page_url( 'privacy-policy' ); if ( $privacy ): ?><a href="<?php echo esc_url( $privacy ); ?>">حریم خصوصی</a><?php endif; ?></span></label>
<button type="submit">ارسال پیام ←</button></form>
<?php else: ?><p>فرم تماس تا پایان آماده‌سازی سایت غیرفعال است.</p><?php endif; ?>
</section></div>
<?php endwhile; ?>
</main><?php get_footer(); ?>
