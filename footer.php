<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<footer class="site-footer">
    <div class="container pvdak-footer-layout">
        <div class="pvdak-footer-brand">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="pvdak-footer-logo">PVDAK</a>
            <p>پویش وکلای دادگستری کشور</p>
            <p>همراهی، حمایت، تجربه؛ با یک مسیر روشن.</p>
        </div>
        <div class="pvdak-footer-nav">
            <?php if ( has_nav_menu( 'footer' ) ) : ?>
                <nav aria-label="<?php esc_attr_e('منوی فوتر','pvdak'); ?>">
                    <?php wp_nav_menu(array('theme_location'=>'footer','container'=>false,'menu_class'=>'menu','fallback_cb'=>false,'depth'=>1)); ?>
                </nav>
            <?php else : ?>
                <p>همراه مسیر حقوقی شما</p>
                <a href="<?php echo esc_url(home_url('/#services')); ?>">خدمات حقوقی</a>
                <a href="<?php echo esc_url(home_url('/#community')); ?>">همکاری با ما</a>
            <?php endif; ?>
        </div>
        <nav class="pvdak-footer-public" aria-label="اطلاعات سایت">
          <strong>لینک‌های مهم</strong>
          <?php foreach(array('about-us'=>'درباره ما','contact-us'=>'تماس با ما','privacy-policy'=>'حریم خصوصی','terms-of-use'=>'شرایط استفاده') as $slug=>$label):
             $url=pvdak_public_page_url($slug); if(!$url)continue; ?>
            <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
          <?php endforeach; ?>
        </nav>
        <div class="pvdak-footer-address">
            <span>نشانی اینترنتی</span>
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(wp_parse_url(home_url('/'), PHP_URL_HOST)); ?></a>
        </div>
    </div>
    <div class="pvdak-footer-bottom container">
        <small>&copy; <?php echo esc_html(wp_date('Y')); ?> تمام حقوق محفوظ است.</small>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
