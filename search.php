<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
global $wp_query;
$term = get_search_query( false );
$count = $wp_query instanceof WP_Query ? (int) $wp_query->found_posts : 0;
get_header(); ?>
<main id="main-content" class="site-main container pvdak-discovery-page pvdak-search-page" tabindex="-1">
  <nav class="pvdak-account-breadcrumbs" aria-label="مسیر صفحه">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">صفحه اصلی</a><span aria-hidden="true">›</span><span aria-current="page">جست‌وجو</span>
  </nav>
  <header class="pvdak-discovery-hero">
    <span class="pvdak-discovery-eyebrow">جست‌وجو در PVDak</span>
    <h1>نتایج جست‌وجو</h1>
    <?php if ( '' !== trim( $term ) ): ?>
      <p>برای عبارت <strong>«<?php echo esc_html( $term ); ?>»</strong>
        <?php echo esc_html( number_format_i18n( $count ) ); ?> نتیجه پیدا شد.</p>
    <?php else: ?>
      <p>موضوع حقوقی یا عبارت موردنظرتان را بنویسید و میان مطالب منتشرشده جست‌وجو کنید.</p>
    <?php endif; ?>
    <div class="pvdak-discovery-search"><?php get_search_form(); ?></div>
  </header>
  <?php if ( have_posts() ): ?>
    <div class="pvdak-discovery-section-heading">
      <h2>مطالب پیدا شده</h2><span>نتایج از محتوای منتشرشده سایت</span>
    </div>
  <?php endif; ?>
  <?php get_template_part( 'template-parts/loop' ); ?>
</main>
<?php get_footer(); ?>
