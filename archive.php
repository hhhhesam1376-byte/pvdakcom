<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$categories = get_categories( array( 'hide_empty' => true, 'number' => 7 ) );
get_header(); ?>
<main id="main-content" class="site-main container pvdak-discovery-page pvdak-archive-page" tabindex="-1">
  <nav class="pvdak-account-breadcrumbs" aria-label="مسیر صفحه">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">صفحه اصلی</a><span aria-hidden="true">›</span><span aria-current="page">آرشیو مطالب</span>
  </nav>
  <header class="pvdak-discovery-hero pvdak-archive-hero">
    <span class="pvdak-discovery-eyebrow">مجله و دانشنامه حقوقی</span>
    <?php the_archive_title( '<h1>', '</h1>' ); ?>
    <?php the_archive_description( '<div class="pvdak-archive-description">', '</div>' ); ?>
    <p>مطالب منتشرشده این بخش را مرور کنید و با انتخاب دسته‌بندی به موضوع موردنیازتان برسید.</p>
  </header>
  <?php if ( $categories ): ?>
    <nav class="pvdak-discovery-categories" aria-label="دسته‌بندی‌های منتشرشده مجله">
      <span>موضوعات مجله</span>
      <?php foreach ( $categories as $category ): ?>
        <a href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>"<?php if ( is_category( $category->term_id ) ): ?> aria-current="page"<?php endif; ?>><?php echo esc_html( $category->name ); ?></a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>
  <div class="pvdak-discovery-section-heading"><h2>نوشته‌های این بخش</h2><span>مرتب‌شده مطابق تنظیمات وردپرس</span></div>
  <?php get_template_part( 'template-parts/loop' ); ?>
</main>
<?php get_footer(); ?>
