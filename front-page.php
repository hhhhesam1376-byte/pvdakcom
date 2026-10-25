<?php
/** PVDak homepage v1.01.05 — pixel-guided semantic markup, ACF-powered content. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$hero_desktop = pvdak_home_image_url('hero_image');
$hero_mobile  = pvdak_home_image_url('hero_image_mobile');
$community_img= pvdak_home_image_url('community_image');

/* Native WordPress content: published posts/categories and child FAQ pages. */
$magazine_feature = pvdak_home_selected_post( 'magazine_featured_post' );
$magazine_other   = pvdak_home_category_posts( 'magazine_category', 5, $magazine_feature ? array( $magazine_feature->ID ) : array() );
if ( ! $magazine_feature && $magazine_other ) {
    $magazine_feature = array_shift( $magazine_other );
}
$news_feature = pvdak_home_selected_post( 'news_featured_post' );
$news_other   = pvdak_home_category_posts( 'news_category', 1, $news_feature ? array( $news_feature->ID ) : array() );
if ( ! $news_feature && $news_other ) {
    $news_feature = $news_other[0];
}
$legal_search_term = pvdak_legal_topic_search_term();
$legal_parent_id = pvdak_legal_topics_root_id();
$all_legal_pages = pvdak_legal_topic_pages( $legal_parent_id );
$legal_pages = pvdak_legal_filter_topic_pages( $all_legal_pages, $legal_search_term );
$notice_posts = pvdak_home_category_posts( 'notice_category', 3 );
$faq_pages    = pvdak_home_faq_pages();
$faq_archive  = pvdak_public_page_url( 'legal-faq' );
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
      <form class="pvdak-home-search" method="get" action="<?php echo esc_url( $all_legal_pages ? home_url( '/#topics' ) : home_url( '/' ) ); ?>" role="search" aria-label="<?php echo esc_attr( $all_legal_pages ? 'جست‌وجوی موضوعات حقوقی' : 'جست‌وجوی مطالب حقوقی سایت' ); ?>">
        <span class="pvdak-home-search-info" aria-hidden="true">?</span>
        <label class="screen-reader-text" for="pvdak-topic-input"><?php echo $all_legal_pages ? 'موضوع حقوقی' : 'جست‌وجوی مطالب سایت'; ?></label>
        <input id="pvdak-topic-input" type="search" name="<?php echo $all_legal_pages ? 'pvdak_topic' : 's'; ?>" value="<?php echo esc_attr( $all_legal_pages ? $legal_search_term : get_search_query() ); ?>" maxlength="100" placeholder="<?php echo esc_attr( $all_legal_pages ? pvdak_home_value( 'hero_search_label' ) : 'جست‌وجو در مطالب حقوقی سایت…' ); ?>" required>
        <?php if ( $all_legal_pages ): ?><button type="button" class="pvdak-home-clear-search" data-pvdak-clear-search hidden aria-label="پاک کردن جست‌وجوی موضوع">پاک کردن ×</button><?php endif; ?>
        <button type="submit"><?php echo $all_legal_pages ? 'جست‌وجوی موضوع' : 'جست‌وجوی مطالب'; ?></button>
      </form>
      <p class="pvdak-home-process-hint"><span aria-hidden="true">✓</span> <?php echo esc_html(pvdak_home_value('hero_hint')); ?></p>
      <div class="pvdak-home-hero-steps" aria-label="دسترسی‌های اصلی">
        <?php if ( $all_legal_pages ): ?><a href="#topics">موضوعات حقوقی <span aria-hidden="true">←</span></a><?php else: ?><a href="<?php echo esc_url( add_query_arg( 's', 'حقوق', home_url( '/' ) ) ); ?>">جست‌وجوی مطالب حقوقی <span aria-hidden="true">←</span></a><?php endif; ?>
        <?php if ( $magazine_feature ): ?><a href="#magazine">مطالعه مطالب <span aria-hidden="true">←</span></a><?php endif; ?>
      </div>
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
      <a href="<?php echo esc_url( $quick_page['url'] ); ?>" aria-label="<?php echo esc_attr( $quick_page['title'] ); ?>">
        <span class="pvdak-home-quick-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><?php echo $symbol; ?></svg></span>
        <span class="pvdak-home-quick-text"><?php echo esc_html( $quick_page['title'] ); ?></span><span class="pvdak-home-quick-arrow" aria-hidden="true">←</span>
      </a>
    <?php endforeach; ?>
  </nav>
  <?php endif; ?>

  <?php
  // Three real destinations, not a required choice of role or a disabled intake form.
  $pvdak_getting_started = array(
      array(
          'title' => pvdak_home_start_copy( 'audience_1_title', 'موضوع حقوقی‌ام را پیدا کنم' ),
          'desc' => pvdak_home_start_copy( 'audience_1_description', 'از میان موضوعات حقوقی، حوزه موردنظر را پیدا کنید.' ),
          'cta' => 'مشاهده موضوعات',
          'url' => $all_legal_pages && $legal_parent_id ?
              get_permalink( $legal_parent_id ) : add_query_arg( 's', 'حقوق', home_url( '/' ) ),
          'icon' => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="21" cy="21" r="12"/><path d="m30 30 11 11M16 21h10M21 16v10"/></svg>',
      ),
      array(
          'title' => pvdak_home_start_copy( 'audience_2_title', 'مقاله و راهنما بخوانم' ),
          'desc' => pvdak_home_start_copy( 'audience_2_description', 'در مجله حقوقی، یادداشت‌ها و مطالب آموزشی را دنبال کنید.' ),
          'cta' => 'ورود به مجله',
          'url' => $magazine_feature ? home_url( '/#magazine' ) : ( $magazine_archive ?: add_query_arg( 's', 'حقوق', home_url( '/' ) ) ),
          'icon' => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 11h15c3 0 5 2 5 5v25c0-4-3-6-7-6H7V11ZM41 11H27v30c0-4 3-6 7-6h7V11ZM14 19h7m-7 6h7m13-6h-4m4 6h-4"/></svg>',
      ),
      array(
          'title' => pvdak_home_start_copy( 'audience_3_title', 'حساب کاربری‌ام را ببینم' ),
          'desc' => pvdak_home_start_copy( 'audience_3_description', 'اگر حساب دارید، از پنل وضعیت و اطلاعات خود را ببینید.' ),
          'cta' => is_user_logged_in() ? 'پنل من' : 'ورود به حساب',
          'url' => is_user_logged_in() ? pvdak_auth_account_url() : pvdak_auth_login_url(),
          'icon' => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="24" cy="16" r="8"/><path d="M9 41v-4c0-9 7-13 15-13s15 4 15 13v4M13 40h22"/></svg>',
      ),
  );
  ?>
  <section id="services" class="pvdak-home-section pvdak-audiences pvdak-start-here" aria-labelledby="audiences-title">
    <div class="pvdak-home-section-head pvdak-start-here-heading">
      <div><span class="pvdak-start-here-kicker">دسترسی سریع</span>
        <h2 id="audiences-title"><?php echo esc_html( pvdak_home_start_copy( 'audiences_title', 'می‌خواهید چه کاری انجام دهید؟' ) ); ?></h2>
        <p><?php echo esc_html( pvdak_home_start_copy( 'audiences_intro', 'بدون انتخاب نقش یا ثبت‌نام اجباری، مستقیم به بخش موردنیازتان بروید.' ) ); ?></p>
      </div>
    </div>
    <div class="pvdak-start-here-grid">
    <?php foreach ( $pvdak_getting_started as $index => $item ): ?>
      <a class="pvdak-start-card pvdak-start-card--<?php echo esc_attr( $index + 1 ); ?>" href="<?php echo esc_url( $item['url'] ); ?>">
        <span class="pvdak-start-icon" aria-hidden="true"><?php echo $item['icon']; ?></span>
        <span class="pvdak-start-info"><strong><?php echo esc_html( $item['title'] ); ?></strong><small><?php echo esc_html( $item['desc'] ); ?></small><span class="pvdak-start-cta"><?php echo esc_html( $item['cta'] ); ?> <span aria-hidden="true">←</span></span></span>
      </a>
    <?php endforeach; ?>
    </div>
  </section>

  <?php
  // Topic pages and search mode are resolved before rendering the hero.
  ?>
  <?php if ( $all_legal_pages ): ?>
  <section id="topics" class="pvdak-home-section pvdak-topics-section" aria-labelledby="topics-title">
    <div class="pvdak-home-section-head">
      <h2 id="topics-title"><?php echo esc_html( pvdak_home_value( 'topics_title' ) ); ?></h2>
      <div class="pvdak-topics-head-actions">
        <div class="pvdak-topics-controls" aria-label="کنترل اسلایدر موضوعات" hidden>
          <span class="pvdak-topics-swipe-hint" aria-hidden="true">موضوعات را ورق بزنید</span>
          <button type="button" class="pvdak-topics-control pvdak-topics-control--prev" data-pvdak-topic-prev aria-controls="pvdak-topics-track" aria-label="موضوع قبلی">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9 5 7 7-7 7" /></svg>
          </button>
          <button type="button" class="pvdak-topics-control pvdak-topics-control--next" data-pvdak-topic-next aria-controls="pvdak-topics-track" aria-label="موضوع بعدی">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m15 5-7 7 7 7" /></svg>
          </button>
        </div>
        <?php if ( $legal_parent_id && $all_legal_pages ): ?>
          <a class="pvdak-section-aside" href="<?php echo esc_url( get_permalink( $legal_parent_id ) ); ?>">همه موضوعات ←</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ( $legal_pages ): ?>
    <p class="pvdak-home-section-caption" data-pvdak-topic-count role="status" aria-live="polite"><?php echo esc_html( number_format_i18n( count( $legal_pages ) ) ); ?> موضوع حقوقی قابل مشاهده است.</p>
    <div class="pvdak-topics-swiper swiper" data-pvdak-swiper role="region" aria-roledescription="اسلایدر" aria-label="موضوعات حقوقی">
      <div id="pvdak-topics-track" class="pvdak-home-topics swiper-wrapper" data-pvdak-swiper-track>
        <?php foreach ( $legal_pages as $topic_index => $legal_page ): ?>
          <a class="pvdak-home-topic swiper-slide pvdak-topic-tone-<?php echo esc_attr( ( $topic_index % 6 ) + 1 ); ?>" data-pvdak-topic href="<?php echo esc_url( get_permalink( $legal_page->ID ) ); ?>">
            <span class="pvdak-topic-card__icon" aria-hidden="true"><?php echo pvdak_topic_card_svg( $legal_page ); ?></span>
            <span class="pvdak-topic-card__title"><?php echo esc_html( get_the_title( $legal_page->ID ) ); ?></span>
            <span class="pvdak-topic-card__caption"><?php echo esc_html( pvdak_topic_card_caption( $legal_page ) ); ?></span>
            <span class="pvdak-topic-card__footer" aria-hidden="true">مشاهده موضوع <span>←</span></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <p id="pvdak-search-empty" class="pvdak-home-search-empty" role="status" hidden>موضوعی با این عبارت پیدا نشد.</p>
    <?php elseif ( '' !== $legal_search_term && $all_legal_pages ): ?>
      <p id="pvdak-search-empty" class="pvdak-home-search-empty" role="status">
        موضوعی مطابق «<?php echo esc_html( $legal_search_term ); ?>» پیدا نشد.
        <a href="<?php echo esc_url( home_url( '/#topics' ) ); ?>">نمایش همه موضوعات</a>
      </p>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php
  // Hand-authored inline SVGs; icons are theme assets while titles/descriptions
  // continue to come from the existing editable ACF homepage fields.
  $process_icons = array(
      1 => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="11" y="10" width="26" height="32" rx="5"/><path d="M20 10v-3h8v3M17 21h14M17 28h9M17 35h6"/><path d="m29 31 7-7 4 4-7 7-5 1 1-5Z"/></svg>',
      2 => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M24 5c7 5 14 5 18 6v13c0 9-6 15-18 19C12 39 6 33 6 24V11c4-1 11-1 18-6Z"/><path d="m15 23 6 6 12-12"/><path d="M17 11c4-1 5-2 7-3"/></svg>',
      3 => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="20" cy="16" r="7"/><path d="M6 37c0-8 6-13 14-13 5 0 9 2 11 6M30 12a6 6 0 0 1 5 11"/><circle cx="36" cy="35" r="8"/><path d="m32 35 3 3 5-6"/></svg>',
      4 => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 8h30v26H24l-9 7v-7H9V8Z"/><path d="M16 17h16m-16 6h9"/><circle cx="35" cy="33" r="9" fill="#134a51" stroke="currentColor"/><path d="m31 33 3 3 5-6"/></svg>',
  );
  ?>
  <section class="pvdak-home-process pvdak-process-timeline" aria-labelledby="process-title">
    <div class="pvdak-process-heading">
      <div>
        <span class="pvdak-process-eyebrow"><?php echo function_exists( 'pvdak_core_intake_open' ) && pvdak_core_intake_open() ? 'مسیر همراهی' : 'آشنایی با روند خدمات'; ?></span>
        <h2 id="process-title"><?php echo esc_html( pvdak_home_value( 'process_title' ) ); ?></h2>
      </div>
      <span class="pvdak-process-heading-note"><?php echo function_exists( 'pvdak_core_intake_open' ) && pvdak_core_intake_open() ? 'چهار گام روشن، از شروع تا پیگیری' : 'این مراحل پس از آغاز رسمی خدمات قابل استفاده خواهند بود'; ?></span>
    </div>
    <ol class="pvdak-home-grid pvdak-home-grid--4 pvdak-process-grid" aria-label="چهار مرحله پیگیری درخواست">
      <?php for ( $i = 1; $i <= 4; $i++ ): ?>
        <li class="pvdak-home-step pvdak-process-step pvdak-process-step--<?php echo esc_attr( $i ); ?>">
          <div class="pvdak-process-step-head">
            <span class="pvdak-process-step-number">مرحله <?php echo esc_html( $i ); ?></span>
            <span class="pvdak-process-step-icon" aria-hidden="true"><?php echo $process_icons[ $i ]; ?></span>
          </div>
          <h3><?php echo esc_html( pvdak_home_value( "step_{$i}_title" ) ); ?></h3>
          <p><?php echo esc_html( pvdak_home_value( "step_{$i}_description" ) ); ?></p>
          <span class="pvdak-process-step-decoration" aria-hidden="true"></span>
        </li>
      <?php endfor; ?>
    </ol>
  </section>

  <?php if ( $magazine_feature ): ?>
  <section id="magazine" class="pvdak-home-section pvdak-magazine" aria-labelledby="magazine-title">
    <div class="pvdak-home-section-head">
      <h2 id="magazine-title"><?php echo esc_html( pvdak_home_value( 'magazine_title' ) ); ?></h2>
      <?php if ( $magazine_archive ): ?><a class="pvdak-section-aside" href="<?php echo esc_url( $magazine_archive ); ?>">ورود به مجله ←</a><?php endif; ?>
    </div>
    <?php if ( $magazine_feature ): ?>
      <div class="pvdak-magazine-layout">
        <a class="pvdak-magazine-feature <?php echo has_post_thumbnail( $magazine_feature->ID ) ? 'has-image' : ''; ?>" href="<?php echo esc_url( get_permalink( $magazine_feature->ID ) ); ?>" aria-label="<?php echo esc_attr( 'مطالعه مقاله: ' . get_the_title( $magazine_feature->ID ) ); ?>">
          <?php if ( has_post_thumbnail( $magazine_feature->ID ) ): ?>
            <?php echo get_the_post_thumbnail( $magazine_feature->ID, 'large', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
          <?php endif; ?>
          <span class="pvdak-magazine-feature-badge">مقاله منتخب</span>
          <?php if ( ! has_post_thumbnail( $magazine_feature->ID ) ): ?><span class="pvdak-magazine-feature-illustration" aria-hidden="true"><svg viewBox="0 0 140 140" fill="none" stroke="currentColor" stroke-width="3"><path d="M70 20v100M28 38h84M38 40l-19 46h38L38 40Zm64 0L83 86h38l-19-46ZM45 122h50"/><circle cx="70" cy="20" r="5" fill="currentColor"/></svg></span><?php endif; ?>
          <span class="pvdak-magazine-feature-content">
            <span class="pvdak-magazine-feature-meta"><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $magazine_feature->ID ) ); ?>"><?php echo esc_html( get_the_date( '', $magazine_feature->ID ) ); ?></time><span class="pvdak-magazine-meta-dot" aria-hidden="true"></span>مجله حقوقی</span>
            <strong class="pvdak-magazine-feature-title"><?php echo esc_html( get_the_title( $magazine_feature->ID ) ); ?></strong>
            <span class="pvdak-magazine-feature-summary"><?php echo esc_html( pvdak_home_post_summary( $magazine_feature, 23 ) ); ?></span>
            <span class="pvdak-magazine-card-cta">مطالعه مقاله <span aria-hidden="true">←</span></span>
          </span>
        </a>
        <?php if ( $magazine_other ): ?>
          <div class="pvdak-magazine-aside">
            <?php foreach ( array_slice( $magazine_other, 0, 4 ) as $magazine_post ): ?>
              <a class="pvdak-magazine-aside-card" href="<?php echo esc_url( get_permalink( $magazine_post->ID ) ); ?>" aria-label="<?php echo esc_attr( 'مطالعه مقاله: ' . get_the_title( $magazine_post->ID ) ); ?>">
                <span class="pvdak-magazine-aside-picture" aria-hidden="true">
                  <?php if ( has_post_thumbnail( $magazine_post->ID ) ): ?>
                    <?php echo get_the_post_thumbnail( $magazine_post->ID, 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
                  <?php else: ?><span class="pvdak-magazine-image-fallback"></span><?php endif; ?>
                </span>
                <span class="pvdak-magazine-aside-body">
                  <span class="pvdak-magazine-aside-date"><?php echo esc_html( get_the_date( '', $magazine_post->ID ) ); ?></span>
                  <strong class="pvdak-magazine-aside-title"><?php echo esc_html( get_the_title( $magazine_post->ID ) ); ?></strong>
                  <span class="pvdak-magazine-aside-summary"><?php echo esc_html( pvdak_home_post_summary( $magazine_post, 12 ) ); ?></span>
                  <span class="pvdak-magazine-card-cta">مطالعه مطلب <span aria-hidden="true">←</span></span>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ( $notice_posts || $news_feature ): ?>
  <section id="news" class="pvdak-home-section pvdak-news" aria-labelledby="news-title">
    <div class="pvdak-home-section-head">
      <h2 id="news-title"><?php echo esc_html( pvdak_home_value( 'news_title' ) ); ?></h2>
      <?php if ( $notices_archive ): ?><a class="pvdak-section-aside" href="<?php echo esc_url( $notices_archive ); ?>"><?php echo esc_html( pvdak_home_value( 'news_archive_label' ) ); ?> ←</a><?php endif; ?>
    </div>
    <div class="pvdak-news-grid">
      <?php if ( $notice_posts ): ?>
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
              <time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $notice_post->ID ) ); ?>"><?php echo esc_html( get_the_date( '', $notice_post->ID ) ); ?></time>
              <p><?php echo esc_html( pvdak_home_post_summary( $notice_post, 13 ) ); ?></p></div>
            </div>
          <?php endforeach; ?>

        </div>
      </article>
      <?php endif; ?>
      <?php if ( $news_feature ): ?>
        <article class="pvdak-news-article">
          <div class="pvdak-news-article-picture">
            <?php if ( has_post_thumbnail( $news_feature->ID ) ): ?>
                <?php echo get_the_post_thumbnail( $news_feature->ID, 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
            <?php else: ?><span class="pvdak-news-fallback-art" aria-hidden="true"><svg viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-width="3"><path d="M40 9v62M12 25h56M25 25 12 49h27L25 25Zm30 0L41 49h27L55 25ZM26 72h28"/></svg></span><?php endif; ?>
          </div>
          <div class="pvdak-news-article-content">
            <time class="pvdak-news-article-time" datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $news_feature->ID ) ); ?>"><?php echo esc_html( get_the_date( '', $news_feature->ID ) ); ?></time>
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
        <?php
        // A manually entered URL is not evidence that the forum workflow
        // exists. Only link an actual published internal forum Page when the
        // matching shortcode is installed and rendered from that Page.
        $forum_url = '';
        $forum_candidate = pvdak_home_value( 'forum_url', '' );
        if ( is_string( $forum_candidate ) && '' !== trim( $forum_candidate ) &&
             shortcode_exists( 'pvdak_forum' ) ) {
            $forum_id = url_to_postid( $forum_candidate );
            $forum_page = $forum_id ? get_post( $forum_id ) : null;
            if ( $forum_page instanceof WP_Post && 'page' === $forum_page->post_type &&
                 'publish' === $forum_page->post_status &&
                 has_shortcode( $forum_page->post_content, 'pvdak_forum' ) ) {
                $forum_url = get_permalink( $forum_page->ID );
            }
        }
        ?>
        <?php if ( $forum_url ): ?><a class="pvdak-forum-button" href="<?php echo esc_url( $forum_url ); ?>"><?php echo esc_html( pvdak_home_value( 'forum_feature_button' ) ); ?> ←</a><?php else: ?><span class="pvdak-forum-soon" role="note">انجمن تخصصی در حال آماده‌سازی است</span><?php endif; ?>
      </article>
    </div>
  </section>
  <?php endif; ?>

  <section id="community" class="pvdak-home-community" aria-labelledby="community-title">
    <div class="pvdak-home-community-content">
      <h2 id="community-title"><?php echo esc_html(pvdak_home_value('community_title')); ?></h2>
      <p><?php echo esc_html(pvdak_home_value('community_description')); ?></p>
      <p><?php echo esc_html(pvdak_home_value('community_note')); ?></p>
      <?php $u=pvdak_home_join_url(); if($u): ?><a class="pvdak-home-button" href="<?php echo esc_url($u); ?>"><?php echo esc_html(pvdak_home_value('community_button')); ?> ←</a><?php else: ?><span class="pvdak-community-pending" role="note">پیوستن به جامعه حقوقی پس از آغاز ثبت‌نام امکان‌پذیر است.</span><?php endif; ?>
    </div>
    <?php if($community_img): ?><img src="<?php echo esc_url($community_img); ?>" alt="" loading="lazy" decoding="async"><?php else: ?>
    <div class="pvdak-community-illustration" aria-hidden="true">
      <svg viewBox="0 0 320 194" xmlns="http://www.w3.org/2000/svg" fill="none">
        <defs><linearGradient id="pvdak-c-plate" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#145a86"/><stop offset="1" stop-color="#093753"/></linearGradient><linearGradient id="pvdak-c-gold" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#ffe4a6"/><stop offset="1" stop-color="#d49a43"/></linearGradient></defs>
        <rect width="320" height="194" rx="17" fill="url(#pvdak-c-plate)"/>
        <circle cx="272" cy="42" r="91" fill="#1a91a2" opacity=".28"/><circle cx="44" cy="163" r="78" fill="#1a8a99" opacity=".17"/>
        <path d="M47 165h232" stroke="#9ad8df" stroke-opacity=".35" stroke-width="2"/>
        <g stroke="#e8bd6c" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
          <path d="M165 30v135M93 59h144M165 23v11M144 165h42M130 171h70"/>
          <path d="m108 64-22 59h44l-22-59ZM222 64l-22 59h44l-22-59Z"/>
        </g>
        <path d="M85 123c2 15 12 20 23 20s21-5 23-20M199 123c2 15 12 20 23 20s21-5 23-20" stroke="url(#pvdak-c-gold)" stroke-width="4"/>
        <circle cx="165" cy="57" r="7" fill="#f6d79d"/>
        <g transform="translate(24 110) rotate(-11)"><rect width="57" height="57" rx="5" fill="#092d51" stroke="#84c7d3"/><path d="M15 8v41M21 8v41" stroke="#d2b279"/><path d="M27 20h21M27 28h17M27 36h13" stroke="#87c7d5" stroke-width="2"/></g>
        <g transform="translate(242 119) rotate(9)"><rect width="57" height="51" rx="5" fill="#0b4166" stroke="#88bbc7"/><path d="M12 13h34M12 21h28M12 29h32M12 37h20" stroke="#c2d9dc" stroke-width="2"/></g>
        <path d="M35 37h64M35 45h42" stroke="#6bb4c7" stroke-opacity=".55" stroke-width="3" stroke-linecap="round"/>
      </svg>
    </div>
  <?php endif; ?>
  </section>

  <?php if ( $faq_pages || $faq_archive ): ?>
  <section class="pvdak-home-section pvdak-home-faq" aria-labelledby="faq-title">
    <div class="pvdak-home-section-head">
      <div><span class="pvdak-faq-eyebrow">پاسخ‌های کوتاه و کاربردی</span><h2 id="faq-title"><?php echo esc_html( pvdak_home_value( 'faq_title' ) ); ?></h2></div>
      <?php if ( $faq_pages ): ?><span class="pvdak-faq-count"><?php echo esc_html( number_format_i18n( count( $faq_pages ) ) ); ?> پرسش منتشرشده</span><?php endif; ?>
    </div>
    <?php foreach ( $faq_pages as $faq_page ): ?>
      <details class="pvdak-faq-item">
        <summary><span class="pvdak-faq-q" aria-hidden="true">؟</span><?php echo esc_html( get_the_title( $faq_page->ID ) ); ?></summary>
        <div><?php echo wp_kses_post( apply_filters( 'the_content', $faq_page->post_content ) ); ?></div>
      </details>
    <?php endforeach; ?>
    <?php if ( ! $faq_pages ): ?>
      <p class="pvdak-faq-pending">پاسخ‌های تفصیلی در حال تکمیل هستند؛ راهنمای فعلی سایت را در صفحه پرسش‌های متداول بخوانید.</p>
    <?php endif; ?>
    <?php if ( $faq_archive ): ?>
      <a class="pvdak-section-aside pvdak-faq-archive-link" href="<?php echo esc_url( $faq_archive ); ?>">مشاهده همه پرسش‌های متداول ←</a>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</main>
<?php get_footer(); ?>
