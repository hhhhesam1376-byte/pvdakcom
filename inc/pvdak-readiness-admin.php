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
add_action('admin_menu','pvdak_readiness_menu');

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
