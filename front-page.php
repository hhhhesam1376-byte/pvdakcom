<?php
/** PVDak homepage based on design 1.01.05, with ACF-editable content. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
$hero_image = pvdak_home_image_url('hero_image');
$hero_image_mobile = pvdak_home_image_url('hero_image_mobile');
?>
<main id="main-content" class="pvdak-home container" tabindex="-1">
    <section class="pvdak-home-hero"<?php if($hero_image || $hero_image_mobile): ?> style="<?php if($hero_image): ?>--pvdak-hero-image:url('<?php echo esc_url($hero_image); ?>');<?php endif; ?><?php if($hero_image_mobile): ?>--pvdak-hero-mobile-image:url('<?php echo esc_url($hero_image_mobile); ?>');<?php endif; ?>"<?php endif; ?>>
        <div class="pvdak-home-hero__content">
            <p class="pvdak-home-eyebrow"><?php echo esc_html(pvdak_home_value('hero_label')); ?></p>
            <h1><?php echo esc_html(pvdak_home_value('hero_title')); ?></h1>
            <p class="pvdak-home-lead"><?php echo esc_html(pvdak_home_value('hero_description')); ?></p>
            <form class="pvdak-home-search" action="<?php echo esc_url(home_url('/')); ?>" method="get" role="search">
                <label for="pvdak-legal-topic" class="screen-reader-text">جستجوی موضوع حقوقی</label>
                <span class="pvdak-home-search-symbol" aria-hidden="true">⌕</span>
                <input id="pvdak-legal-topic" name="s" type="search" autocomplete="off"
                    placeholder="<?php echo esc_attr(pvdak_home_value('hero_search_label')); ?>">
                <button type="submit">جستجو</button>
            </form>
            <div class="pvdak-home-hero-actions" aria-label="خدمات اصلی">
                <?php
                $hero_symbols = array(1=>'⚖',2=>'▤',3=>'؟');
                for($i=1;$i<=3;$i++):
                    $label = pvdak_home_value("service_{$i}_title");
                    $url = pvdak_home_value("service_{$i}_url",'');
                    if($url): ?>
                        <a class="pvdak-home-hero-action" href="<?php echo esc_url($url); ?>">
                            <span aria-hidden="true"><?php echo esc_html($hero_symbols[$i]); ?></span><?php echo esc_html($label); ?>
                        </a>
                    <?php else: ?>
                        <span class="pvdak-home-hero-action">
                            <span aria-hidden="true"><?php echo esc_html($hero_symbols[$i]); ?></span><?php echo esc_html($label); ?>
                        </span>
                    <?php endif;
                endfor; ?>
            </div>
            <p class="pvdak-home-process-hint"><?php echo esc_html(pvdak_home_value('hero_hint')); ?></p>
        </div>
    </section>
    <div class="pvdak-home-quick-actions" aria-label="دسترسی سریع به بخش‌های سایت">
        <a href="#services"><span aria-hidden="true">▤</span> خدمات حقوقی</a>
        <a href="#topics"><span aria-hidden="true">⌕</span> موضوعات حقوقی</a>
        <a href="#magazine"><span aria-hidden="true">▣</span> مجله حقوقی</a>
        <a href="#community"><span aria-hidden="true">✦</span> همکاری با ما</a>
    </div>
    <section id="services" class="pvdak-home-section" aria-labelledby="services-title">
        <div class="pvdak-home-section-head"><h2 id="services-title"><?php echo esc_html(pvdak_home_value('services_title')); ?></h2></div>
        <div class="pvdak-home-grid pvdak-home-grid--3">
        <?php for($i=1;$i<=3;$i++):
            $url = pvdak_home_value("service_{$i}_url",'');
        ?>
            <article class="pvdak-home-tile pvdak-home-tile--service">
                <span class="pvdak-home-icon" aria-hidden="true"><?php echo esc_html(sprintf('%02d',$i)); ?></span>
                <h3><?php echo esc_html(pvdak_home_value("service_{$i}_title")); ?></h3>
                <p><?php echo esc_html(pvdak_home_value("service_{$i}_description")); ?></p>
                <?php if($url): ?><a class="pvdak-home-text-link" href="<?php echo esc_url($url); ?>">ثبت درخواست ←</a><?php endif; ?>
            </article>
        <?php endfor; ?>
        </div>
    </section>
    <section class="pvdak-home-section" aria-labelledby="audiences-title">
        <div class="pvdak-home-section-head">
            <h2 id="audiences-title"><?php echo esc_html(pvdak_home_value('audiences_title')); ?></h2>
            <p><?php echo esc_html(pvdak_home_value('audiences_intro')); ?></p>
        </div>
        <div class="pvdak-home-grid pvdak-home-grid--3">
        <?php for($i=1;$i<=3;$i++):
            $url = pvdak_home_value("audience_{$i}_url",'');
        ?>
            <article class="pvdak-home-tile pvdak-home-tile--audience <?php echo 1===$i?'pvdak-home-tile--featured':''; ?>">
                <span class="pvdak-home-icon" aria-hidden="true"><?php echo esc_html(sprintf('%02d',$i)); ?></span>
                <h3><?php echo esc_html(pvdak_home_value("audience_{$i}_title")); ?></h3>
                <p><?php echo esc_html(pvdak_home_value("audience_{$i}_description")); ?></p>
                <?php if($url): ?><a class="pvdak-home-text-link" href="<?php echo esc_url($url); ?>">ورود به بخش مربوط ←</a><?php endif; ?>
            </article>
        <?php endfor; ?>
        </div>
    </section>
    <section id="topics" class="pvdak-home-section" aria-labelledby="topics-title">
        <div class="pvdak-home-section-head"><h2 id="topics-title"><?php echo esc_html(pvdak_home_value('topics_title')); ?></h2></div>
        <div class="pvdak-home-topics">
        <?php for($i=1;$i<=8;$i++):
            $url=pvdak_home_value("topic_{$i}_url",'');
            $label=pvdak_home_value("topic_{$i}_title");
        ?>
            <?php if($url): ?><a class="pvdak-home-topic" data-pvdak-topic href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
            <?php else: ?><span class="pvdak-home-topic" data-pvdak-topic><?php echo esc_html($label); ?></span><?php endif; ?>
        <?php endfor; ?>
        </div>
        <p class="pvdak-home-search-empty" id="pvdak-search-empty" role="status" hidden>موضوعی با این عبارت پیدا نشد. می‌توانید عبارت دیگری امتحان کنید.</p>
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
    <section id="magazine" class="pvdak-home-section" aria-labelledby="magazine-title">
        <div class="pvdak-home-section-head"><h2 id="magazine-title"><?php echo esc_html(pvdak_home_value('magazine_title')); ?></h2></div>
        <div class="pvdak-home-grid pvdak-home-grid--2">
            <?php $magazine_image=pvdak_home_image_url('magazine_image'); ?>
            <article class="pvdak-home-editorial pvdak-home-editorial--magazine">
                <?php if($magazine_image): ?><img src="<?php echo esc_url($magazine_image); ?>" alt="" loading="lazy"><?php endif; ?>
                <div class="pvdak-home-editorial__body">
                    <h3><?php echo esc_html(pvdak_home_value('magazine_heading')); ?></h3>
                    <p><?php echo esc_html(pvdak_home_value('magazine_description')); ?></p>
                    <?php $url=pvdak_home_value('magazine_url',''); if($url): ?><a class="pvdak-home-text-link" href="<?php echo esc_url($url); ?>"><?php echo esc_html(pvdak_home_value('magazine_button')); ?> ←</a><?php endif; ?>
                </div>
            </article>
            <?php $forum_image=pvdak_home_image_url('forum_image'); ?>
            <article id="forum" class="pvdak-home-editorial pvdak-home-editorial--forum">
                <?php if($forum_image): ?><img src="<?php echo esc_url($forum_image); ?>" alt="" loading="lazy"><?php endif; ?>
                <div class="pvdak-home-editorial__body">
                    <h3><?php echo esc_html(pvdak_home_value('forum_title')); ?></h3>
                    <p><?php echo esc_html(pvdak_home_value('forum_description')); ?></p>
                    <?php $url=pvdak_home_value('forum_url',''); if($url): ?><a class="pvdak-home-text-link" href="<?php echo esc_url($url); ?>"><?php echo esc_html(pvdak_home_value('forum_button')); ?> ←</a><?php endif; ?>
                </div>
            </article>
        </div>
    </section>
    <section id="community" class="pvdak-home-community" aria-labelledby="community-title">
        <?php $community_image=pvdak_home_image_url('community_image'); ?>
        <?php if($community_image): ?><img src="<?php echo esc_url($community_image); ?>" alt="" loading="lazy"><?php endif; ?>
        <div>
            <h2 id="community-title"><?php echo esc_html(pvdak_home_value('community_title')); ?></h2>
            <p><?php echo esc_html(pvdak_home_value('community_description')); ?></p>
            <p><?php echo esc_html(pvdak_home_value('community_note')); ?></p>
            <?php $url=pvdak_home_value('community_url',''); if($url): ?><a class="pvdak-home-button" href="<?php echo esc_url($url); ?>"><?php echo esc_html(pvdak_home_value('community_button')); ?></a><?php endif; ?>
        </div>
    </section>
    <section class="pvdak-home-section pvdak-home-faq" aria-labelledby="faq-title">
        <div class="pvdak-home-section-head"><h2 id="faq-title"><?php echo esc_html(pvdak_home_value('faq_title')); ?></h2></div>
        <?php for($i=1;$i<=3;$i++):
            $answer=pvdak_home_value("faq_{$i}_answer",'');
        ?>
            <details>
                <summary><?php echo esc_html(pvdak_home_value("faq_{$i}_question")); ?></summary>
                <p><?php echo esc_html($answer ? $answer : 'پاسخ این پرسش به‌زودی در همین بخش قرار می‌گیرد.'); ?></p>
            </details>
        <?php endfor; ?>
    </section>
</main>
<?php get_footer(); ?>
