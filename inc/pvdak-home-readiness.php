<?php
/** Admin-only readiness overview for the editable PVDak homepage. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_home_readiness_items() {
    $front = (int) get_option( 'page_on_front' );
    $has_front = 'page' === get_option( 'show_on_front' ) && 'page' === get_post_type( $front );
    $topics_root = function_exists( 'pvdak_legal_topics_root_id' ) ? pvdak_legal_topics_root_id() : 0;
    $topic_pages = $topics_root ? pvdak_legal_topic_pages( $topics_root ) : array();
    $topics_count = count( $topic_pages );
    // D09: published empty samples are intentionally noindex and cannot
    // count as usable legal content. This tests existence, NOT legal approval.
    $topics_with_text = 0;
    foreach ( $topic_pages as $topic_page ) {
        if ( $topic_page instanceof WP_Post &&
             '' !== trim( wp_strip_all_tags( strip_shortcodes( $topic_page->post_content ) ) ) ) {
            $topics_with_text++;
        }
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
    $magazine_count = count( pvdak_home_category_posts( 'magazine_category', 1 ) );
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

    return array(
        array( 'صفحه اصلی وردپرس', $has_front, $has_front ? 'برگه اصلی انتخاب شده' : 'برگه اصلی ثابت را تنظیم کنید', admin_url( 'options-reading.php' ) ),
        array( 'زبان فارسی پیشخوان وردپرس', 'fa_IR' === (string) get_option( 'WPLANG', '' ),
            'زبان سایت را در تنظیمات عمومی روی فارسی قرار دهید؛ زبان شخصی هر مدیر نیز بررسی شود', admin_url( 'options-general.php' ) ),
        array( 'قالب راست‌چین', is_readable( get_template_directory() . '/rtl.css' ),
            'فایل راست‌چین و نسخه قالب را بررسی کنید', admin_url( 'themes.php' ) ),
        array( 'تصویر اصلی بنر', (bool) pvdak_home_image_url( 'hero_image' ), 'یک تصویر شاخص برای بنر انتخاب کنید', admin_url( 'admin.php?page=pvdak-home-content' ) ),
        array( 'برگه مادر موضوعات حقوقی', $topics_root > 0,
            $topics_root ? 'برگه مادر آماده است' : 'در همین صفحه دکمه «آماده‌سازی ساختار محتوایی» را بزنید', admin_url( 'admin.php?page=pvdak-home-content' ) ),
        array( 'لندینگ‌های موضوعات حقوقی', $topics_count > 0, $topics_count . ' برگه فرزند منتشر شده', admin_url( 'edit.php?post_type=page' ) ),
        array( 'حداقل سه لندینگ دارای متن', $topics_with_text >= 3,
            $topics_with_text . ' برگه منتشرشده دارای متن؛ محتوای نمونه/خالی قابل تحویل نیست و تأیید تخصصی مدیر محتوا جداست', admin_url( 'edit.php?post_type=page' ) ),
        array( '۱۷ لندینگ نمونه موضوعی', $sample_total > 0 && $sample_ready === $sample_total,
            $sample_ready . ' از ' . $sample_total . ' لندینگ نمونه منتشرشده (برای تکمیل در همین صفحه دکمه نمونه‌ها را بزنید)', admin_url( 'admin.php?page=pvdak-home-content' ) ),
        array( 'مجله حقوقی', $magazine_term > 0 && $magazine_count > 0, $magazine_count ? 'دارای مقاله منتشرشده' : 'دسته مجله و نوشته منتشرشده لازم است', admin_url( 'edit.php' ) ),
        array( 'اطلاعیه‌های پویش', $notice_term > 0 && $notice_count > 0, $notice_count ? 'دارای اطلاعیه منتشرشده' : 'دسته اطلاعیه و نوشته منتشرشده لازم است', admin_url( 'edit.php' ) ),
        array( 'پرسش‌های متداول', $faq_written > 0, $faq_written . ' پاسخ معتبر از ' . $faq_count . ' برگه منتشرشده', admin_url( 'edit.php?post_type=page' ) ),
        array( 'چهار دسترسی سریع منتشرشده', 4 === $quick_count,
            $quick_count . ' از ۴ برگه معتبر انتخاب شده؛ برگه تکراری یا پیش‌نویس پذیرفته نمی‌شود', admin_url( 'admin.php?page=pvdak-home-content' ) ),
        array( 'پاسخ‌های واقعی در FAQ', $faq_count > 0 && $faq_written === $faq_count,
            $faq_written . ' از ' . $faq_count . ' پاسخ منتشرشده دارای متن است؛ تأیید تخصصی محتوا جداگانه لازم است', admin_url( 'edit.php?post_type=page' ) ),
        array( 'مسیرهای اصلی و قالب برگه‌ها', count( $public_templates ) === $public_ready,
            $public_ready . ' از ' . count( $public_templates ) . ' برگه ورود، پنل، تماس، حریم خصوصی و قوانین با قالب صحیح منتشر شده‌اند', admin_url( 'edit.php?post_type=page' ) ),
        array( 'فهرست اصلی', has_nav_menu( 'primary' ), has_nav_menu( 'primary' ) ? 'فهرست اختصاص داده شده' : 'فهرست سایت را اختصاص دهید', admin_url( 'nav-menus.php' ) ),
        array( 'نصب مستقل PVDak Core', $core_installed,
            $core_installed ? 'نسخه افزونه روی وردپرس شناسایی شد' : 'Cron فقط قالب را منتشر می‌کند؛ نصب افزونه مستقل هنوز انجام نشده', admin_url( 'plugins.php' ) ),
        array( 'هماهنگی نسخه قالب و Core', $core_matches,
            $core_matches ? 'فایل اصلی با بسته قالب برابر است' : 'فایل‌های نصب‌شده را با نسخه مخزن و آزمون کامل مقایسه کنید', admin_url( 'plugins.php' ) ),
        array( 'فعال‌بودن هسته سامانه', $core_active,
            $core_active ? 'افزونه Core فعال است' : 'هسته هنوز در وردپرس فعال نشده', admin_url( 'plugins.php' ) ),
        array( 'نقش‌های پایه کاربران', $roles_ready,
            $roles_ready ? 'چهار نقش پایه حاضرند؛ آزمون چندحسابی همچنان لازم است' : 'نقش‌های مراجعه‌کننده یا متخصص نیازمند بازبینی هستند', admin_url( 'users.php' ) ),
        array( 'صفحه تعمیرات عمومی', $maintenance_on,
            $maintenance_on ? 'بازدیدکنندگان عمومی هنوز صفحه تعمیرات را می‌بینند' : 'تا پایان آزمون‌ها صفحه تعمیرات را فعال نگه دارید', admin_url( 'themes.php' ) ),
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
