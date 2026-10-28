<?php
/**
 * Staff-only, READ-ONLY prelaunch review for PVDak.
 * Never creates pages/users, changes Core settings, or reads case contents.
 */
if (!defined('ABSPATH'))exit;

function pvdak_readiness_menu() {
    add_management_page('آمادگی راه‌اندازی PVDak','آمادگی PVDak',
        'manage_options','pvdak-readiness','pvdak_readiness_render');
}
/**
 * A single starting point for client-facing editors. The technical readiness
 * review stays at its existing Tools URL to preserve bookmarked admin links.
 * Register the parent ahead of the ACF submenu (priority 10).
 */
function pvdak_management_menu() {
    add_menu_page(
        'مدیریت سایت PVDak',
        'مدیریت PVDak',
        'edit_pages',
        'pvdak-management',
        'pvdak_management_render',
        'dashicons-admin-site-alt3',
        3
    );
    add_submenu_page(
        'pvdak-management',
        'نمای کلی مدیریت',
        'شروع و راهنما',
        'edit_pages',
        'pvdak-management',
        'pvdak_management_render'
    );
}
add_action('admin_menu','pvdak_management_menu',8);
add_action('admin_menu','pvdak_readiness_menu');

function pvdak_management_links() {
    $links = array(
        array('ویرایش صفحه اصلی', 'مدیریت متن‌ها و تصاویر صفحه اصلی', 'edit_pages', 'admin.php?page=pvdak-home-content'),
        array('برگه‌ها و موضوعات حقوقی', 'صفحات مادر، فرزند و توضیحات حقوقی سایت', 'edit_pages', 'edit.php?post_type=page'),
        array('مقالات و اطلاعیه‌ها', 'محتوای مجله و خبرهای وب‌سایت', 'edit_posts', 'edit.php'),
        array('تصاویر و فایل‌ها', 'کتابخانه رسانه‌های وردپرس', 'upload_files', 'upload.php'),
        array('فهرست‌های سایت', 'تنظیم منوی اصلی و فوتر', 'edit_theme_options', 'nav-menus.php'),
        array('کاربران', 'مشاهده و مدیریت حساب‌های سایت', 'list_users', 'users.php'),
        array('تیکت‌های پشتیبانی', 'مشاهده و پاسخ به درخواست‌های پشتیبانی', 'manage_options', 'edit.php?post_type=pvdak_support_ticket'),
        array('درخواست‌های حقوقی', 'صف رسیدگی به درخواست‌ها؛ بدون فعال‌کردن پذیرش عمومی', 'edit_pvdak_requests', 'edit.php?post_type=pvdak_request'),
        array('صف بررسی درخواست‌ها', 'نمای عملیاتی درخواست‌های خصوصی، بدون نمایش محتوای پرونده', 'edit_pvdak_requests', 'edit.php?post_type=pvdak_request&page=pvdak-request-triage'),
        array('درخواست‌های منتظر بررسی', 'درخواست‌های ثبت‌شده یا در حال بررسی، قدیمی‌ترین ابتدا', 'edit_pvdak_requests', 'edit.php?post_type=pvdak_request&pvdak_filter_queue=pending&pvdak_case_order=oldest'),
        array('پرونده‌های عضویت متخصصان', 'بررسی پروفایل‌های خصوصی و مدارک در ابزار اصلی Core', 'edit_pvdak_professionals', 'edit.php?post_type=pvdak_professional'),
        array('عضویت‌های منتظر بررسی', 'پرونده‌های همکاری ثبت‌شده و نیازمند رسیدگی', 'edit_pvdak_professionals', 'edit.php?post_type=pvdak_professional&pvdak_state_filter=pending'),
        array('برگه‌های پیش‌نویس', 'مرور صفحات آماده‌نشده پیش از انتشار', 'edit_pages', 'edit.php?post_type=page&post_status=draft'),
        array('مقالات پیش‌نویس', 'مرور نوشته‌های منتشرنشده مجله', 'edit_posts', 'edit.php?post_status=draft'),
        array('دیدگاه‌ها و گفت‌وگوها', 'مدیریت و تأیید دیدگاه‌ها و گفت‌وگوهای انجمن، در صورت استفاده', 'moderate_comments', 'edit-comments.php'),
        array('صف انتظار تأیید', 'بررسی پیام‌های تازه پیش از نمایش عمومی', 'moderate_comments', 'edit-comments.php?comment_status=moderated'),
        array('تنظیمات اصلی', 'تنظیم ثبت‌نام و حالت تعمیرات سایت', 'manage_options', 'options-general.php'),
        array('تنظیمات خدمات حقوقی', 'کنترل‌های حساس پذیرش، عضویت و اعلان‌ها؛ فقط مدیر سایت', 'manage_options', 'options-general.php?page=pvdak-core'),
        array('بررسی پیش از راه‌اندازی', 'وضعیت فنی و حقوقی فقط برای مدیر سایت', 'manage_options', 'tools.php?page=pvdak-readiness'),
    );
    return $links;
}

/**
 * Only advertise routes that exist for this installation and current role.
 * The underlying WordPress and Core permission checks remain authoritative.
 */
function pvdak_management_link_available( $link ) {
    if ( ! current_user_can( $link[2] ) ) { return false; }
    // The homepage editor must not advertise an edit route for a page this
    // staff member cannot edit, even if their role can edit other pages.
    if ( 'admin.php?page=pvdak-home-content' === $link[3] ) {
        if ( ! function_exists( 'pvdak_home_admin_landing' ) ) { return false; }
        if ( function_exists( 'pvdak_home_page_id' ) ) {
            $home_id = pvdak_home_page_id();
            // Only an administrator can initialize a missing front page.
            if ( ! $home_id ) { return current_user_can( 'manage_options' ); }
            if ( ! current_user_can( 'edit_post', $home_id ) ) { return false; }
        }
    }
    // Never advertise a Core settings page when its plugin is not active.
    if ( 'options-general.php?page=pvdak-core' === $link[3] &&
         ( ! defined( 'PVDAK_CORE_VERSION' ) || ! function_exists( 'pvdak_core_intake_open' ) ) ) { return false; }
    if ( 0 === strpos( $link[3], 'edit.php?post_type=' ) ) {
        $type = explode( '&', substr( $link[3], strlen( 'edit.php?post_type=' ) ), 2 )[0];
        if ( ! post_type_exists( $type ) ) { return false; }
        $object = get_post_type_object( $type );
        if ( ! $object || ! isset( $object->cap->edit_posts ) ||
             ! current_user_can( $object->cap->edit_posts ) ) { return false; }
    }
    // Deep links require their active Core query handlers, not merely a CPT.
    if ( false !== strpos( $link[3], 'page=pvdak-request-triage' ) &&
         ! function_exists( 'pvdak_core_triage_page' ) ) { return false; }
    if ( false !== strpos( $link[3], 'pvdak_filter_queue=' ) &&
         ! function_exists( 'pvdak_core_triage_filter_query' ) ) { return false; }
    if ( false !== strpos( $link[3], 'pvdak_state_filter=' ) &&
         ! function_exists( 'pvdak_core_prof_admin_query' ) ) { return false; }
    return true;
}

/**
 * A count-only moderation reminder: never fetch comment bodies, authors,
 * email addresses or case data into the employer dashboard.
 */
function pvdak_management_pending_comments() {
    if ( ! current_user_can( 'moderate_comments' ) ) { return 0; }
    $counts = wp_count_comments();
    if ( ! is_object( $counts ) || ! isset( $counts->moderated ) ) { return 0; }
    $value = $counts->moderated;
    if ( ! is_int( $value ) && ! is_string( $value ) ) { return 0; }
    $raw = (string) $value;
    // Do not turn a negative/malformed count into a misleading pending badge.
    if ( ! preg_match( '/^(?:0|[1-9][0-9]{0,9})$/D', $raw ) ||
         (float) $raw > 2147483647 ) { return 0; }
    return (int) $raw;
}

function pvdak_management_render_links() {
    $sections = array(
        'محتوا و ظاهر سایت' => array(
            'ویرایش صفحه اصلی', 'برگه‌ها و موضوعات حقوقی', 'مقالات و اطلاعیه‌ها',
            'تصاویر و فایل‌ها', 'فهرست‌های سایت',
        ),
        'درخواست‌ها و گفت‌وگوها' => array(
            'درخواست‌های حقوقی', 'پرونده‌های عضویت متخصصان', 'تیکت‌های پشتیبانی',
            'صف انتظار تأیید', 'دیدگاه‌ها و گفت‌وگوها',
        ),
        'حساب‌ها و تنظیمات' => array(
            'کاربران', 'تنظیمات اصلی', 'تنظیمات خدمات حقوقی', 'بررسی پیش از راه‌اندازی',
        ),
    );
    $links = pvdak_management_links();
    foreach ( $sections as $heading => $names ) {
        $cards = array();
        foreach ( $links as $link ) {
            if ( ! in_array( $link[0], $names, true ) ||
                 ! pvdak_management_link_available( $link ) ) { continue; }
            $label = $link[0];
            $description = $link[1];
            if ( 'ویرایش صفحه اصلی' === $label && function_exists( 'pvdak_home_page_id' ) &&
                 ! pvdak_home_page_id() ) {
                $label = 'راه‌اندازی صفحه اصلی';
                $description = 'اتصال برگه اصلی به وردپرس؛ فقط برای مدیر سایت';
            }
            if ( 'صف انتظار تأیید' === $label ) {
                $pending = pvdak_management_pending_comments();
                if ( $pending > 0 ) { $label .= ' (' . number_format_i18n( $pending ) . ')'; }
            }
            $cards[] = '<a style="display:block;padding:18px;background:#fff;border:1px solid #dcdcde;border-radius:8px;text-decoration:none" href="' .
                esc_url( admin_url( $link[3] ) ) . '"><strong style="display:block;font-size:15px;margin-bottom:8px">' .
                esc_html( $label ) . '</strong><span style="color:#50575e">' . esc_html( $description ) . '</span></a>';
        }
        if ( ! $cards ) { continue; }
        echo '<section aria-label="' . esc_attr( $heading ) . '"><h2 style="margin-top:26px">' .
            esc_html( $heading ) . '</h2>';
        echo '<div class="pvdak-management-links" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,245px),1fr));gap:12px;max-width:1050px">';
        echo implode( '', $cards ); // Every card is built from escaped, fixed admin routes.
        echo '</div></section>';
    }
}

/**
 * A short, capability-aware daily route for the employer. The destinations
 * come from the same allowlisted links as the cards below; no case contents,
 * comment identities, or user records are loaded here.
 */
function pvdak_management_daily_steps() {
    $available = array();
    foreach ( pvdak_management_links() as $link ) {
        if ( pvdak_management_link_available( $link ) ) {
            $available[ $link[3] ] = $link;
        }
    }
    $steps = array(
        array( 'محتوای صفحه اصلی و پیش‌نویس‌ها را بازبینی کنید', array(
            'admin.php?page=pvdak-home-content', 'edit.php?post_type=page&post_status=draft',
            'edit.php?post_status=draft',
        ) ),
        array( 'صف درخواست‌ها، عضویت‌ها و پشتیبانی را پیگیری کنید', array(
            'edit.php?post_type=pvdak_request&page=pvdak-request-triage',
            'edit.php?post_type=pvdak_request&pvdak_filter_queue=pending&pvdak_case_order=oldest',
            'edit.php?post_type=pvdak_professional&pvdak_state_filter=pending',
            'edit.php?post_type=pvdak_support_ticket',
        ) ),
        array( 'گفت‌وگوها و پیام‌های منتظر تأیید را بررسی کنید', array(
            'edit-comments.php?comment_status=moderated',
            'edit-comments.php',
        ) ),
        array( 'پیش از ارائه عمومی، وضعیت خدمات را بررسی کنید', array(
            'tools.php?page=pvdak-readiness', 'options-general.php',
            'options-general.php?page=pvdak-core',
        ) ),
    );
    $items = array();
    foreach ( $steps as $step ) {
        $destinations = array();
        foreach ( $step[1] as $route ) {
            if ( ! isset( $available[ $route ] ) ) { continue; }
            $destinations[] = '<a href="' . esc_url( admin_url( $route ) ) . '">' .
                esc_html( $available[ $route ][0] ) . '</a>';
        }
        // Both legal requests and support (and both moderation routes) must
        // remain visible when permitted; the first link must not hide the second.
        if ( $destinations ) {
            $items[] = '<li><span>' . esc_html( $step[0] ) . '</span> — ' .
                implode( '، ', $destinations ) . '</li>';
        }
    }
    if ( $items ) {
        echo '<section aria-label="راهنمای کارهای روزمره"><h2>از کجا شروع کنم؟</h2>';
        echo '<ol style="list-style:decimal;padding-right:22px;line-height:2">' .
            implode( '', $items ) . '</ol></section>';
    }
}

/**
 * Interpret the administrator-facing notification switch without casting
 * unexpected option values (arrays/objects) to strings or issuing warnings.
 * This is a read-only status; the Core send-time gate remains authoritative.
 */
function pvdak_management_switch_enabled( $value ) {
    return in_array( $value, array( '1', 1, true ), true );
}

function pvdak_management_render() {
    if (!current_user_can('edit_pages')) {
        wp_die('دسترسی مجاز نیست.','',array('response'=>403));
    }
    echo '<div class="wrap"><h1>مدیریت سایت PVDak</h1>';
    echo '<p>از اینجا کارهای روزمره سایت را انجام دهید. هر بخش مستقیماً به ابزار اصلی وردپرس متصل است و اطلاعات شما را جابه‌جا نمی‌کند.</p>';
    echo '<p><a class="button" href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener noreferrer">مشاهده سایت</a></p>';
    echo '<p class="description">ابتدا محتوای سایت را ویرایش کنید، سپس درخواست‌ها و گفت‌وگوها را بررسی کنید. بخش‌هایی که نصب یا مجوز لازم ندارند در این راهنما نمایش داده نمی‌شوند؛ دسترسی مستقیم و تنظیمات امنیتی وردپرس تغییری نمی‌کنند.</p>';
    pvdak_management_daily_steps();
    pvdak_management_render_links();
    if (current_user_can('manage_options')) {
        $front_state = pvdak_readiness_front_page_snapshot();
        if ( 'ready' !== $front_state ) {
            echo '<div class="notice notice-warning inline"><p><strong>تنظیم صفحه اصلی:</strong> ' .
                esc_html( pvdak_readiness_front_page_status_label( $front_state ) ) .
                ' — <a href="' . esc_url( admin_url( 'options-reading.php' ) ) .
                '">بررسی تنظیمات خواندن</a>. هیچ تنظیمی از این راهنما تغییر نمی‌کند.</p></div>';
        }
        $maintenance = function_exists('pvdak_maintenance_enabled') && pvdak_maintenance_enabled();
        $signup = function_exists('pvdak_registration_open') && pvdak_registration_open();
        $core_available = defined( 'PVDAK_CORE_VERSION' ) && function_exists( 'pvdak_core_intake_open' );
        $requests = $core_available && pvdak_core_intake_open();
        $members = $core_available && function_exists( 'pvdak_core_member_intake_open' ) && pvdak_core_member_intake_open();
        $directory = $core_available && function_exists( 'pvdak_core_directory_enabled' ) && pvdak_core_directory_enabled();
        $notifications = $core_available && pvdak_management_switch_enabled( get_option( 'pvdak_core_email_notifications_enabled', '0' ) );
        echo '<h2 style="margin-top:30px">وضعیت دسترسی‌های عمومی</h2>';
        echo '<p>این بخش فقط وضعیت فعلی را نشان می‌دهد؛ هیچ خدمتی را به‌صورت خودکار فعال نمی‌کند.</p><ul style="list-style:disc;padding-right:20px">';
        echo '<li>حالت تعمیرات: <strong>' . ($maintenance ? 'فعال' : 'غیرفعال') . '</strong></li>';
        echo '<li>ثبت‌نام عمومی: <strong>' . ($signup ? 'فعال' : 'بسته') . '</strong></li>';
        echo '<li>پذیرش درخواست حقوقی: <strong>' . ( ! $core_available ? 'افزونه Core فعال نیست' : ( $requests ? 'فعال' : 'بسته' ) ) . '</strong></li>';
        echo '<li>پذیرش همکاری متخصصان: <strong>' . ( ! $core_available ? 'افزونه Core فعال نیست' : ( $members ? 'فعال' : 'بسته' ) ) . '</strong></li>';
        echo '<li>فهرست عمومی متخصصان: <strong>' . ( ! $core_available ? 'افزونه Core فعال نیست' : ( $directory ? 'کلید فعال است؛ رضایت فردی لازم است' : 'بسته' ) ) . '</strong></li>';
        echo '<li>اعلان ایمیلی: <strong>' . ( ! $core_available ? 'افزونه Core فعال نیست' : ( $notifications ? 'کلید فعال است؛ رضایت فردی لازم است' : 'خاموش' ) ) . '</strong></li>';
        echo '</ul><p>پیش از فعال‌سازی خدمات جدید، مقررات و تأییدهای لازم را بررسی کنید.</p>';
    }
    echo '</div>';
}

/**
 * Keep WordPress's native Comments menu visible to every role already allowed
 * to moderate comments. The PVDak management hub remains an additional shortcut,
 * not a replacement for WordPress moderation or its pending-count badge.
 */

function pvdak_management_dashboard_setup() {
    if (!current_user_can('edit_pages')) { return; }
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    // Activity includes WordPress's native pending-comment moderation.
    // Keep it for reviewers; only declutter it for non-moderating editors.
    if ( ! current_user_can( 'moderate_comments' ) ) {
        remove_meta_box('dashboard_activity', 'dashboard', 'normal');
    }
    wp_add_dashboard_widget('pvdak_management_start', 'راهنمای مدیریت PVDak', 'pvdak_management_dashboard_widget');
}
add_action('wp_dashboard_setup','pvdak_management_dashboard_setup',50);

function pvdak_management_dashboard_widget() {
    echo '<p>برای مدیریت سایت از مسیرهای اصلی زیر استفاده کنید. امکانات حقوقی تا دریافت مجوز، خودکار فعال نمی‌شوند.</p>';
    echo '<p><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=pvdak-management')) . '">ورود به مدیریت PVDak</a>';
    $links = pvdak_management_links();
    if ( pvdak_management_link_available( $links[0] ) ) {
        $home_label = function_exists( 'pvdak_home_page_id' ) && ! pvdak_home_page_id() ?
            'راه‌اندازی صفحه اصلی' : 'ویرایش صفحه اصلی';
        echo ' <a class="button" href="' . esc_url(admin_url('admin.php?page=pvdak-home-content')) . '">' . esc_html( $home_label ) . '</a>';
    }
    echo '</p>';
    if (current_user_can('moderate_comments')) {
        $pending = pvdak_management_pending_comments();
        $queue_label = $pending > 0 ? 'بررسی ' . number_format_i18n( $pending ) . ' پیام منتظر تأیید' : 'بررسی پیام‌های منتظر تأیید';
        echo '<p><a href="' . esc_url(admin_url('edit-comments.php?comment_status=moderated')) . '">' . esc_html( $queue_label ) . '</a></p>';
    }
    // Use the same fixed, capability-checked destinations as the management hub.
    // Never query case, ticket, professional or comment contents in this widget.
    $queue_routes = array(
        'edit.php?post_type=pvdak_request&page=pvdak-request-triage',
        'edit.php?post_type=pvdak_professional&pvdak_state_filter=pending',
        'edit.php?post_type=pvdak_support_ticket',
    );
    $queue_links = array();
    foreach ( $links as $link ) {
        if ( in_array( $link[3], $queue_routes, true ) &&
             pvdak_management_link_available( $link ) ) {
            $queue_links[] = '<a href="' . esc_url( admin_url( $link[3] ) ) . '">' .
                esc_html( $link[0] ) . '</a>';
        }
    }
    if ( $queue_links ) {
        echo '<p><strong>پیگیری کارها:</strong> ' . implode( ' | ', $queue_links ) . '</p>';
    }
    if (current_user_can('manage_options')) {
        echo '<p><a href="' . esc_url(admin_url('tools.php?page=pvdak-readiness')) . '">بررسی وضعیت راه‌اندازی</a></p>';
    }
}

/**
 * Classify a required Page without reading its body or changing its status.
 * Any unexpected provisional-policy metadata is treated as needing review,
 * rather than silently certifying the Page as editorially complete.
 */
function pvdak_readiness_page_classify( $exists, $status, $provisional, $template_matches = true, $template_available = true ) {
    if ( ! $exists ) { return 'missing'; }
    if ( ! is_string( $status ) || 'publish' !== $status ) { return 'unpublished'; }
    // A matching Page template slug is not enough: its PHP file may be
    // missing from the deployed theme, or the expected seed may be absent.
    // Never count such a public account route as ready.
    if ( true !== $template_available ) { return 'template_unavailable'; }
    // A published Page with the wrong template is not a working login,
    // recovery or account route. Detect this before provisional text status.
    if ( true !== $template_matches ) { return 'wrong_template'; }
    if ( ! in_array( $provisional, array( '', '0', 0, false, null ), true ) ) {
        return 'provisional';
    }
    return 'published';
}

/** Human-readable editorial status; never confuse publication with approval. */
function pvdak_readiness_page_status_label( $classification, $status ) {
    if ( 'missing' === $classification ) { return 'برگه وجود ندارد'; }
    if ( 'wrong_template' === $classification ) {
        return 'منتشرشده — قالب صفحه نادرست است؛ مسیر ممکن است کار نکند';
    }
    if ( 'template_unavailable' === $classification ) {
        return 'منتشرشده — فایل یا تعریف قالب لازم در نصب فعلی موجود نیست؛ نیازمند بررسی فنی';
    }
    if ( 'provisional' === $classification ) { return 'منتشرشده — متن اولیه یا نشانگر نامعتبر؛ نیازمند تأیید'; }
    if ( 'published' === $classification ) { return 'منتشرشده — تأیید محتوایی مستقل لازم است'; }
    $statuses = array(
        'draft' => 'پیش‌نویس', 'pending' => 'در انتظار بررسی',
        'private' => 'خصوصی', 'trash' => 'زباله‌دان', 'future' => 'زمان‌بندی‌شده',
    );
    $label = is_string( $status ) && isset( $statuses[ $status ] ) ?
        $statuses[ $status ] : 'وضعیت نامعتبر یا نامشخص';
    return 'منتشرنشده — ' . $label;
}

/**
 * WordPress privacy-page setting is separate from publishing a privacy Page.
 * This read-only comparison never treats the selected Page as legally approved.
 */
function pvdak_readiness_privacy_setting_state( $expected_id, $published, $selected_id ) {
    if ( ! is_int( $expected_id ) || $expected_id < 1 || true !== $published ) {
        return 'missing';
    }
    if ( 0 === $selected_id || '0' === $selected_id || '' === $selected_id ) {
        return 'unset';
    }
    if ( ! is_int( $selected_id ) && ! is_string( $selected_id ) ) {
        return 'mismatch';
    }
    return (string) $expected_id === (string) $selected_id ? 'matched' : 'mismatch';
}

/** A malformed page_on_front option must not be coerced into a real Page ID. */
function pvdak_readiness_front_page_id( $value ) {
    if ( ! is_int( $value ) && ! is_string( $value ) ) { return 0; }
    $raw = (string) $value;
    if ( ! preg_match( '/^[1-9][0-9]*$/D', $raw ) ) { return 0; }
    $id = (int) $raw;
    return $id > 0 && (string) $id === $raw ? $id : 0;
}

/**
 * Report Reading settings, not a claim about rendered front-page.php.
 * WordPress can still load front-page.php when "latest posts" is selected,
 * but the ACF homepage editor needs a published static Page.
 */
function pvdak_readiness_front_page_state( $mode, $selected_id, $post_type, $post_status, $posts_page_id = 0 ) {
    if ( 'posts' === $mode ) { return 'posts_mode'; }
    // A corrupted Reading option must not masquerade as the valid posts mode.
    if ( 'page' !== $mode ) { return 'invalid_mode'; }
    $front_id = pvdak_readiness_front_page_id( $selected_id );
    if ( ! $front_id ) { return 'missing'; }
    if ( 'page' !== $post_type ) { return 'invalid'; }
    if ( 'publish' !== $post_status ) { return 'unpublished'; }
    // A Page cannot safely serve as both the static front page and posts index.
    if ( $front_id === pvdak_readiness_front_page_id( $posts_page_id ) ) {
        return 'posts_conflict';
    }
    return 'ready';
}

/** Read only WordPress options and Page metadata, never edit them. */
function pvdak_readiness_front_page_snapshot() {
    $mode = get_option( 'show_on_front', 'posts' );
    // Do not query Pages when WordPress is configured for latest posts.
    if ( 'page' !== $mode ) {
        return pvdak_readiness_front_page_state( $mode, 0, false, false );
    }
    $selected_id = get_option( 'page_on_front', 0 );
    $id = pvdak_readiness_front_page_id( $selected_id );
    return pvdak_readiness_front_page_state(
        $mode, $selected_id,
        $id ? get_post_type( $id ) : false,
        $id ? get_post_status( $id ) : false,
        get_option( 'page_for_posts', 0 )
    );
}

function pvdak_readiness_front_page_status_label( $state ) {
    $labels = array(
        'ready' => 'برگه ثابت منتشرشده برای صفحه اصلی انتخاب شده است',
        'posts_mode' => 'حالت آخرین نوشته‌ها انتخاب شده؛ قالب صفحه اصلی ممکن است نمایش داده شود، اما ویرایشگر محتوای آن به برگه ثابت نیاز دارد',
        'invalid_mode' => 'تنظیم نمایش صفحه اصلی وردپرس نامعتبر است؛ تنظیمات خواندن را بررسی کنید',
        'posts_conflict' => 'یک برگه هم‌زمان برای صفحه اصلی و فهرست نوشته‌ها انتخاب شده؛ این دو برگه باید متفاوت باشند',
        'missing' => 'برگه ثابت صفحه اصلی انتخاب نشده یا شناسه تنظیم‌شده نامعتبر است',
        'invalid' => 'انتخاب صفحه اصلی به یک برگه معتبر اشاره نمی‌کند',
        'unpublished' => 'برگه انتخاب‌شده برای صفحه اصلی هنوز منتشر نشده است',
    );
    return is_string( $state ) && isset( $labels[ $state ] ) ?
        $labels[ $state ] : 'وضعیت تنظیم صفحه اصلی نامشخص است';
}

/**
 * Only required Pages count toward the readiness summary. Optional legacy
 * routes may be listed for cleanup without blocking the current login flow.
 * Ignore malformed rows rather than allowing a PHP illegal-offset error.
 */
function pvdak_readiness_required_totals( $audits ) {
    $totals = array( 'missing' => 0, 'unpublished' => 0, 'wrong_template' => 0,
        'template_unavailable' => 0, 'provisional' => 0, 'published' => 0 );
    if ( ! is_array( $audits ) ) { return $totals; }
    foreach ( $audits as $audit ) {
        if ( ! is_array( $audit ) || ! empty( $audit['optional'] ) ||
             ! isset( $audit['classification'] ) ||
             ! is_string( $audit['classification'] ) ||
             ! array_key_exists( $audit['classification'], $totals ) ) { continue; }
        $totals[ $audit['classification'] ]++;
    }
    return $totals;
}

function pvdak_readiness_render() {
    if(!current_user_can('manage_options')){
        wp_die('دسترسی مجاز نیست.','',array('response'=>403));
    }
    $pages=array(
        'about-us'=>'درباره ما',
        'contact-us'=>'تماس با ما',
        'legal-faq'=>'پرسش‌های متداول',
        'privacy-policy'=>'حریم خصوصی',
        'terms-of-use'=>'شرایط استفاده',
        'account-login'=>'ورود اختصاصی',
        'account-recovery'=>'بازیابی رمز',
        'account-profile'=>'پروفایل',
        'my-pvdak-account'=>'پنل کاربری',
    );
    // Kept for old bookmarks only: signup now happens inside account-login.
    $optional_pages = array( 'account-register'=>'ثبت‌نام (برگه قدیمی)' );
    echo '<div class="wrap"><h1>آمادگی راه‌اندازی PVDak</h1>';
    echo '<p>این گزارش فقط خواندنی است و هیچ تنظیم یا اطلاعات کاربری را تغییر نمی‌دهد.</p>';
    $privacy_page = get_page_by_path( 'privacy-policy', OBJECT, 'page' );
    $privacy_expected_id = $privacy_page instanceof WP_Post ? (int) $privacy_page->ID : 0;
    $privacy_published = $privacy_page instanceof WP_Post &&
        'publish' === $privacy_page->post_status;
    $privacy_state = pvdak_readiness_privacy_setting_state(
        $privacy_expected_id, $privacy_published,
        get_option( 'wp_page_for_privacy_policy', 0 ) );
    $privacy_labels = array(
        'missing' => 'برگه حریم خصوصی PVDak منتشر نشده یا وجود ندارد',
        'unset' => 'در تنظیمات وردپرس هنوز برگه‌ای انتخاب نشده',
        'mismatch' => 'برگه دیگری انتخاب شده؛ تنظیمات را بررسی کنید',
        'matched' => 'برگه PVDak انتخاب شده؛ تأیید حقوقی متن جداگانه لازم است',
    );
    $front_state = pvdak_readiness_front_page_snapshot();
    $facts=array(
        'نسخه قالب'=>wp_get_theme()->get('Version'),
        'تنظیم صفحه اصلی'=>pvdak_readiness_front_page_status_label( $front_state ),
        'نسخه Core فعال'=>defined('PVDAK_CORE_VERSION')?PVDAK_CORE_VERSION:'نصب یا فعال نشده',
        'برگه حریم خصوصی وردپرس'=>$privacy_labels[ $privacy_state ],
        'حالت تعمیرات'=>(function_exists('pvdak_maintenance_enabled')&&pvdak_maintenance_enabled())?'فعال':'غیرفعال',
        'ثبت‌نام عمومی'=>(function_exists('pvdak_registration_open')&&pvdak_registration_open())?'فعال — نیازمند بررسی':'بسته',
        'فرم پذیرش پرونده'=>(function_exists('pvdak_core_intake_open')&&pvdak_core_intake_open())?'فعال — نیازمند بررسی':'بسته',
        'عضویت متخصص'=>(function_exists('pvdak_core_member_intake_open')&&pvdak_core_member_intake_open())?'فعال — نیازمند بررسی':'بسته',
        'محدودیت اشتراک'=>(function_exists('pvdak_core_subscription_gate_on')&&pvdak_core_subscription_gate_on())?'فعال — نیازمند بررسی':'خاموش'
    );
    echo '<h2>وضعیت کلی</h2><table class="widefat striped"><tbody>';
    foreach($facts as $name=>$value){
        echo '<tr><th scope="row">'.esc_html($name).'</th><td>'.esc_html((string)$value).'</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p><a href="' . esc_url( admin_url( 'options-reading.php' ) ) .
        '">بررسی تنظیمات خواندن و برگه صفحه اصلی</a> — این گزارش وضعیت برگه انتخاب‌شده را بررسی می‌کند، نه نمایش نهایی قالب front-page.php.</p>';
    echo '<p><a href="' . esc_url( admin_url( 'options-privacy.php' ) ) .
        '">بررسی تنظیمات حریم خصوصی وردپرس</a> — انتخاب برگه در وردپرس، جایگزین تأیید حقوقی متن نیست.</p>';
    // Use the same template definitions as the existing Page bootstrap.
    // A published URL with a changed template can break account flows.
    // Never read private page bodies or auto-create/publish from this report.
    $seed_pages = function_exists( 'pvdak_public_page_seeds' ) ?
        pvdak_public_page_seeds() : array();
    $audits = array();
    foreach ( $pages + $optional_pages as $slug => $label ) {
        $post = get_page_by_path( $slug, OBJECT, 'page' );
        $exists = $post instanceof WP_Post && absint( $post->ID ) > 0;
        $status = $exists ? $post->post_status : '';
        $provisional = $exists ? get_post_meta( $post->ID, '_pvdak_provisional_policy', true ) : '';
        $expected_template = isset( $seed_pages[ $slug ][1] ) &&
            is_string( $seed_pages[ $slug ][1] ) ? $seed_pages[ $slug ][1] : '';
        $template_available = '' !== $expected_template &&
            (bool) locate_template( $expected_template, false, false );
        $template_matches = $exists && '' !== $expected_template &&
            $expected_template === get_page_template_slug( $post->ID );
        $classification = pvdak_readiness_page_classify(
            $exists, $status, $provisional, $template_matches, $template_available );
        $audits[] = array(
            'slug' => $slug, 'title' => $label, 'post' => $exists ? $post : null,
            'optional' => isset( $optional_pages[ $slug ] ),
            'classification' => $classification,
            'status' => pvdak_readiness_page_status_label( $classification, $status ),
        );
    }
    $totals = pvdak_readiness_required_totals( $audits );
    echo '<h2>برگه‌های مهم</h2>';
    echo '<p class="description">۹ برگه ضروری در آمار زیر شمرده می‌شوند. برگه «ثبت‌نام (برگه قدیمی)» صرفاً برای بررسی پیوندهای قبلی در جدول باقی می‌ماند؛ نبود آن نقص راه‌اندازی نیست. ثبت‌نام فعلی در زبانه صفحه ورود انجام می‌شود و وجود برگه قدیمی به معنی فعال‌بودن ثبت‌نام نیست.</p>';
    echo '<p><strong>خلاصه وضعیت ۹ برگه ضروری:</strong> ' .
        esc_html( number_format_i18n( $totals['published'] ) ) . ' منتشرشده بدون نشانگر متن اولیه، ' .
        esc_html( number_format_i18n( $totals['provisional'] ) ) . ' نیازمند تأیید متن اولیه، ' .
        esc_html( number_format_i18n( $totals['wrong_template'] ) ) . ' با قالب نادرست، ' .
        esc_html( number_format_i18n( $totals['template_unavailable'] ) ) . ' فاقد فایل/تعریف قالب، ' .
        esc_html( number_format_i18n( $totals['unpublished'] ) ) . ' منتشرنشده و ' .
        esc_html( number_format_i18n( $totals['missing'] ) ) . ' فاقد برگه.</p>';
    echo '<p class="description">این آمار فقط وضعیت برگه‌ها و تطبیق قالب آن‌هاست، نه تأیید حقوقی محتوا، امنیت خدمات یا مجوز افتتاح عمومی. برای برگه‌های دارای قالب نادرست، در ویرایش برگه قالب مناسب را بررسی کنید؛ اگر فایل/تعریف قالب در دسترس نیست، توسعه‌دهنده باید بسته نصب‌شده را بررسی کند. هیچ برگه‌ای از این صفحه ساخته یا منتشر نمی‌شود.</p>';
    echo '<table class="widefat striped"><thead><tr><th scope="col">برگه</th><th scope="col">نامک مورد انتظار</th><th scope="col">وضعیت</th><th scope="col">اقدام مدیر</th></tr></thead><tbody>';
    foreach ( $audits as $audit ) {
        $post = $audit['post'];
        $url = $post && current_user_can( 'edit_post', $post->ID ) ?
            get_edit_post_link( $post->ID ) : '';
        $action = 'template_unavailable' === $audit['classification'] ?
            'بررسی قالب با توسعه‌دهنده' : ( 'wrong_template' === $audit['classification'] ?
            'بازبینی قالب برگه' : 'ویرایش و بررسی' );
        if ( ! $post && ! empty( $audit['optional'] ) ) {
            // An absent legacy registration Page is expected, not a task.
            $url = '';
            $action = 'نیازی به ایجاد نیست';
        } elseif ( ! $post && current_user_can( 'edit_pages' ) ) {
            $url = admin_url( 'post-new.php?post_type=page' );
            $action = 'ایجاد دستی برگه';
        }
        echo '<tr><th scope="row">' . esc_html( $audit['title'] ) .
            '</th><td><code dir="ltr">' . esc_html( $audit['slug'] ) .
            '</code></td><td>' . esc_html( $audit['status'] ) . '</td><td>';
        if ( $url ) {
            echo '<a href="' . esc_url( $url ) . '">' . esc_html( $action ) . '</a>';
        } else {
            echo esc_html( $action );
        }
        echo '</td></tr>';
    }
    echo '</tbody></table><p><strong>پیش از افتتاح:</strong> سیاست حریم خصوصی، قوانین، ایمیل‌های سیستمی، مجوزهای دو حساب مستقل و نسخه فعال Core باید عملاً بررسی شوند.</p>';
    echo '</div>';
}
