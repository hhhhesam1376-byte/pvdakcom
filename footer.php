<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<footer class="site-footer">
    <div class="container pvdak-footer-layout">
        <div class="pvdak-footer-brand">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="pvdak-footer-logo"><svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M20 3v32M6 11h28M9 12 4 24h10L9 12Zm22 0-5 12h10l-5-12ZM12 36h16"/><circle cx="20" cy="5" r="2" fill="currentColor"/></svg><span>PVDAK</span></a>
            <p>پویش وکلای دادگستری کشور</p>
            <p>مسیر روشن آگاهی حقوقی، از مطالعه تا پیگیری.</p><span class="pvdak-footer-tagline">آگاهی • عدالت • همراهی</span>
        </div>
        <div class="pvdak-footer-nav">
            <h2 class="pvdak-footer-heading">دسترسی سریع</h2>
            <?php if ( has_nav_menu( 'footer' ) ) : ?>
                <nav aria-label="<?php esc_attr_e('منوی فوتر','pvdak'); ?>">
                    <?php wp_nav_menu(array('theme_location'=>'footer','container'=>false,'menu_class'=>'menu','fallback_cb'=>false,'depth'=>1)); ?>
                </nav>
            <?php else : ?>
                <p>آشنایی با خدمات و مطالب پویش</p>
                <?php foreach ( array_slice( pvdak_default_navigation_links(), 0, 3 ) as $link ): ?>
                  <a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <nav class="pvdak-footer-public" aria-label="اطلاعات سایت">
          <?php $account_link = is_user_logged_in() ? pvdak_public_page_url( 'my-pvdak-account' ) : pvdak_public_page_url( 'account-login' );
          if ( $account_link ): ?><a class="pvdak-footer-account-link" href="<?php echo esc_url( $account_link ); ?>"><?php echo is_user_logged_in() ? 'پنل من' : 'ورود به حساب'; ?></a><?php endif; ?>
          <strong class="pvdak-footer-heading">لینک‌های مهم</strong>
          <?php foreach(array('about-us'=>'درباره ما','contact-us'=>'تماس با ما','legal-faq'=>'پرسش‌های متداول','privacy-policy'=>'حریم خصوصی','terms-of-use'=>'شرایط استفاده') as $slug=>$label):
             $url=pvdak_public_page_url($slug); if(!$url)continue; ?>
            <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
          <?php endforeach; ?>
        </nav>
        <div class="pvdak-footer-address">
            <strong class="pvdak-footer-heading">ارتباط و راهنمایی</strong>
            <span>نشانی اینترنتی</span>
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(wp_parse_url(home_url('/'), PHP_URL_HOST)); ?></a>
            <?php $contact_url = pvdak_public_page_url( 'contact-us' ); if ( $contact_url ): ?>
              <a class="pvdak-footer-contact-link" href="<?php echo esc_url( $contact_url ); ?>">تماس با پویش <span aria-hidden="true">←</span></a>
            <?php endif; ?>
            <?php if ( is_page_template( 'page-templates/pvdak-account.php' ) ): ?>
              <form role="search" class="pvdak-profile-footer-search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <label for="pvdak-profile-footer-s">جست‌وجو در راهنمای حقوقی</label>
                <div>
                  <input id="pvdak-profile-footer-s" type="search" name="s" placeholder="موضوع مورد نظر شما..." required>
                  <button type="submit" aria-label="جست‌وجوی موضوع">⌕</button>
                </div>
              </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="pvdak-footer-bottom container">
        <small>&copy; <?php echo esc_html(wp_date('Y')); ?> تمام حقوق محفوظ است.</small>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
