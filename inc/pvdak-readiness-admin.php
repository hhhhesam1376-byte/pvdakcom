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
    return is_object( $counts ) && isset( $counts->moderated ) ? absint( $counts->moderated ) : 0;
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
        $maintenance = function_exists('pvdak_maintenance_enabled') && pvdak_maintenance_enabled();
        $signup = function_exists('pvdak_registration_open') && pvdak_registration_open();
        $core_available = defined( 'PVDAK_CORE_VERSION' ) && function_exists( 'pvdak_core_intake_open' );
        $requests = $core_available && pvdak_core_intake_open();
        $members = $core_available && function_exists( 'pvdak_core_member_intake_open' ) && pvdak_core_member_intake_open();
        $directory = $core_available && function_exists( 'pvdak_core_directory_enabled' ) && pvdak_core_directory_enabled();
        $notifications = $core_available && '1' === (string) get_option( 'pvdak_core_email_notifications_enabled', '0' );
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
 * Simplify the primary menu, not permissions or content. Comment moderation
 * remains reachable from the management hub, including forum discussions.
 */
function pvdak_management_trim_menu() {
    if (current_user_can('edit_pages') && current_user_can('moderate_comments') && ! current_user_can('manage_options')) {
        remove_menu_page('edit-comments.php');
    }
}
add_action('admin_menu','pvdak_management_trim_menu',999);

function pvdak_management_dashboard_setup() {
    if (!current_user_can('edit_pages')) { return; }
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');
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
    echo '<div class="wrap"><h1>آمادگی راه‌اندازی PVDak</h1>';
    echo '<p>این گزارش فقط خواندنی است و هیچ تنظیم یا اطلاعات کاربری را تغییر نمی‌دهد.</p>';
    $facts=array(
        'نسخه قالب'=>wp_get_theme()->get('Version'),
        'نسخه Core فعال'=>defined('PVDAK_CORE_VERSION')?PVDAK_CORE_VERSION:'نصب یا فعال نشده',
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
    echo '</tbody></table><h2>برگه‌های مهم</h2><table class="widefat striped"><thead><tr><th>برگه</th><th>وضعیت</th><th>بررسی مدیر</th></tr></thead><tbody>';
    foreach($pages as $slug=>$label){
        $post=get_page_by_path($slug,OBJECT,'page');
        $state=$post?'وضعیت: '.$post->post_status:'وجود ندارد';
        if($post && '1'===(string)get_post_meta($post->ID,'_pvdak_provisional_policy',true)){
            $state.=' — متن اولیه، نیازمند تأیید';
        }
        $url=$post&&current_user_can('edit_post',$post->ID)?get_edit_post_link($post->ID):'';
        echo '<tr><th scope="row">'.esc_html($label).'</th><td>'.esc_html($state).'</td><td>';
        if($url)echo '<a href="'.esc_url($url).'">ویرایش و بررسی</a>';
        else echo '—';
        echo '</td></tr>';
    }
    echo '</tbody></table><p><strong>پیش از افتتاح:</strong> سیاست حریم خصوصی، قوانین، ایمیل‌های سیستمی، مجوزهای دو حساب مستقل و نسخه فعال Core باید عملاً بررسی شوند.</p>';
    echo '</div>';
}
