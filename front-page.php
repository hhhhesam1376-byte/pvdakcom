<?php
/** PVDak homepage v1.01.05 — pixel-guided semantic markup, ACF-powered content. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$hero_desktop = pvdak_home_image_url('hero_image');
$hero_mobile  = pvdak_home_image_url('hero_image_mobile');
$magazine_img = pvdak_home_image_url('magazine_image');
$article_img  = pvdak_home_image_url('news_article_image');
if ( ! $article_img ) { $article_img = $magazine_img; }
$community_img= pvdak_home_image_url('community_image');

$icon_paths = array(
    1 => '<circle cx="9" cy="8" r="3"/><path d="M3.5 20v-2a5.5 5.5 0 0 1 11 0v2M17 9a3 3 0 0 1 0 6m2 5v-2a4 4 0 0 0-3-3.87"/>',
    2 => '<path d="M3 10.5 12 3l9 7.5M5 9v12h14V9M9 21v-9h6v9"/>',
    3 => '<path d="m14 4 6 6m-9-3 6 6M3 21l10-10m-2-4 3-3 6 6-3 3m-4 3 3 3M4 20h16"/>',
    4 => '<path d="M5 3h14v18H5zM8 7h8m-8 4h8m-8 4h5"/>',
    5 => '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M9 7V4h6v3m-12 7h18M11 13h2"/>',
    6 => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 11h18m-15 4h4"/>',
    7 => '<path d="m4 20 8-16 8 16M8 13h8M2 20h20"/>',
    8 => '<path d="M5 21V3h14v18M2 21h20M9 7h2m3 0h2M9 11h2m3 0h2M9 15h2m3 0h2"/>'
);
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
      <form class="pvdak-home-search" method="get" action="<?php echo esc_url(home_url('/')); ?>" role="search">
        <span class="pvdak-home-search-info" aria-hidden="true">?</span>
        <label class="screen-reader-text" for="pvdak-topic-input">موضوع حقوقی</label>
        <input id="pvdak-topic-input" type="search" name="s" placeholder="<?php echo esc_attr(pvdak_home_value('hero_search_label')); ?>">
        <button type="submit">جست‌وجو</button>
      </form>
      <div class="pvdak-home-hero-actions">
        <?php
        $symbol_names=array(1=>'scales',2=>'chat',3=>'question');
        for($i=1;$i<=3;$i++):
            $url=pvdak_home_value("service_{$i}_url",'');
            $title=pvdak_home_value("service_{$i}_title");
            $symbol='<svg viewBox="0 0 24 24" aria-hidden="true">'. $svg_icons[$symbol_names[$i]] . '</svg>';
        ?>
          <?php if($url): ?><a class="pvdak-home-hero-action" href="<?php echo esc_url($url); ?>">
          <?php else: ?><span class="pvdak-home-hero-action">
          <?php endif; ?>
            <?php echo $symbol; ?><span><?php echo esc_html($title); ?></span><span class="pvdak-arrow" aria-hidden="true">←</span>
          <?php if($url): ?></a><?php else: ?></span><?php endif; ?>
        <?php endfor; ?>
      </div>
      <p class="pvdak-home-process-hint"><?php echo esc_html(pvdak_home_value('hero_hint')); ?></p>
    </div>
  </section>

  <div class="pvdak-home-quick-actions" aria-label="دسترسی‌های سریع">
    <?php
    $quick_symbols=array('document','question','briefcase','chat');
    for($i=1;$i<=4;$i++):
        $title=pvdak_home_value("quick_{$i}_title");
        $url=pvdak_home_value("quick_{$i}_url",'');
        $symbol='<svg viewBox="0 0 24 24" aria-hidden="true">'. $svg_icons[$quick_symbols[$i-1]] . '</svg>';
    ?>
      <?php if($url): ?><a href="<?php echo esc_url($url); ?>"><?php else: ?><span><?php endif; ?>
        <?php echo $symbol; ?><span><?php echo esc_html($title); ?></span>
      <?php if($url): ?></a><?php else: ?></span><?php endif; ?>
    <?php endfor; ?>
  </div>

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
  $legal_pages = pvdak_legal_topic_pages( $legal_parent_id );
  ?>
  <section id="topics" class="pvdak-home-section pvdak-topics-section" aria-labelledby="topics-title">
    <div class="pvdak-home-section-head">
      <h2 id="topics-title"><?php echo esc_html( pvdak_home_value( 'topics_title' ) ); ?></h2>
      <?php if ( $legal_parent_id ): ?>
        <a class="pvdak-section-aside" href="<?php echo esc_url( get_permalink( $legal_parent_id ) ); ?>">همه موضوعات ←</a>
      <?php endif; ?>
    </div>

    <?php if ( $legal_pages ): ?>
    <div class="pvdak-home-topics">
      <?php foreach ( $legal_pages as $legal_page ): ?>
        <a class="pvdak-home-topic" data-pvdak-topic href="<?php echo esc_url( get_permalink( $legal_page->ID ) ); ?>">
          <?php echo pvdak_legal_topic_svg( pvdak_legal_topic_icon( $legal_page->ID ) ); ?>
          <span><?php echo esc_html( get_the_title( $legal_page->ID ) ); ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <p id="pvdak-search-empty" class="pvdak-home-search-empty" role="status" hidden>موضوعی با این عبارت پیدا نشد.</p>
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

  <section id="magazine" class="pvdak-home-section pvdak-magazine" aria-labelledby="magazine-title">
    <div class="pvdak-home-section-head">
      <h2 id="magazine-title"><?php echo esc_html(pvdak_home_value('magazine_title')); ?></h2>
      <?php $mag_url=pvdak_home_value('magazine_url',''); ?>
      <?php if($mag_url): ?><a class="pvdak-section-aside" href="<?php echo esc_url($mag_url); ?>">ورود به مجله ←</a><?php else: ?><span class="pvdak-section-aside">ورود به مجله ←</span><?php endif; ?>
    </div>
    <div class="pvdak-magazine-layout">
      <article class="pvdak-magazine-feature <?php echo $magazine_img?'has-image':''; ?>">
        <?php if($magazine_img): ?><img src="<?php echo esc_url($magazine_img); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
        <div class="pvdak-magazine-feature-content">
          <h3><?php echo esc_html(pvdak_home_value('magazine_heading')); ?></h3>
          <p><?php echo esc_html(pvdak_home_value('magazine_description')); ?></p>
          <?php if($mag_url): ?><a href="<?php echo esc_url($mag_url); ?>"><?php echo esc_html(pvdak_home_value('magazine_button')); ?> ←</a><?php else: ?><span><?php echo esc_html(pvdak_home_value('magazine_button')); ?> ←</span><?php endif; ?>
        </div>
      </article>
      <div class="pvdak-magazine-aside">
        <?php for($i=1;$i<=2;$i++):
          $side_url=pvdak_home_value("magazine_side_{$i}_url",'');
        ?>
          <article class="pvdak-magazine-aside-card pvdak-magazine-aside-card--<?php echo esc_attr($i); ?>">
            <span class="pvdak-magazine-aside-symbol" aria-hidden="true"><svg viewBox="0 0 24 24"><?php echo $svg_icons[$i===1?'document':'chat']; ?></svg></span>
            <h3><?php echo esc_html(pvdak_home_value("magazine_side_{$i}_title")); ?></h3>
            <p><?php echo esc_html(pvdak_home_value("magazine_side_{$i}_description")); ?></p>
            <?php if($side_url): ?><a href="<?php echo esc_url($side_url); ?>"><?php else: ?><span><?php endif; ?><?php echo esc_html(pvdak_home_value("magazine_side_{$i}_button")); ?> ←<?php if($side_url): ?></a><?php else: ?></span><?php endif; ?>
          </article>
        <?php endfor; ?>
      </div>
    </div>
  </section>

  <section id="news" class="pvdak-home-section pvdak-news" aria-labelledby="news-title">
    <div class="pvdak-home-section-head">
      <h2 id="news-title"><?php echo esc_html(pvdak_home_value('news_title')); ?></h2>
      <?php $archive=pvdak_home_value('news_archive_url',''); ?>
      <?php if($archive): ?><a class="pvdak-section-aside" href="<?php echo esc_url($archive); ?>"><?php else: ?><span class="pvdak-section-aside"><?php endif; ?>
        <?php echo esc_html(pvdak_home_value('news_archive_label')); ?> ←
      <?php if($archive): ?></a><?php else: ?></span><?php endif; ?>
    </div>
    <div class="pvdak-news-grid">
      <article class="pvdak-notices-panel">
        <div class="pvdak-notices-header">
          <h3><?php echo esc_html(pvdak_home_value('notice_heading')); ?></h3>
          <span><?php echo esc_html(pvdak_home_value('notice_badge')); ?></span>
        </div>
        <div class="pvdak-notices-list">
          <?php for($i=1;$i<=3;$i++): $u=pvdak_home_value("notice_{$i}_url",''); ?>
            <div class="pvdak-notice">
              <span aria-hidden="true"><svg viewBox="0 0 24 24"><?php echo $svg_icons['document']; ?></svg></span>
              <div>
                <?php if($u): ?><a href="<?php echo esc_url($u); ?>"><?php else: ?><strong><?php endif; ?><?php echo esc_html(pvdak_home_value("notice_{$i}_title")); ?><?php if($u): ?></a><?php else: ?></strong><?php endif; ?>
                <p><?php echo esc_html(pvdak_home_value("notice_{$i}_description")); ?></p>
              </div>
            </div>
          <?php endfor; ?>
        </div>
      </article>
      <article class="pvdak-news-article">
        <div class="pvdak-news-article-picture">
          <?php if($article_img): ?><img src="<?php echo esc_url($article_img); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
        </div>
        <div class="pvdak-news-article-content">
          <h3><?php echo esc_html(pvdak_home_value('news_article_title')); ?></h3>
          <p><?php echo esc_html(pvdak_home_value('news_article_description')); ?></p>
          <?php $u=pvdak_home_value('news_article_url',''); if($u): ?><a href="<?php echo esc_url($u); ?>"><?php else: ?><span><?php endif; ?><?php echo esc_html(pvdak_home_value('news_article_button')); ?> ←<?php if($u): ?></a><?php else: ?></span><?php endif; ?>
        </div>
      </article>
      <article id="forum" class="pvdak-forum-card">
        <span class="pvdak-forum-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><?php echo $svg_icons['chat']; ?></svg></span>
        <h3><?php echo esc_html(pvdak_home_value('forum_feature_title')); ?><br><?php echo esc_html(pvdak_home_value('forum_feature_subtitle')); ?></h3>
        <p><?php echo esc_html(pvdak_home_value('forum_feature_description')); ?></p>
        <?php $u=pvdak_home_value('forum_url',''); if($u): ?><a class="pvdak-forum-button" href="<?php echo esc_url($u); ?>"><?php else: ?><span class="pvdak-forum-button"><?php endif; ?><?php echo esc_html(pvdak_home_value('forum_feature_button')); ?><?php if($u): ?></a><?php else: ?></span><?php endif; ?>
      </article>
    </div>
  </section>

  <section id="community" class="pvdak-home-community" aria-labelledby="community-title">
    <div class="pvdak-home-community-content">
      <h2 id="community-title"><?php echo esc_html(pvdak_home_value('community_title')); ?></h2>
      <p><?php echo esc_html(pvdak_home_value('community_description')); ?></p>
      <p><?php echo esc_html(pvdak_home_value('community_note')); ?></p>
      <?php $u=pvdak_home_value('community_url',''); if($u): ?><a class="pvdak-home-button" href="<?php echo esc_url($u); ?>"><?php else: ?><span class="pvdak-home-button"><?php endif; ?><?php echo esc_html(pvdak_home_value('community_button')); ?> ←<?php if($u): ?></a><?php else: ?></span><?php endif; ?>
    </div>
    <?php if($community_img): ?><img src="<?php echo esc_url($community_img); ?>" alt="" loading="lazy" decoding="async"><?php else: ?><div class="pvdak-community-placeholder" aria-hidden="true"></div><?php endif; ?>
  </section>

  <section class="pvdak-home-section pvdak-home-faq" aria-labelledby="faq-title">
    <div class="pvdak-home-section-head"><h2 id="faq-title"><?php echo esc_html(pvdak_home_value('faq_title')); ?></h2></div>
    <?php for($i=1;$i<=3;$i++): $answer=pvdak_home_value("faq_{$i}_answer",''); ?>
      <details>
        <summary><?php echo esc_html(pvdak_home_value("faq_{$i}_question")); ?></summary>
        <div><?php echo $answer ? wpautop(esc_html($answer)) : '<p>پاسخ این پرسش به‌زودی اضافه می‌شود.</p>'; ?></div>
      </details>
    <?php endfor; ?>
  </section>
</main>
<?php get_footer(); ?>
