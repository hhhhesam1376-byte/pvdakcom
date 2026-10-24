<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$recommended = get_categories( array( 'hide_empty' => true, 'number' => 4 ) );
?>
<section class="no-results pvdak-empty-state" aria-labelledby="pvdak-empty-title">
  <div class="pvdak-empty-icon" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3"><circle cx="28" cy="27" r="17"/><path d="m41 40 15 15M20 27h16"/></svg></div>
  <span class="pvdak-discovery-eyebrow">نتیجه‌ای پیدا نشد</span>
  <h2 id="pvdak-empty-title">مطلبی با این مشخصات پیدا نکردیم</h2>
  <p>عبارت جست‌وجو را کوتاه‌تر کنید یا از موضوعات منتشرشده مجله شروع کنید.</p>
  <div class="pvdak-empty-search"><?php get_search_form(); ?></div>
  <?php if ( $recommended ): ?>
    <nav class="pvdak-empty-categories" aria-label="موضوعات پیشنهادی از مطالب منتشرشده">
      <?php foreach ( $recommended as $category ): ?>
        <a href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>
  <a class="pvdak-empty-home" href="<?php echo esc_url( home_url( '/' ) ); ?>">بازگشت به صفحه اصلی ←</a>
</section>
