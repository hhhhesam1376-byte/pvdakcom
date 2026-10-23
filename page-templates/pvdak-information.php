<?php
/**
 * Template Name: اطلاعات عمومی PVDak
 * Template Post Type: page
 */
if(!defined('ABSPATH'))exit;
get_header(); ?>
<main id="main-content" class="container pvdak-static" tabindex="-1">
<?php while(have_posts()):the_post(); ?>
<header class="pvdak-static-head"><span>پویش وکلای دادگستری کشور</span><h1><?php the_title(); ?></h1></header>
<article <?php post_class('pvdak-static-body'); ?>>
<?php if('1'===(string)get_post_meta(get_the_ID(),'_pvdak_provisional_policy',true)): ?>
  <aside class="pvdak-static-note" role="note">متن این صفحه مقدماتی است و قبل از آغاز خدمات باید توسط مدیر سایت بازبینی و تأیید شود.</aside>
<?php endif; ?>
<?php the_content(); ?>
</article>
<?php endwhile; ?>
</main><?php get_footer(); ?>
