<?php
/**
 * Template Name: اطلاعات عمومی PVDak
 * Template Post Type: page
 */
if(!defined('ABSPATH'))exit;
get_header(); ?>
<main id="main-content" class="container pvdak-static" tabindex="-1">
<nav class="pvdak-account-breadcrumbs" aria-label="مسیر صفحه"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a><span aria-hidden="true"> / </span><span aria-current="page"><?php echo esc_html( get_the_title() ); ?></span></nav>
<?php while(have_posts()):the_post(); ?>
<header class="pvdak-static-head"><span>PVDak</span><h1><?php the_title(); ?></h1></header>
<div class="pvdak-info-layout">
<article <?php post_class('pvdak-static-body pvdak-info-article'); ?>>
<?php if('1'===(string)get_post_meta(get_the_ID(),'_pvdak_provisional_policy',true)): ?>
  <aside class="pvdak-static-note" role="note">متن این صفحه مقدماتی است و قبل از آغاز خدمات باید توسط مدیر سایت بازبینی و تأیید شود.</aside>
<?php endif; ?>
<?php the_content(); ?>
</article>
<aside class="pvdak-info-sidebar" aria-label="دسترسی به صفحه‌های راهنما">
  <span class="pvdak-static-label">دسترسی سریع</span><h2>راهنمای PVDak</h2>
  <nav aria-label="صفحه‌های عمومی PVDak">
  <?php foreach ( array( 'about-us' => 'درباره PVDak', 'legal-faq' => 'پرسش‌های متداول', 'contact-us' => 'تماس با ما', 'privacy-policy' => 'حریم خصوصی', 'terms-of-use' => 'شرایط استفاده' ) as $slug => $label ):
    $page_url = pvdak_public_page_url( $slug );
    if ( ! $page_url ) { continue; } ?>
    <a href="<?php echo esc_url( $page_url ); ?>"<?php if ( is_page( $slug ) ): ?> aria-current="page"<?php endif; ?>><?php echo esc_html( $label ); ?> <span aria-hidden="true">←</span></a>
  <?php endforeach; ?>
  </nav>
  <p>برای ارتباط عمومی از صفحه تماس استفاده کنید. ارسال مدارک محرمانه از این بخش پشتیبانی نمی‌شود.</p>
</aside>
</div>
<?php endwhile; ?>
</main><?php get_footer(); ?>
