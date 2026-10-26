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
        array('درخواست‌های حقوقی', 'صف رسیدگی به درخواست‌ها؛ بدون فعال‌کردن پذیرش عمومی', 'manage_options', 'edit.php?post_type=pvdak_request'),
        array('دیدگاه‌ها و گفت‌وگوها', 'مدیریت و تأیید دیدگاه‌ها و گفت‌وگوهای انجمن، در صورت استفاده', 'moderate_comments', 'edit-comments.php'),
        array('تنظیمات اصلی', 'تنظیم ثبت‌نام و حالت تعمیرات سایت', 'manage_options', 'options-general.php'),
        array('بررسی پیش از راه‌اندازی', 'وضعیت فنی و حقوقی فقط برای مدیر سایت', 'manage_options', 'tools.php?page=pvdak-readiness'),
    );
    return $links;
}

function pvdak_management_render_links() {
    echo '<div class="pvdak-management-links" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(245px,1fr));gap:12px;max-width:1050px;margin-top:20px">';
    foreach (pvdak_management_links() as $link) {
        if (!current_user_can($link[2])) { continue; }
        // WordPress validates post-type access again on the destination page.
        echo '<a style="display:block;padding:18px;background:#fff;border:1px solid #dcdcde;border-radius:8px;text-decoration:none" href="' .
            esc_url(admin_url($link[3])) . '"><strong style="display:block;font-size:15px;margin-bottom:8px">' .
            esc_html($link[0]) . '</strong><span style="color:#50575e">' . esc_html($link[1]) . '</span></a>';
    }
    echo '</div>';
}

function pvdak_management_render() {
    if (!current_user_can('edit_pages')) {
        wp_die('دسترسی مجاز نیست.','',array('response'=>403));
    }
    echo '<div class="wrap"><h1>مدیریت سایت PVDak</h1>';
    echo '<p>از اینجا کارهای روزمره سایت را انجام دهید. هر بخش مستقیماً به ابزار اصلی وردپرس متصل است و اطلاعات شما را جابه‌جا نمی‌کند.</p>';
    pvdak_management_render_links();
    if (current_user_can('manage_options')) {
        $maintenance = function_exists('pvdak_maintenance_enabled') && pvdak_maintenance_enabled();
        $signup = function_exists('pvdak_registration_open') && pvdak_registration_open();
        $requests = function_exists('pvdak_core_intake_open') && pvdak_core_intake_open();
        echo '<h2 style="margin-top:30px">وضعیت دسترسی‌های عمومی</h2>';
        echo '<p>این بخش فقط وضعیت فعلی را نشان می‌دهد؛ هیچ خدمتی را به‌صورت خودکار فعال نمی‌کند.</p><ul style="list-style:disc;padding-right:20px">';
        echo '<li>حالت تعمیرات: <strong>' . ($maintenance ? 'فعال' : 'غیرفعال') . '</strong></li>';
        echo '<li>ثبت‌نام عمومی: <strong>' . ($signup ? 'فعال' : 'بسته') . '</strong></li>';
        echo '<li>پذیرش درخواست حقوقی: <strong>' . ($requests ? 'فعال' : 'بسته') . '</strong></li>';
        echo '</ul><p>پیش از فعال‌سازی خدمات جدید، مقررات و تأییدهای لازم را بررسی کنید.</p>';
    }
    echo '</div>';
}

/**
 * Simplify the primary menu, not permissions or content. Comment moderation
 * remains reachable from the management hub, including forum discussions.
 */
function pvdak_management_trim_menu() {
    if (current_user_can('edit_pages') && current_user_can('moderate_comments')) {
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
    echo '<p><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=pvdak-management')) . '">ورود به مدیریت PVDak</a> ';
    echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=pvdak-home-content')) . '">ویرایش صفحه اصلی</a></p>';
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
