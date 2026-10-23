<?php
/** PVDak homepage v1.01.05 — pixel-guided semantic markup, ACF-powered content. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$hero_desktop = pvdak_home_image_url('hero_image');
$hero_mobile  = pvdak_home_image_url('hero_image_mobile');
$community_img= pvdak_home_image_url('community_image');

/* Native WordPress content: published posts/categories and child FAQ pages. */
$magazine_feature = pvdak_home_selected_post( 'magazine_featured_post' );
$magazine_other   = pvdak_home_category_posts( 'magazine_category', 3, $magazine_feature ? array( $magazine_feature->ID ) : array() );
if ( ! $magazine_feature && $magazine_other ) {
    $magazine_feature = array_shift( $magazine_other );
}
$news_feature = pvdak_home_selected_post( 'news_featured_post' );
$news_other   = pvdak_home_category_posts( 'news_category', 1, $news_feature ? array( $news_feature->ID ) : array() );
if ( ! $news_feature && $news_other ) {
    $news_feature = $news_other[0];
}
$legal_search_term = pvdak_legal_topic_search_term();
$notice_posts = pvdak_home_category_posts( 'notice_category', 3 );
$faq_pages    = pvdak_home_faq_pages();
$magazine_category_id = pvdak_home_selected_category( 'magazine_category' );
$notice_category_id   = pvdak_home_selected_category( 'notice_category' );
$magazine_archive = pvdak_home_value( 'magazine_url', '' );
if ( ! $magazine_archive && $magazine_category_id ) {
    $magazine_archive = get_category_link( $magazine_category_id );
}
$notices_archive = pvdak_home_value( 'news_archive_url', '' );
if ( ! $notices_archive && $notice_category_id ) {
    $notices_archive = get_category_link( $notice_category_id );
}


$svg_icons = array(
    'scales' => '<path d="M12 3v18m-7 0h14M6 7h12M5 7l-3 7h6l-3-7m14 0-3 7h6l-3-7"/>',
    'chat' => '<path d="M4 4h16v13H8l-4 4V4zM7 9h10m-10 4h7"/>',
    'question' => '<circle cx="12" cy="12" r="9"/><path d="M9 9a3 3 0 1 1 4 3c-1 1-1 2-1 3m0 3v.1"/>',
    'document' => '<path d="M6 3h12v18H6zM9 7h6M9 11h6M9 15h4"/>',
    'briefcase' => '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M9 7V4h6v3m-12 7h18"/>'
);
?>
<main id="main-content" class="pvdak-home container" tabindex="-1">
  <section class="pvdak-home-hero" aria-labelledby="hero-title" <?php
      $hero_style = '';
      if($hero_desktop) { $hero_style .= '--pvdak-hero-image:url("'.esc_url_raw($hero_desktop).'");'; }
      if($hero_mobile) { $hero_style .= '--pvdak-hero-mobile-image:url("'.esc_url_raw($hero_mobile).'");'; }
      if($hero_style) { echo 'style="'.esc_attr($hero_style).'"'; }
  ?>>
    <div class="pvdak-home-hero__content">
      <p class="pvdak-home-eyebrow"><?php echo esc_html(pvdak_home_value('hero_label')); ?></p>
      <h1 id="hero-title"><?php echo esc_html(pvdak_home_value('hero_title')); ?></h1>
      <p class="pvdak-home-lead"><?php echo esc_html(pvdak_home_value('hero_description')); ?></p>
      <form class="pvdak-home-search" method="get" action="<?php echo esc_url( home_url( '/' ) . '#topics' ); ?>" role="search" aria-label="جست‌وجوی موضوعات حقوقی">
        <span class="pvdak-home-search-info" aria-hidden="true">?</span>
        <label class="screen-reader-text" for="pvdak-topic-input">موضوع حقوقی</label>
        <input id="pvdak-topic-input" type="search" name="pvdak_topic" value="<?php echo esc_attr( $legal_search_term ); ?>" maxlength="100" placeholder="<?php echo esc_attr( pvdak_home_value( 'hero_search_label' ) ); ?>">
        <button type="submit">جست‌وجو</button>
      </form>
      <p class="pvdak-home-process-hint"><?php echo esc_html(pvdak_home_value('hero_hint')); ?></p>
    </div>
  </section>

  <?php $quick_pages = pvdak_home_quick_pages(); ?>
  <?php if ( $quick_pages ): ?>
  <nav class="pvdak-home-quick-actions" aria-label="چهار دسترسی سریع منتخب">
    <?php
    $quick_symbols = array( 'document', 'question', 'briefcase', 'chat' );
    foreach ( $quick_pages as $index => $quick_page ):
        $symbol = $svg_icons[ $quick_symbols[ $index % 4 ] ];
    ?>
      <a href="<?php echo esc_url( $quick_page['url'] ); ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><?php echo $symbol; ?></svg>
        <span><?php echo esc_html( $quick_page['title'] ); ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <?php elseif ( current_user_can( 'edit_pages' ) ): ?>
  <p class="pvdak-quick-admin-note">برای نمایش چهار دسترسی سریع، در «محتوای صفحه اصلی ← چهار دسترسی سریع زیر بنر» چهار برگه منتشرشده انتخاب کنید.</p>
  <?php endif; ?>

  <section id="services" class="pvdak-home-section pvdak-audiences" aria-labelledby="audiences-title">
    <div class="pvdak-home-section-head">
      <h2 id="audiences-title"><?php echo esc_html(pvdak_home_value('audiences_title')); ?></h2>
      <p><?php echo esc_html(pvdak_home_value('audiences_intro')); ?></p>
    </div>
    <div class="pvdak-home-grid pvdak-home-grid--3">
      <?php $audience_icons=array('scales','chat','question'); for($i=1;$i<=3;$i++):
        $url=pvdak_home_value("audience_{$i}_url",'');
      ?>
      <article class="pvdak-home-tile <?php echo 1===$i?'pvdak-home-tile--featured':''; ?>">
        <span class="pvdak-home-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><?php echo $svg_icons[$audience_icons[$i-1]]; ?></svg></span>
        <h3><?php echo esc_html(pvdak_home_value("audience_{$i}_title")); ?></h3>
        <p><?php echo esc_html(pvdak_home_value("audience_{$i}_description")); ?></p>
        <?php if($url): ?><a class="pvdak-home-text-link" href="<?php echo esc_url($url); ?>">ورود به بخش مربوط ←</a><?php else: ?><span class="pvdak-home-text-link">ورود به بخش مربوط ←</span><?php endif; ?>
      </article>
      <?php endfor; ?>
    </div>
  </section>

  <?php
  $legal_parent_id = pvdak_legal_topics_root_id();
  $all_legal_pages = pvdak_legal_topic_pages( $legal_parent_id );
  $legal_pages = pvdak_legal_filter_topic_pages( $all_legal_pages, $legal_search_term );
  ?>
  <section id="topics" class="pvdak-home-section pvdak-topics-section" aria-labelledby="topics-title">
    <div class="pvdak-home-section-head">
      <h2 id="topics-title"><?php echo esc_html( pvdak_home_value( 'topics_title' ) ); ?></h2>
      <?php if ( $legal_parent_id ): ?>
        <a class="pvdak-section-aside" href="<?php echo esc_url( get_permalink( $legal_parent_id ) ); ?>">همه موضوعات ←</a>
      <?php endif; ?>
    </div>

    <?php if ( $legal_pages ): ?>
    <div class="pvdak-topics-swiper" data-pvdak-swiper role="region" aria-roledescription="اسلایدر" aria-label="موضوعات حقوقی">
      <div class="pvdak-home-topics" data-pvdak-swiper-track>
        <?php foreach ( $legal_pages as $legal_page ): ?>
          <a class="pvdak-home-topic" data-pvdak-topic href="<?php echo esc_url( get_permalink( $legal_page->ID ) ); ?>">
            <?php echo pvdak_legal_topic_svg( pvdak_legal_topic_icon( $legal_page->ID ) ); ?>
            <span><?php echo esc_html( get_the_title( $legal_page->ID ) ); ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="pvdak-topics-controls" aria-label="کنترل اسلایدر موضوعات">
      <button type="button" class="pvdak-topics-control" data-pvdak-topic-prev aria-label="موضوع قبلی">→</button>
      <button type="button" class="pvdak-topics-control" data-pvdak-topic-next aria-label="موضوع بعدی">←</button>
    </div>
    <p id="pvdak-search-empty" class="pvdak-home-search-empty" role="status" hidden>موضوعی با این عبارت پیدا نشد.</p>
    <?php elseif ( '' !== $legal_search_term && $all_legal_pages ): ?>
      <p id="pvdak-search-empty" class="pvdak-home-search-empty" role="status">
        موضوعی مطابق «<?php echo esc_html( $legal_search_term ); ?>» پیدا نشد.
        <a href="<?php echo esc_url( home_url( '/#topics' ) ); ?>">نمایش همه موضوعات</a>
      </p>
    <?php elseif ( current_user_can( 'edit_pages' ) ): ?>
      <div class="pvdak-legal-topics-setup">
        هنوز لندینگ منتشرشده‌ای زیر برگه مادر موضوعات حقوقی وجود ندارد.
        در پیشخوان یک برگه مادر و برگه‌های فرزند آن را بسازید، سپس برگه مادر را در «محتوای صفحه اصلی» انتخاب کنید.
        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>">مدیریت برگه‌ها</a>
      </div>
    <?php endif; ?>
  </section>

  <section class="pvdak-home-process" aria-labelledby="process-title">
    <h2 id="process-title"><?php echo esc_html(pvdak_home_value('process_title')); ?></h2>
    <div class="pvdak-home-grid pvdak-home-grid--4">
      <?php for($i=1;$i<=4;$i++): ?>
      <div class="pvdak-home-step">
        <span aria-hidden="true"><?php echo esc_html($i); ?></span>
        <h3><?php echo esc_html(pvdak_home_value("step_{$i}_title")); ?></h3>
        <p><?php echo esc_html(pvdak_home_value("step_{$i}_description")); ?></p>
      </div>
      <?php endfor; ?>
    </div>
  </section>

  <?php if ( $magazine_feature || current_user_can( 'edit_posts' ) ): ?>
  <section id="magazine" class="pvdak-home-section pvdak-magazine" aria-labelledby="magazine-title">
    <div class="pvdak-home-section-head">
      <h2 id="magazine-title"><?php echo esc_html( pvdak_home_value( 'magazine_title' ) ); ?></h2>
      <?php if ( $magazine_archive ): ?><a class="pvdak-section-aside" href="<?php echo esc_url( $magazine_archive ); ?>">ورود به مجله ←</a><?php endif; ?>
    </div>
    <?php if ( $magazine_feature ): ?>
      <div class="pvdak-magazine-layout">
        <article class="pvdak-magazine-feature <?php echo has_post_thumbnail( $magazine_feature->ID ) ? 'has-image' : ''; ?>">
          <?php if ( has_post_thumbnail( $magazine_feature->ID ) ): ?>
            <?php echo get_the_post_thumbnail( $magazine_feature->ID, 'large', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
          <?php endif; ?>
          <div class="pvdak-magazine-feature-content">
            <h3><?php echo esc_html( get_the_title( $magazine_feature->ID ) ); ?></h3>
            <p><?php echo esc_html( pvdak_home_post_summary( $magazine_feature, 23 ) ); ?></p>
            <a href="<?php echo esc_url( get_permalink( $magazine_feature->ID ) ); ?>">مطالعه مقاله ←</a>
          </div>
        </article>
        <div class="pvdak-magazine-aside">
          <?php foreach ( array_slice( $magazine_other, 0, 2 ) as $index => $magazine_post ): ?>
            <article class="pvdak-magazine-aside-card pvdak-magazine-aside-card--<?php echo esc_attr( $index + 1 ); ?>">
              <span class="pvdak-magazine-aside-symbol" aria-hidden="true"><svg viewBox="0 0 24 24"><?php echo $svg_icons['document']; ?></svg></span>
              <h3><?php echo esc_html( get_the_title( $magazine_post->ID ) ); ?></h3>
              <p><?php echo esc_html( pvdak_home_post_summary( $magazine_post, 14 ) ); ?></p>
              <a href="<?php echo esc_url( get_permalink( $magazine_post->ID ) ); ?>">مطالعه مطلب ←</a>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <p class="pvdak-legal-topics-setup">برای نمایش مجله، در پیشخوان «نوشته‌ها ← دسته‌ها» دسته مجله را بسازید، چند مقاله منتشر کنید و در «محتوای صفحه اصلی» آن دسته را انتخاب کنید.</p>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ( $notice_posts || $news_feature || current_user_can( 'edit_posts' ) ): ?>
  <section id="news" class="pvdak-home-section pvdak-news" aria-labelledby="news-title">
    <div class="pvdak-home-section-head">
      <h2 id="news-title"><?php echo esc_html( pvdak_home_value( 'news_title' ) ); ?></h2>
      <?php if ( $notices_archive ): ?><a class="pvdak-section-aside" href="<?php echo esc_url( $notices_archive ); ?>"><?php echo esc_html( pvdak_home_value( 'news_archive_label' ) ); ?> ←</a><?php endif; ?>
    </div>
    <div class="pvdak-news-grid">
      <article class="pvdak-notices-panel">
        <div class="pvdak-notices-header">
          <h3><?php echo esc_html( pvdak_home_value( 'notice_heading' ) ); ?></h3>
          <span><?php echo esc_html( pvdak_home_value( 'notice_badge' ) ); ?></span>
        </div>
        <div class="pvdak-notices-list">
          <?php foreach ( $notice_posts as $notice_post ): ?>
            <div class="pvdak-notice">
              <span aria-hidden="true"><svg viewBox="0 0 24 24"><?php echo $svg_icons['document']; ?></svg></span>
              <div><a href="<?php echo esc_url( get_permalink( $notice_post->ID ) ); ?>"><?php echo esc_html( get_the_title( $notice_post->ID ) ); ?></a>
              <p><?php echo esc_html( pvdak_home_post_summary( $notice_post, 13 ) ); ?></p></div>
            </div>
          <?php endforeach; ?>
          <?php if ( ! $notice_posts && current_user_can( 'edit_posts' ) ): ?>
            <p>برای این بخش دسته «اطلاعیه‌ها» را در ویرایشگر صفحه اصلی انتخاب کنید و در آن نوشته منتشر کنید.</p>
          <?php endif; ?>
        </div>
      </article>
      <?php if ( $news_feature ): ?>
        <article class="pvdak-news-article">
          <div class="pvdak-news-article-picture">
            <?php if ( has_post_thumbnail( $news_feature->ID ) ): ?>
                <?php echo get_the_post_thumbnail( $news_feature->ID, 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
            <?php endif; ?>
          </div>
          <div class="pvdak-news-article-content">
            <h3><?php echo esc_html( get_the_title( $news_feature->ID ) ); ?></h3>
            <p><?php echo esc_html( pvdak_home_post_summary( $news_feature, 20 ) ); ?></p>
            <a href="<?php echo esc_url( get_permalink( $news_feature->ID ) ); ?>">مطالعه در مجله ←</a>
          </div>
        </article>
      <?php endif; ?>
      <article id="forum" class="pvdak-forum-card">
        <span class="pvdak-forum-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><?php echo $svg_icons['chat']; ?></svg></span>
        <h3><?php echo esc_html( pvdak_home_value( 'forum_feature_title' ) ); ?><br><?php echo esc_html( pvdak_home_value( 'forum_feature_subtitle' ) ); ?></h3>
        <p><?php echo esc_html( pvdak_home_value( 'forum_feature_description' ) ); ?></p>
        <?php $forum_url = pvdak_home_value( 'forum_url', '' ); ?>
        <?php if ( $forum_url ): ?><a class="pvdak-forum-button" href="<?php echo esc_url( $forum_url ); ?>"><?php else: ?><span class="pvdak-forum-button"><?php endif; ?>
          <?php echo esc_html( pvdak_home_value( 'forum_feature_button' ) ); ?>
        <?php if ( $forum_url ): ?></a><?php else: ?></span><?php endif; ?>
      </article>
    </div>
  </section>
  <?php endif; ?>

  <section id="community" class="pvdak-home-community" aria-labelledby="community-title">
    <div class="pvdak-home-community-content">
      <h2 id="community-title"><?php echo esc_html(pvdak_home_value('community_title')); ?></h2>
      <p><?php echo esc_html(pvdak_home_value('community_description')); ?></p>
      <p><?php echo esc_html(pvdak_home_value('community_note')); ?></p>
      <?php $u=pvdak_home_join_url(); if($u): ?><a class="pvdak-home-button" href="<?php echo esc_url($u); ?>"><?php else: ?><span class="pvdak-home-button"><?php endif; ?><?php echo esc_html(pvdak_home_value('community_button')); ?> ←<?php if($u): ?></a><?php else: ?></span><?php endif; ?>
    </div>
    <?php if($community_img): ?><img src="<?php echo esc_url($community_img); ?>" alt="" loading="lazy" decoding="async"><?php else: ?><div class="pvdak-community-placeholder" aria-hidden="true"></div><?php endif; ?>
  </section>

  <?php if ( $faq_pages || current_user_can( 'edit_pages' ) ): ?>
  <section class="pvdak-home-section pvdak-home-faq" aria-labelledby="faq-title">
    <div class="pvdak-home-section-head"><h2 id="faq-title"><?php echo esc_html( pvdak_home_value( 'faq_title' ) ); ?></h2></div>
    <?php if ( $faq_pages ): ?>
      <?php foreach ( $faq_pages as $faq_page ): ?>
        <details>
          <summary><?php echo esc_html( get_the_title( $faq_page->ID ) ); ?></summary>
          <div><?php echo wp_kses_post( apply_filters( 'the_content', $faq_page->post_content ) ); ?></div>
        </details>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="pvdak-legal-topics-setup">یک برگه مادر «پرسش‌های متداول» و برگه‌های فرزند آن را بسازید؛ سپس در «محتوای صفحه اصلی» برگه مادر را انتخاب کنید.</p>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</main>
<?php get_footer(); ?>
