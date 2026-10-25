<?php
/** WordPress-native public Pages; create only missing Pages on next admin visit. */
if ( ! defined('ABSPATH') ) { exit; }
/** Unicode code-point length, including newlines, matching form counters. */
function pvdak_public_text_length( $value ) {
  if ( function_exists( 'mb_strlen' ) ) { return mb_strlen( $value, 'UTF-8' ); }
  $count = preg_match_all( '/[\s\S]/u', $value );
  return false === $count ? strlen( $value ) : $count;
}
function pvdak_public_page_url($slug) {
  $p = get_page_by_path(sanitize_title($slug), OBJECT, 'page');
  return $p && 'publish' === $p->post_status ? get_permalink($p->ID) : '';
}
/**
 * Resolve a branded route only when the published Page actually uses its
 * expected PHP template. Editors may change templates after the initial seed;
 * a published URL alone does not guarantee a functioning account screen.
 */
function pvdak_public_template_page_url( $slug, $template ) {
  $page = get_page_by_path( sanitize_title( $slug ), OBJECT, 'page' );
  if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ||
       $template !== get_page_template_slug( $page->ID ) ) {
    return '';
  }
  return get_permalink( $page->ID );
}
function pvdak_public_page_seeds() {
  return array(
    'about-us'=>array('درباره ما','page-templates/pvdak-information.php','<h2>درباره PVDak</h2><p>PVDak بستری در حال توسعه برای آشنایی با موضوعات حقوقی و جامعه متخصصان است.</p><h2>مسیر توسعه</h2><p>مجله و محتوای آموزشی در حال تکمیل هستند. خدمات پذیرش پرونده و عضویت تا پایان آزمون‌ها غیرفعال‌اند.</p>'),
    'contact-us'=>array('تماس با ما','page-templates/pvdak-contact.php','<p>برای بازخورد، پیشنهاد و مشکلات عمومی وب‌سایت از فرم تماس استفاده کنید. اطلاعات محرمانه یا مدارک پرونده‌ها را ارسال نکنید.</p>'),
    'privacy-policy'=>array('حریم خصوصی','page-templates/pvdak-information.php','<h2>اطلاع‌رسانی اولیه</h2><p>این متن اولیه باید پیش از شروع رسمی خدمات بازبینی شود. ورود حساب کاربری از سازوکار وردپرس استفاده می‌کند و فرم عمومی تماس برای پیام‌های غیرحساس است.</p><p>جزئیات نگهداری داده‌ها و حقوق کاربران قبل از راه‌اندازی توسط مدیر تکمیل می‌شود.</p>'),
    'terms-of-use'=>array('شرایط استفاده','page-templates/pvdak-information.php','<h2>پیش‌نویس شرایط</h2><p>این متن اولیه شرایط نهایی خدمات محسوب نمی‌شود. مطالب نمونه، مشاوره اختصاصی نیستند و پذیرش درخواست تا زمان راه‌اندازی غیرفعال است.</p>'),
    'legal-faq'=>array('پرسش‌های متداول','page-templates/pvdak-information.php','<h2>راهنمای استفاده از سایت</h2><p>این بخش برای پاسخ به پرسش‌های عمومی و راهنمای استفاده از خدمات در حال آماده‌سازی است.</p><h2>آیا اکنون امکان ثبت درخواست حقوقی وجود دارد؟</h2><p>خیر؛ ثبت درخواست و عضویت تا پایان آزمایش و راه‌اندازی رسمی غیرفعال است.</p><h2>چگونه با PVDak ارتباط بگیرم؟</h2><p>برای بازخورد عمومی از صفحه تماس با ما استفاده کنید. لطفاً اطلاعات محرمانه پرونده‌ها را وارد نکنید.</p>'),
    'account-recovery'=>array('بازیابی رمز عبور','page-templates/pvdak-recovery.php',''),
    'account-profile'=>array('پروفایل من','page-templates/pvdak-profile.php',''),
    'account-register'=>array('ثبت‌نام','page-templates/pvdak-register.php',''),
    'account-login'=>array('ورود به حساب','page-templates/pvdak-login.php',''),
    'my-pvdak-account'=>array('پنل کاربری','page-templates/pvdak-account.php',''),
  );
}
/** Legacy WordPress-created sample content, matched byte-for-byte only. */
function pvdak_public_legacy_brand_seed_content() {
  return array(
    'about-us' => '<h2>درباره پویش</h2><p>پویش وکلای دادگستری کشور بستری در حال توسعه برای آشنایی با موضوعات حقوقی و جامعه متخصصان است.</p><h2>مسیر توسعه</h2><p>مجله و محتوای آموزشی در حال تکمیل هستند. خدمات پذیرش پرونده و عضویت تا پایان آزمون‌ها غیرفعال‌اند.</p>',
    'legal-faq' => '<h2>راهنمای استفاده از سایت</h2><p>این بخش برای پاسخ به پرسش‌های عمومی و راهنمای استفاده از خدمات در حال آماده‌سازی است.</p><h2>آیا اکنون امکان ثبت درخواست حقوقی وجود دارد؟</h2><p>خیر؛ ثبت درخواست و عضویت تا پایان آزمایش و راه‌اندازی رسمی غیرفعال است.</p><h2>چگونه با پویش ارتباط بگیرم؟</h2><p>برای بازخورد عمومی از صفحه تماس با ما استفاده کنید. لطفاً اطلاعات محرمانه پرونده‌ها را وارد نکنید.</p>',
  );
}
function pvdak_public_create_missing_pages() {
  if ( ! is_admin() || wp_doing_ajax() ||
       ! current_user_can('manage_options') || ! current_user_can('publish_pages') ) { return; }
  // A previous one-time completion flag may be stale: Pages can disappear
  // later, or a partial import can leave them unpublished. Always reconcile.
  // Only absent slugs are created; existing Pages are never overwritten.
  $complete = true;
  foreach(pvdak_public_page_seeds() as $slug=>$p) {
    $existing = get_page_by_path($slug,OBJECT,'page');
    if ($existing) {
      // Do not alter editor-authored Pages. Only upgrade the exact original
      // boilerplate published by our own earlier PVDak page bootstrap.
      if ('publish' !== $existing->post_status) { $complete = false; }
      if ('publish' === $existing->post_status) {
        $legacy = pvdak_public_legacy_brand_seed_content();
        if ( isset($legacy[$slug]) && $existing->post_content === $legacy[$slug] ) {
          $updated = wp_update_post(array('ID'=>$existing->ID,'post_content'=>$p[2]),true);
          if(is_wp_error($updated)||!$updated) {$complete=false;}
        }
      }
      continue;
    }
    $id=wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_name'=>$slug,
      'post_title'=>$p[0],'page_template'=>$p[1],'post_content'=>$p[2],
      'post_author'=>get_current_user_id(),'comment_status'=>'closed','ping_status'=>'closed'),true);
    if (is_wp_error($id)||!$id) {$complete=false;continue;}
    if (in_array($slug,array('privacy-policy','terms-of-use'),true)) {
      update_post_meta($id,'_pvdak_provisional_policy','1');
    }
  }
  if ($complete && '0.8.29' !== get_option('_pvdak_public_pages_bootstrap')) {
    update_option('_pvdak_public_pages_bootstrap','0.8.29',false);
  }
}
add_action('admin_init','pvdak_public_create_missing_pages',20);
function pvdak_public_policy_noindex($robots) {
  $post=is_singular('page') ? get_queried_object() : null;
  if($post instanceof WP_Post && '1'===get_post_meta($post->ID,'_pvdak_provisional_policy',true)) {
    $robots['noindex']=true;
  }
  return $robots;
}
add_filter('wp_robots','pvdak_public_policy_noindex');
function pvdak_contact_allowed() {
  return !function_exists('pvdak_maintenance_enabled') || !pvdak_maintenance_enabled() ||
   (function_exists('pvdak_maintenance_can_preview_site') && pvdak_maintenance_can_preview_site());
}
function pvdak_contact_handle() {
  if(!pvdak_contact_allowed()) wp_die('فرم تا پایان توسعه غیرفعال است.','',array('response'=>403));
  if('POST'!==($_SERVER['REQUEST_METHOD']??'')) wp_die('Invalid method','',array('response'=>405));
  check_admin_referer('pvdak_contact_form','_pvdak_contact_nonce');
  $url=pvdak_public_page_url('contact-us')?:home_url('/');
  if(!empty($_POST['contact_website'])) {wp_safe_redirect(add_query_arg('sent','1',$url));exit;}
  $raw_name=wp_unslash($_POST['contact_name']??'');
  $raw_email=wp_unslash($_POST['contact_email']??'');
  $raw_message=wp_unslash($_POST['contact_message']??'');
  $name=is_string($raw_name)?sanitize_text_field($raw_name):'';
  $email=is_string($raw_email)?sanitize_email($raw_email):'';
  $msg=is_string($raw_message)?sanitize_textarea_field($raw_message):'';
  if(!$name || pvdak_public_text_length($name)>100 || !is_email($email) || strlen($email)>254 ||
     pvdak_public_text_length($msg)<10 || pvdak_public_text_length($msg)>3500 ||
     !isset($_POST['contact_consent'])) {
    wp_safe_redirect(add_query_arg('sent','invalid',$url));exit;
  }
  $key='pvdak_contact_'.substr(hash_hmac('sha256',($_SERVER['REMOTE_ADDR']??'').$email,wp_salt('nonce')),0,30);
  if(get_transient($key)){wp_safe_redirect(add_query_arg('sent','slow',$url));exit;}
  set_transient($key,1,90);
  $body="نام: $name\nایمیل: $email\n\nپیام:\n$msg";
  $ok=wp_mail(get_option('admin_email'),'PVDak - پیام تماس عمومی',$body,array('Reply-To: '.$email));
  if(!$ok)delete_transient($key);
  wp_safe_redirect(add_query_arg('sent',$ok?'1':'failed',$url));exit;
}
add_action('admin_post_pvdak_contact','pvdak_contact_handle');
add_action('admin_post_nopriv_pvdak_contact','pvdak_contact_handle');
