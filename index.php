<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$categories = get_categories( array( 'hide_empty' => true, 'number' => 7 ) );
get_header(); ?>
<main id="main-content" class="site-main container pvdak-discovery-page pvdak-journal-page" tabindex="-1">
  <nav class="pvdak-account-breadcrumbs" aria-label="مسیر صفحه">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">صفحه اصلی</a><span aria-hidden="true">›</span><span aria-current="page">مجله حقوقی</span>
  </nav>
  <header class="pvdak-discovery-hero">
    <span class="pvdak-discovery-eyebrow">دانشنامه و مطالب پویش</span>
    <h1>مجله حقوقی پویش</h1>
    <p>آخرین مطالب منتشرشده را بخوانید یا از دسته‌بندی‌های موجود موضوع دلخواهتان را پیدا کنید.</p>
    <div class="pvdak-discovery-search"><?php get_search_form(); ?></div>
  </header>
  <?php if ( $categories ): ?>
    <nav class="pvdak-discovery-categories" aria-label="موضوعات مطالب منتشرشده">
      <span>دسته‌بندی‌ها</span>
      <?php foreach ( $categories as $category ): ?>
        <a href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>
  <div class="pvdak-discovery-section-heading"><h2>آخرین نوشته‌ها</h2><span>مطالب منتشرشده توسط تحریریه</span></div>
  <?php get_template_part( 'template-parts/loop' ); ?>
</main>
<?php get_footer(); ?>
