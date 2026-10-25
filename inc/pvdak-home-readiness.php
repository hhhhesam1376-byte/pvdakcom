<?php
/** Admin-only readiness overview for the editable PVDak homepage. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_home_readiness_items() {
    $front = (int) get_option( 'page_on_front' );
    $has_front = 'page' === get_option( 'show_on_front' ) && 'page' === get_post_type( $front );
    $topics_root = function_exists( 'pvdak_legal_topics_root_id' ) ? pvdak_legal_topics_root_id() : 0;
    $topic_pages = $topics_root ? pvdak_legal_topic_pages( $topics_root ) : array();
    $topics_count = count( $topic_pages );
    // D09: non-empty text alone is not proof of finished legal content.
    // The exact 17 demo landing messages must NOT pass editorial readiness.
    // WordPress editors keep full control of Pages; this is a read-only check.
    $topics_with_text = 0;
    $topic_placeholders = 0;
    foreach ( $topic_pages as $topic_page ) {
        if ( ! $topic_page instanceof WP_Post ) { continue; }
        $topic_text = trim( wp_strip_all_tags( strip_shortcodes( $topic_page->post_content ) ) );
        $topic_is_sample = false !== strpos( $topic_text, 'موضوع نمونه برای تکمیل ساختار سایت' ) ||
            false !== stripos( $topic_text, 'lorem ipsum' );
        if ( $topic_is_sample || '' === $topic_text ) { $topic_placeholders++; continue; }
        $topic_length = function_exists( 'mb_strlen' ) ?
            mb_strlen( $topic_text, 'UTF-8' ) : strlen( $topic_text );
        if ( $topic_length >= 120 ) { $topics_with_text++; }
    }
    $sample_total = function_exists( 'pvdak_editor_example_legal_topics' ) ?
        count( pvdak_editor_example_legal_topics() ) : 0;
    $sample_ready = 0;
    if ( $topics_root && $sample_total && function_exists( 'pvdak_editor_child_path' ) ) {
        foreach ( pvdak_editor_example_legal_topics() as $slug => $label ) {
            $page = get_page_by_path( pvdak_editor_child_path( $topics_root, $slug ), OBJECT, 'page' );
            if ( $page && 'publish' === $page->post_status &&
                 (int) $page->post_parent === (int) $topics_root ) {
                $sample_ready++;
            }
        }
    }

    $magazine_term = pvdak_home_selected_category( 'magazine_category' );
    // Read at most 12 published posts; an old demo post cannot make the
    // magazine look complete solely because it has status=publish.
    $magazine_posts = pvdak_home_category_posts( 'magazine_category', 12 );
    $magazine_count = count( $magazine_posts );
    $magazine_editorial_count = 0;
    $magazine_sample_count = 0;
    foreach ( $magazine_posts as $magazine_post ) {
        if ( ! $magazine_post instanceof WP_Post ) { continue; }
        $body = trim( wp_strip_all_tags( strip_shortcodes( $magazine_post->post_content ) ) );
        $demo = '1' === (string) get_post_meta( $magazine_post->ID, '_pvdak_demo_blog', true ) ||
            false !== stripos( $body, 'lorem ipsum' ) ||
            'hello-world' === $magazine_post->post_name;
        if ( $demo ) { $magazine_sample_count++; continue; }
        $body_length = function_exists( 'mb_strlen' ) ? mb_strlen( $body, 'UTF-8' ) : strlen( $body );
        if ( $body_length >= 160 ) { $magazine_editorial_count++; }
    }
    $notice_term = pvdak_home_selected_category( 'notice_category' );
    $notice_count = count( pvdak_home_category_posts( 'notice_category', 1 ) );
    // D08: The front page must have exactly four *published* quick Pages.
    // Reuse the same resolver used by front-page.php; never count draft links
    // or invent/fill slots on behalf of the editor.
    $quick_pages = function_exists( 'pvdak_home_quick_pages' ) ? pvdak_home_quick_pages() : array();
    $quick_count = count( $quick_pages );
    // Audit the unfiltered published Pages; the public renderer deliberately
    // omits empty answers, which otherwise made this check always PASS.
    $faq_pages = pvdak_home_faq_pages( true );
    $faq_count = count( $faq_pages );
    $faq_written = 0;
    foreach ( $faq_pages as $faq_page ) {
        if ( $faq_page instanceof WP_Post &&
             '' !== trim( wp_strip_all_tags( strip_shortcodes( $faq_page->post_content ) ) ) ) {
            $faq_written++;
        }
    }
    // Distinguish source delivery, actual plugin installation and safe activation.
    $core_source = get_template_directory() . '/packages/pvdak-core/pvdak-core.php';
    $core_plugin = WP_PLUGIN_DIR . '/pvdak-core/pvdak-core.php';
    $core_installed = is_file( $core_plugin );
    $core_active = function_exists( 'pvdak_core_register_post_types' );
    $core_matches = $core_installed && is_file( $core_source ) &&
        hash_file( 'sha256', $core_plugin ) === hash_file( 'sha256', $core_source );
    $core_safe = $core_active && function_exists( 'pvdak_core_intake_open' ) &&
        function_exists( 'pvdak_core_member_intake_open' ) &&
        ! pvdak_core_intake_open() && ! pvdak_core_member_intake_open();
    // Visibility for administrators only; not a substitute for account-level
    // isolation tests. These baseline roles must be present and non-privileged.
    $roles_ready = $core_active;
    foreach ( array( 'pvdak_client', 'pvdak_lawyer', 'pvdak_trainee', 'pvdak_student' ) as $slug ) {
        $role = get_role( $slug );
        if ( ! $role || ! $role->has_cap( 'read' ) ||
             $role->has_cap( 'edit_pvdak_requests' ) ||
             $role->has_cap( 'edit_pvdak_professionals' ) ) {
            $roles_ready = false;
        }
    }
    $maintenance_on = function_exists( 'pvdak_maintenance_enabled' ) && pvdak_maintenance_enabled();
    // D05: verify that mandatory public routes exist, are published and
    // actually use the expected native template. Do not auto-republish,
    // redirect or silently "fix" Pages edited by the client.
    $public_templates = array(
        'account-login' => 'page-templates/pvdak-login.php',
        'account-register' => 'page-templates/pvdak-register.php',
        'account-recovery' => 'page-templates/pvdak-recovery.php',
        'my-pvdak-account' => 'page-templates/pvdak-account.php',
        'contact-us' => 'page-templates/pvdak-contact.php',
        'privacy-policy' => 'page-templates/pvdak-information.php',
        'terms-of-use' => 'page-templates/pvdak-information.php',
    );
    $public_ready = 0;
    foreach ( $public_templates as $slug => $expected_template ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( $page instanceof WP_Post && 'publish' === $page->post_status &&
             $expected_template === get_page_template_slug( $page->ID ) ) {
            $public_ready++;
        }
    }

    // Work QA D09/D10: front-end demo must not be marked ready while the
    // stock WordPress title, policy text and sample posts remain published.
    $site_title = trim( (string) get_option( 'blogname', '' ) );
    $site_title_ready = '' !== $site_title &&
        ! in_array( $site_title, array( 'Bloggers Unite', 'WordPress', 'My WordPress Blog' ), true );
    $privacy_page = get_page_by_path( 'privacy-policy', OBJECT, 'page' );
    $privacy_text = $privacy_page instanceof WP_Post ?
        trim( wp_strip_all_tags( strip_shortcodes( $privacy_page->post_content ) ) ) : '';
    $privacy_is_template = false !== stripos( $privacy_text, 'Suggested text' ) ||
        false !== stripos( $privacy_text, 'Who we are' ) ||
        false !== strpos( $privacy_text, 'این متن اولیه باید پیش از شروع رسمی خدمات بازبینی شود' );
    $privacy_ready = $privacy_page instanceof WP_Post &&
        'publish' === $privacy_page->post_status &&
        '1' !== (string) get_post_meta( $privacy_page->ID, '_pvdak_provisional_policy', true ) &&
        ! $privacy_is_template && '' !== $privacy_text;

    $terms_page = get_page_by_path( 'terms-of-use', OBJECT, 'page' );
    $terms_text = $terms_page instanceof WP_Post ?
        trim( wp_strip_all_tags( strip_shortcodes( $terms_page->post_content ) ) ) : '';
    $terms_is_draft = false !== strpos( $terms_text, 'پیش‌نویس شرایط' ) ||
        false !== strpos( $terms_text, 'این متن اولیه شرایط نهایی خدمات محسوب نمی‌شود' );
    $terms_ready = $terms_page instanceof WP_Post &&
        'publish' === $terms_page->post_status &&
        '1' !== (string) get_post_meta( $terms_page->ID, '_pvdak_provisional_policy', true ) &&
        ! $terms_is_draft && '' !== $terms_text;
    $hello_post = get_page_by_path( 'hello-world', OBJECT, 'post' );
    $hello_sample_published = $hello_post instanceof WP_Post &&
        'publish' === $hello_post->post_status &&
        in_array( $hello_post->post_title, array( 'Hello world!', 'Hello world' ), true );

    return array(
        array( 'صفحه اصلی وردپرس', $has_front, $has_front ? 'برگه اصلی انتخاب شده' : 'برگه اصلی ثابت را تنظیم کنید', admin_url( 'options-reading.php' ) ),
        array( 'عنوان سایت به جای نام پیش‌فرض وردپرس', $site_title_ready,
            $site_title_ready ? 'عنوان فعلی: ' . $site_title . '؛ نام تجاری را با کارفرما تطبیق دهید' :
                'عنوان فعلی «' . $site_title . '» است؛ از تنظیمات عمومی به PVDak تغییر دهید', admin_url( 'options-general.php' ) ),
        array( 'حریم خصوصی اختصاصی منتشرشده', $privacy_ready,
            $privacy_ready ? 'متن پیش‌فرض تشخیص داده نشد؛ تأیید حقوقی همچنان با کارفرماست' :
                'متن پیش‌فرض/اولیه یا برگه نامعتبر است؛ پس از بررسی حقوقی در خود برگه جایگزین شود', admin_url( 'edit.php?post_type=page' ) ),
        array( 'شرایط استفاده نهایی منتشرشده', $terms_ready,
            $terms_ready ? 'پیش‌نویس شناخته‌شده تشخیص داده نشد؛ تأیید حقوقی جداگانه لازم است' :
                'پیش‌نویس شرایط یا برگه نامعتبر است؛ متن مصوب را منتشر کنید', admin_url( 'edit.php?post_type=page' ) ),
        array( 'حذف نمونه پیش‌فرض Hello world', ! $hello_sample_published,
            $hello_sample_published ? 'نوشته Hello world هنوز عمومی است؛ در نوشته‌ها اصلاح یا پیش‌نویس شود' :
                'نوشته نمونه پیش‌فرض منتشرشده‌ای با عنوان اولیه تشخیص داده نشد', admin_url( 'edit.php' ) ),
        array( 'زبان فارسی سایت وردپرس', 'fa_IR' === get_locale(),
            'زبان سایت را در تنظیمات عمومی روی فارسی قرار دهید؛ زبان شخصی هر مدیر نیز بررسی شود', admin_url( 'options-general.php' ) ),
        array( 'قالب راست‌چین', is_readable( get_template_directory() . '/rtl.css' ),
            'فایل راست‌چین و نسخه قالب را بررسی کنید', admin_url( 'themes.php' ) ),
        array( 'تصویر اصلی بنر', (bool) pvdak_home_image_url( 'hero_image' ), 'یک تصویر شاخص برای بنر انتخاب کنید', admin_url( 'admin.php?page=pvdak-home-content' ) ),
        array( 'برگه مادر موضوعات حقوقی', $topics_root > 0,
            $topics_root ? 'برگه مادر آماده است' : 'در همین صفحه دکمه «آماده‌سازی ساختار محتوایی» را بزنید', admin_url( 'admin.php?page=pvdak-home-content' ) ),
        array( 'لندینگ‌های موضوعات حقوقی', $topics_count > 0, $topics_count . ' برگه فرزند منتشر شده', admin_url( 'edit.php?post_type=page' ) ),
        array( 'حداقل سه لندینگ دارای متن', $topics_with_text >= 3,
            $topics_with_text . ' لندینگ با حداقل ۱۲۰ نویسه و بدون نشانه متن نمونه؛ محتوای واقعی نیازمند تأیید حقوقی است', admin_url( 'edit.php?post_type=page' ) ),
        array( 'لندینگ‌های خالی یا نمونه', 0 === $topic_placeholders,
            $topic_placeholders . ' برگه خالی یا دارای متن نمونه شناسایی شد؛ در ویرایشگر خود برگه تکمیل کنید', admin_url( 'edit.php?post_type=page' ) ),
        array( '۱۷ لندینگ نمونه موضوعی', $sample_total > 0 && $sample_ready === $sample_total,
            $sample_ready . ' از ' . $sample_total . ' لندینگ نمونه منتشرشده (برای تکمیل در همین صفحه دکمه نمونه‌ها را بزنید)', admin_url( 'admin.php?page=pvdak-home-content' ) ),
        array( 'مجله حقوقی', $magazine_term > 0 && $magazine_editorial_count > 0,
            $magazine_editorial_count . ' مقاله غیرنمونه از ' . $magazine_count . ' نوشته بررسی‌شده؛ صرف انتشار مطلب نمونه کافی نیست', admin_url( 'edit.php' ) ),
        array( 'مقالات آزمایشی مجله', 0 === $magazine_sample_count,
            $magazine_sample_count . ' مقاله نمونه در حداکثر ۱۲ نوشته اخیر تشخیص داده شد؛ محتوای واقعی را جایگزین کنید', admin_url( 'edit.php' ) ),
        array( 'اطلاعیه‌های PVDak', $notice_term > 0 && $notice_count > 0, $notice_count ? 'دارای اطلاعیه منتشرشده' : 'دسته اطلاعیه و نوشته منتشرشده لازم است', admin_url( 'edit.php' ) ),
        array( 'پرسش‌های متداول', $faq_written > 0, $faq_written . ' پاسخ معتبر از ' . $faq_count . ' برگه منتشرشده', admin_url( 'edit.php?post_type=page' ) ),
        array( 'چهار دسترسی سریع منتشرشده', 4 === $quick_count,
            $quick_count . ' از ۴ برگه معتبر انتخاب شده؛ برگه تکراری یا پیش‌نویس پذیرفته نمی‌شود', admin_url( 'admin.php?page=pvdak-home-content' ) ),
        array( 'پاسخ‌های واقعی در FAQ', $faq_count > 0 && $faq_written === $faq_count,
            $faq_written . ' از ' . $faq_count . ' پاسخ منتشرشده دارای متن است؛ تأیید تخصصی محتوا جداگانه لازم است', admin_url( 'edit.php?post_type=page' ) ),
        array( 'مسیرهای اصلی و قالب برگه‌ها', count( $public_templates ) === $public_ready,
            $public_ready . ' از ' . count( $public_templates ) . ' برگه ورود، ثبت‌نام، بازیابی رمز، پنل، تماس، حریم خصوصی و قوانین با قالب صحیح منتشر شده‌اند', admin_url( 'edit.php?post_type=page' ) ),
        array( 'فهرست اصلی', has_nav_menu( 'primary' ), has_nav_menu( 'primary' ) ? 'فهرست اختصاص داده شده' : 'فهرست سایت را اختصاص دهید', admin_url( 'nav-menus.php' ) ),
        array( 'نصب مستقل PVDak Core', $core_installed,
            $core_installed ? 'نسخه افزونه روی وردپرس شناسایی شد' : 'Cron فقط قالب را منتشر می‌کند؛ نصب افزونه مستقل هنوز انجام نشده', admin_url( 'plugins.php' ) ),
        array( 'هماهنگی نسخه قالب و Core', $core_matches,
            $core_matches ? 'فایل اصلی با بسته قالب برابر است' : 'فایل‌های نصب‌شده را با نسخه مخزن و آزمون کامل مقایسه کنید', admin_url( 'plugins.php' ) ),
        array( 'فعال‌بودن هسته سامانه', $core_active,
            $core_active ? 'افزونه Core فعال است' : 'هسته هنوز در وردپرس فعال نشده', admin_url( 'plugins.php' ) ),
        array( 'نقش‌های پایه کاربران', $roles_ready,
            $roles_ready ? 'چهار نقش پایه حاضرند؛ آزمون چندحسابی همچنان لازم است' : 'نقش‌های مراجعه‌کننده یا متخصص نیازمند بازبینی هستند', admin_url( 'users.php' ) ),
        array( 'دسترسی نمایشی بازدیدکننده بدون تعمیرات', ! $maintenance_on,
            $maintenance_on ? 'بازدیدکنندگان هنوز صفحه تعمیرات را می‌بینند؛ برای بازبینی خاموش کنید'
                : 'بازدیدکنندگان عادی نسخه نمایشی سایت را می‌بینند؛ فعال بودن ثبت‌نام و پذیرش پرونده جداست',
            admin_url( 'options-general.php' ) ),
        array( 'پذیرش داده‌های حقوقی خاموش', $core_safe,
            $core_safe ? 'هر دو فرم درخواست و همکاری غیرفعال‌اند' : 'قبل از تست واقعی، پذیرش اطلاعات باید خاموش بماند', $core_active ? admin_url( 'options-general.php?page=pvdak-core' ) : admin_url( 'plugins.php' ) ),
    );
}

function pvdak_home_readiness_panel() {
    if ( ! current_user_can( 'edit_pages' ) ) { return; }
    $items = pvdak_home_readiness_items();
    $done = 0;
    foreach ( $items as $item ) { if ( $item[1] ) { $done++; } }
    echo '<div class="postbox" style="margin:18px 0;max-width:1100px">';
    echo '<div class="inside" style="padding:14px 20px">';
    echo '<h2>آمادگی بخش‌های سایت <small style="font-weight:normal">(' . esc_html( $done ) . ' از ' . esc_html( count( $items ) ) . ')</small></h2>';
    echo '<p>این گزارش فقط وضعیت تنظیمات و وجود محتوا را نشان می‌دهد؛ به معنی تست امنیت یا تحویل نهایی نیست.</p>';
    echo '<table class="widefat striped"><thead><tr><th>قسمت</th><th>وضعیت</th><th>اقدام</th></tr></thead><tbody>';
    foreach ( $items as $item ) {
        echo '<tr><th scope="row">' . esc_html( $item[0] ) . '</th>';
        echo '<td>' . ( $item[1] ? '<span style="color:#16734c">✓ برقرار</span>' : '<span style="color:#925600">نیازمند بررسی</span>' ) . '</td>';
        echo '<td><a href="' . esc_url( $item[3] ) . '">' . esc_html( $item[2] ) . '</a></td></tr>';
    }
    echo '</tbody></table></div></div>';
}
