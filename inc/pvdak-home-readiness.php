<?php
/** Admin-only readiness overview for the editable PVDak homepage. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_home_readiness_items() {
    $front = (int) get_option( 'page_on_front' );
    $has_front = 'page' === get_option( 'show_on_front' ) && 'page' === get_post_type( $front );
    $topics_root = function_exists( 'pvdak_legal_topics_root_id' ) ? pvdak_legal_topics_root_id() : 0;
    $topics_count = $topics_root ? count( pvdak_legal_topic_pages( $topics_root ) ) : 0;

    $magazine_term = pvdak_home_selected_category( 'magazine_category' );
    $magazine_count = count( pvdak_home_category_posts( 'magazine_category', 1 ) );
    $notice_term = pvdak_home_selected_category( 'notice_category' );
    $notice_count = count( pvdak_home_category_posts( 'notice_category', 1 ) );
    $faq_count = count( pvdak_home_faq_pages() );
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

    return array(
        array( 'صفحه اصلی وردپرس', $has_front, $has_front ? 'برگه اصلی انتخاب شده' : 'برگه اصلی ثابت را تنظیم کنید', admin_url( 'options-reading.php' ) ),
        array( 'زبان فارسی پیشخوان وردپرس', 'fa_IR' === (string) get_option( 'WPLANG', '' ),
            'زبان سایت را در تنظیمات عمومی روی فارسی قرار دهید؛ زبان شخصی هر مدیر نیز بررسی شود', admin_url( 'options-general.php' ) ),
        array( 'قالب راست‌چین', is_readable( get_template_directory() . '/rtl.css' ),
            'فایل راست‌چین و نسخه قالب را بررسی کنید', admin_url( 'themes.php' ) ),
        array( 'تصویر اصلی بنر', (bool) pvdak_home_image_url( 'hero_image' ), 'یک تصویر شاخص برای بنر انتخاب کنید', admin_url( 'admin.php?page=pvdak-home-content' ) ),
        array( 'لندینگ‌های موضوعات حقوقی', $topics_count > 0, $topics_count . ' برگه فرزند منتشر شده', admin_url( 'edit.php?post_type=page' ) ),
        array( 'مجله حقوقی', $magazine_term > 0 && $magazine_count > 0, $magazine_count ? 'دارای مقاله منتشرشده' : 'دسته مجله و نوشته منتشرشده لازم است', admin_url( 'edit.php' ) ),
        array( 'اطلاعیه‌های پویش', $notice_term > 0 && $notice_count > 0, $notice_count ? 'دارای اطلاعیه منتشرشده' : 'دسته اطلاعیه و نوشته منتشرشده لازم است', admin_url( 'edit.php' ) ),
        array( 'پرسش‌های متداول', $faq_count > 0, $faq_count . ' برگه پرسش/پاسخ منتشر شده', admin_url( 'edit.php?post_type=page' ) ),
        array( 'فهرست اصلی', has_nav_menu( 'primary' ), has_nav_menu( 'primary' ) ? 'فهرست اختصاص داده شده' : 'فهرست سایت را اختصاص دهید', admin_url( 'nav-menus.php' ) ),
        array( 'نصب مستقل PVDak Core', $core_installed,
            $core_installed ? 'نسخه افزونه روی وردپرس شناسایی شد' : 'Cron فقط قالب را منتشر می‌کند؛ نصب افزونه مستقل هنوز انجام نشده', admin_url( 'plugins.php' ) ),
        array( 'هماهنگی نسخه قالب و Core', $core_matches,
            $core_matches ? 'فایل اصلی با بسته قالب برابر است' : 'فایل‌های نصب‌شده را با نسخه مخزن و آزمون کامل مقایسه کنید', admin_url( 'plugins.php' ) ),
        array( 'فعال‌بودن هسته سامانه', $core_active,
            $core_active ? 'افزونه Core فعال است' : 'هسته هنوز در وردپرس فعال نشده', admin_url( 'plugins.php' ) ),
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
        echo '<td>' . ( $item[1] ? '<span style="color:#16734c">✓ آماده از نظر محتوا</span>' : '<span style="color:#925600">نیازمند تنظیم</span>' ) . '</td>';
        echo '<td><a href="' . esc_url( $item[3] ) . '">' . esc_html( $item[2] ) . '</a></td></tr>';
    }
    echo '</tbody></table></div></div>';
}
