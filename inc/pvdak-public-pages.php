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
/**
 * Only original, unchanged Core page titles are renamed after an admin visit.
 * Never overwrite a title edited by the site owner or modify a page slug.
 */
function pvdak_public_relabel_legacy_brand_pages() {
  if ( ! is_admin() || ! current_user_can('edit_pages') ) { return; }
  // Only migrate a WordPress site title/tagline that is exactly the old
  // scaffold brand. Never reset a customized name or description.
  if ( current_user_can( 'manage_options' ) ) {
    $old_site_names = array( 'پویش', 'پویش وکلای دادگستری کشور' );
    if ( in_array( (string) get_option('blogname'), $old_site_names, true ) ) {
      update_option( 'blogname', 'PVDak' );
    }
    $old_taglines = array(
      'پویش وکلای دادگستری کشور' => 'PVDak',
      'پویش وکلای دادگستری کشور • همراه مسیر حقوقی شما' => 'PVDak • همراه مسیر حقوقی شما',
    );
    $tagline = (string) get_option( 'blogdescription', '' );
    if ( isset( $old_taglines[$tagline] ) ) {
      update_option( 'blogdescription', $old_taglines[$tagline] );
    }
  }
  $old_titles = array(
    'my-pvdak-account' => array( 'پنل کاربری پویش', 'پنل کاربری PVDak' ),
    'join-legal-community' => array( 'همکاری با پویش وکلا', 'همکاری با PVDak' ),
  );
  foreach ( $old_titles as $slug => $names ) {
    $page = get_page_by_path( $slug, OBJECT, 'page' );
    if ( $page instanceof WP_Post && $page->post_title === $names[0] &&
         current_user_can( 'edit_post', $page->ID ) ) {
      wp_update_post( array( 'ID' => $page->ID, 'post_title' => $names[1] ) );
    }
  }
}
add_action('admin_init','pvdak_public_relabel_legacy_brand_pages',25);

/**
 * D10: Work observed the untouched WordPress sample site name "Bloggers Unite"
 * in public browser tabs. Do not mutate the owner's WordPress setting behind
 * their back: show the PVDak brand only for this exact uncustomized scaffold.
 * The admin readiness panel points to Settings > General for a lasting fix.
 */
function pvdak_public_document_title_brand_fallback( $parts ) {
  if ( ! is_array( $parts ) ||
       'Bloggers Unite' !== trim( (string) get_option( 'blogname', '' ) ) ) {
    return $parts;
  }
  foreach ( array( 'title', 'site' ) as $key ) {
    if ( isset( $parts[ $key ] ) &&
         'Bloggers Unite' === trim( (string) $parts[ $key ] ) ) {
      $parts[ $key ] = 'PVDak';
    }
  }
  return $parts;
}
add_filter( 'document_title_parts', 'pvdak_public_document_title_brand_fallback', 20 );

function pvdak_public_policy_noindex($robots) {
  $post=is_singular('page') ? get_queried_object() : null;
  if($post instanceof WP_Post && '1'===get_post_meta($post->ID,'_pvdak_provisional_policy',true)) {
    $robots['noindex']=true;
  }
  return $robots;
}
add_filter('wp_robots','pvdak_public_policy_noindex');

/**
 * D28: moderated community discussion built on native WordPress comments.
 * This is a general-topic forum, NOT a legal advice or client-case channel.
 * It is disabled until the site owner approves moderation and legal policies.
 * No Page, option, user role, comment or database record is created on deploy.
 */
function pvdak_forum_enabled() {
  return '1' === (string) get_option('pvdak_forum_enabled', '0') &&
    (!function_exists('pvdak_maintenance_enabled') || !pvdak_maintenance_enabled());
}
function pvdak_forum_page_valid($page_id) {
  $page = get_post(absint($page_id));
  return $page instanceof WP_Post && 'page' === $page->post_type &&
    'publish' === $page->post_status && is_string($page->post_content) &&
    has_shortcode($page->post_content, 'pvdak_forum');
}
/** Never equate a WordPress role with an approved community application. */
function pvdak_forum_member_can_post() {
  $user_id = get_current_user_id();
  if (!$user_id || !current_user_can('read') ||
      !function_exists('pvdak_core_existing_application')) return false;
  $user = get_userdata($user_id);
  if (!$user || !isset($user->user_email) || !is_email($user->user_email) ||
      (int) get_user_meta($user_id, '_pvdak_email_verified_at', true) < 1 ||
      '1' === (string) get_user_meta($user_id, '_pvdak_email_verification_pending', true)) return false;
  $verified = get_user_meta($user_id, '_pvdak_email_verified_address', true);
  if (!is_string($verified) ||
      !hash_equals(strtolower(trim($user->user_email)), strtolower(trim($verified)))) return false;
  $profile_id = absint(pvdak_core_existing_application($user_id));
  if (!$profile_id || 'pvdak_professional' !== get_post_type($profile_id) ||
      'private' !== get_post_status($profile_id) ||
      $user_id !== (int) get_post_field('post_author', $profile_id) ||
      'approved' !== get_post_meta($profile_id, '_pvdak_member_state', true)) return false;
  return in_array(get_post_meta($profile_id, '_pvdak_member_type', true),
    array('lawyer', 'trainee', 'student'), true);
}
/** Native moderation controls remain with WordPress administrators. */
function pvdak_forum_shortcode() {
  $page_id = get_queried_object_id();
  if (!pvdak_forum_page_valid($page_id)) return '';
  $html = '<section id="pvdak-forum" class="pvdak-forum-board" dir="rtl" aria-labelledby="pvdak-forum-title">';
  $html .= '<h2 id="pvdak-forum-title">گفت‌وگوی عمومی جامعه حقوقی</h2>';
  $html .= '<p>این بخش برای موضوعات عمومی و آموزشی است؛ اطلاعات پرونده، نام اشخاص، شماره تماس یا مدارک محرمانه را ارسال نکنید. پیام‌ها پیش از انتشار بررسی می‌شوند.</p>';
  if (!pvdak_forum_enabled()) {
    return $html . '<p class="pvdak-forum-alert" role="status">انجمن تا تأیید سیاست انتشار و مدیریت محتوا غیرفعال است.</p></section>';
  }
  $notice = isset($_GET['forum']) && is_string($_GET['forum']) ? sanitize_key(wp_unslash($_GET['forum'])) : '';
  $notices = array(
    'pending' => 'پیام دریافت شد و فقط پس از بررسی مدیر ممکن است منتشر شود.',
    'slow' => 'تعداد ارسال‌ها بیش از حد مجاز است. بعداً دوباره تلاش کنید.',
    'invalid' => 'موضوع یا متن پیام معتبر نیست. از درج پیوند و اطلاعات حساس خودداری کنید.',
    'failed' => 'ثبت پیام انجام نشد. لطفاً بعداً دوباره تلاش کنید.',
  );
  if (isset($notices[$notice])) {
    $html .= '<p class="pvdak-forum-alert" role="status">' . esc_html($notices[$notice]) . '</p>';
  }
  $entries = get_comments(array(
    'post_id' => $page_id, 'status' => 'approve', 'type' => 'comment',
    'meta_key' => '_pvdak_forum_entry', 'meta_value' => '1',
    'number' => 10, 'orderby' => 'comment_date_gmt', 'order' => 'DESC',
  ));
  $items = array();
  foreach (is_array($entries) ? $entries : array() as $entry) {
    if (!$entry instanceof WP_Comment || (int) $entry->comment_post_ID !== (int) $page_id ||
        '1' !== (string) $entry->comment_approved ||
        '1' !== (string) get_comment_meta($entry->comment_ID, '_pvdak_forum_entry', true) ||
        !is_string($entry->comment_content) || '' === trim($entry->comment_content)) continue;
    $items[] = '<li class="pvdak-forum-entry"><p>' .
      nl2br(esc_html($entry->comment_content)) . '</p><small>عضو جامعه · ' .
      esc_html(get_comment_date('Y/m/d', $entry)) . '</small></li>';
  }
  $html .= $items ? '<ol class="pvdak-forum-entries">' . implode('', $items) . '</ol>' :
    '<p class="pvdak-forum-alert">هنوز گفت‌وگوی تأییدشده‌ای برای نمایش وجود ندارد.</p>';
  if (!pvdak_forum_member_can_post()) {
    return $html . '<p class="pvdak-forum-alert">ارسال پیام فقط برای اعضای جامعه حقوقی با درخواست همکاری تأییدشده و ایمیل تأییدشده فعال است.</p></section>';
  }
  $html .= '<form class="pvdak-forum-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
  $html .= '<input type="hidden" name="action" value="pvdak_forum_submit">';
  $html .= '<input type="hidden" name="forum_page_id" value="' . esc_attr($page_id) . '">';
  $html .= wp_nonce_field('pvdak_forum_post_' . $page_id, '_pvdak_forum_nonce', true, false);
  $html .= '<label for="pvdak-forum-subject">موضوع گفت‌وگو</label><input id="pvdak-forum-subject" name="forum_subject" type="text" minlength="5" maxlength="120" required>';
  $html .= '<label for="pvdak-forum-message">متن عمومی</label><textarea id="pvdak-forum-message" name="forum_message" minlength="20" maxlength="1500" rows="5" required></textarea>';
  $html .= '<div class="pvdak-forum-honeypot" aria-hidden="true"><label>این فیلد را خالی بگذارید<input name="forum_website" tabindex="-1" autocomplete="off"></label></div>';
  $html .= '<p><small>حداکثر ۳ پیام در ساعت؛ بدون پیوند. انتشار تنها پس از تأیید مدیر است.</small></p>';
  $html .= '<button type="submit">ارسال برای بررسی</button></form></section>';
  return $html;
}
add_shortcode('pvdak_forum', 'pvdak_forum_shortcode');

/** Prevent cached per-user form nonces and indexing before legal approval. */
function pvdak_forum_send_headers() {
  if (is_page() && pvdak_forum_page_valid(get_queried_object_id())) nocache_headers();
}
// Main query is not resolved at send_headers; template_redirect runs before output.
add_action('template_redirect', 'pvdak_forum_send_headers', 0);
function pvdak_forum_robots($robots) {
  if (is_page() && pvdak_forum_page_valid(get_queried_object_id())) $robots['noindex'] = true;
  return $robots;
}
add_filter('wp_robots', 'pvdak_forum_robots');

/** Fail closed on malformed forms, concurrency failures and untracked posts. */
function pvdak_forum_submit() {
  if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '')) wp_die('روش درخواست نامعتبر است.', '', array('response' => 405));
  $raw_id = isset($_POST['forum_page_id']) ? wp_unslash($_POST['forum_page_id']) : '';
  if (!is_string($raw_id) || strlen($raw_id) > 10 ||
      !ctype_digit($raw_id) || (int) $raw_id < 1) wp_die('صفحه نامعتبر است.', '', array('response' => 400));
  $page_id = (int) $raw_id;
  if (!pvdak_forum_enabled() || !pvdak_forum_page_valid($page_id) ||
      !pvdak_forum_member_can_post()) wp_die('ارسال پیام مجاز نیست.', '', array('response' => 403));
  check_admin_referer('pvdak_forum_post_' . $page_id, '_pvdak_forum_nonce');
  $subject_raw = isset($_POST['forum_subject']) ? wp_unslash($_POST['forum_subject']) : '';
  $message_raw = isset($_POST['forum_message']) ? wp_unslash($_POST['forum_message']) : '';
  $honeypot = isset($_POST['forum_website']) ? wp_unslash($_POST['forum_website']) : '';
  $subject = is_string($subject_raw) ? trim(sanitize_text_field($subject_raw)) : '';
  $message = is_string($message_raw) ? trim(sanitize_textarea_field($message_raw)) : '';
  $valid = is_string($honeypot) && '' === trim($honeypot) &&
    pvdak_public_text_length($subject) >= 5 && pvdak_public_text_length($subject) <= 120 &&
    pvdak_public_text_length($message) >= 20 && pvdak_public_text_length($message) <= 1500 &&
    !preg_match('~(?:https?://|www\.)~i', $subject . ' ' . $message);
  $result = 'invalid';
  if ($valid) {
    global $wpdb;
    $user_id = get_current_user_id();
    $lock_name = 'pvdak_forum_user_' . $user_id;
    $locked = isset($wpdb) && is_object($wpdb) &&
      '1' === (string) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 3)', $lock_name));
    $result = 'failed';
    if ($locked) {
      try {
        $hour_key = 'pvdak_forum_hour_' . $user_id;
        $cool_key = 'pvdak_forum_cool_' . $user_id;
        $count = (int) get_transient($hour_key);
        if ($count >= 3 || get_transient($cool_key)) {
          $result = 'slow';
        } else {
          // Store no public account ID, email, URL, IP or user agent.
          // Moderators can see the private submitter ID in comment meta.
          $comment_id = wp_insert_comment(array(
            'comment_post_ID' => $page_id, 'comment_content' => 'موضوع: ' . $subject . "\n\n" . $message,
            'comment_approved' => '0', 'comment_type' => 'comment',
            'comment_author' => 'عضو جامعه', 'comment_author_email' => '',
            'comment_author_url' => '', 'comment_author_IP' => '',
            'comment_agent' => '', 'user_id' => 0, 'comment_parent' => 0,
          ));
          $stored = $comment_id && !is_wp_error($comment_id) ? get_comment($comment_id) : null;
          $marked = $stored instanceof WP_Comment &&
            '0' === (string) $stored->comment_approved &&
            add_comment_meta($comment_id, '_pvdak_forum_entry', '1', true) &&
            add_comment_meta($comment_id, '_pvdak_forum_submitter', (string) $user_id, true) &&
            '1' === (string) get_comment_meta($comment_id, '_pvdak_forum_entry', true) &&
            (string) $user_id === (string) get_comment_meta($comment_id, '_pvdak_forum_submitter', true);
          if ($marked && set_transient($hour_key, $count + 1, HOUR_IN_SECONDS) &&
              set_transient($cool_key, 1, 90)) {
            $result = 'pending';
          } else {
            if ($comment_id && !is_wp_error($comment_id)) {
              delete_comment_meta($comment_id, '_pvdak_forum_entry');
              wp_delete_comment($comment_id, true);
            }
            delete_transient($cool_key);
          }
        }
      } finally {
        $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
      }
    }
  }
  wp_safe_redirect(add_query_arg('forum', $result, get_permalink($page_id)) . '#pvdak-forum');
  exit;
}
add_action('admin_post_pvdak_forum_submit', 'pvdak_forum_submit');

/** Settings > General: an explicit OFF-by-default switch, never auto-enabled. */
function pvdak_forum_sanitize_switch($value) {
  return '1' === (string) $value ? '1' : '0';
}
function pvdak_forum_settings_field() {
  echo '<input type="hidden" name="pvdak_forum_enabled" value="0">';
  echo '<label><input type="checkbox" name="pvdak_forum_enabled" value="1" ' .
    checked('1', get_option('pvdak_forum_enabled', '0'), false) .
    '> فعال‌سازی انجمن پس از تأیید سیاست محتوا و مدیریت پیام‌ها</label>';
  echo '<p class="description">پیش‌فرض غیرفعال است. ابتدا برگه منتشرشده دارای شورت‌کد انجمن، مقررات، مسئول بررسی پیام‌ها و آزمون نقش‌ها را آماده کنید.</p>';
}
function pvdak_forum_register_setting() {
  register_setting('general', 'pvdak_forum_enabled', array(
    'type' => 'string', 'default' => '0', 'sanitize_callback' => 'pvdak_forum_sanitize_switch',
  ));
  add_settings_field('pvdak_forum_enabled', 'انجمن PVDak',
    'pvdak_forum_settings_field', 'general');
}
add_action('admin_init', 'pvdak_forum_register_setting');

function pvdak_contact_allowed() {
  // An unpublished or misconfigured Page must not leave admin-post intake open.
  return (bool)pvdak_public_template_page_url('contact-us','page-templates/pvdak-contact.php') &&
    (!function_exists('pvdak_maintenance_enabled') || !pvdak_maintenance_enabled() ||
     (function_exists('pvdak_maintenance_can_preview_site') && pvdak_maintenance_can_preview_site()));
}
function pvdak_contact_handle() {
  if(!pvdak_contact_allowed()) wp_die('فرم تا پایان توسعه غیرفعال است.','',array('response'=>403));
  if('POST'!==($_SERVER['REQUEST_METHOD']??'')) wp_die('Invalid method','',array('response'=>405));
  check_admin_referer('pvdak_contact_form','_pvdak_contact_nonce');
  $url=pvdak_public_page_url('contact-us')?:home_url('/');
  // Include honeypot and malformed submissions in an IP-wide limit.
  if(pvdak_registration_rate_exceeded('contact',12,HOUR_IN_SECONDS)) {
    wp_safe_redirect(add_query_arg('sent','slow',$url));exit;
  }
  if(!empty($_POST['contact_website'])) {wp_safe_redirect(add_query_arg('sent','1',$url));exit;}
  $raw_name=wp_unslash($_POST['contact_name']??'');
  $raw_email=wp_unslash($_POST['contact_email']??'');
  $raw_message=wp_unslash($_POST['contact_message']??'');
  $name=is_string($raw_name)?sanitize_text_field($raw_name):'';
  $email=is_string($raw_email)?sanitize_email($raw_email):'';
  $msg=is_string($raw_message)?sanitize_textarea_field($raw_message):'';
  if(!$name || pvdak_public_text_length($name)>100 || !is_email($email) || strlen($email)>254 ||
     pvdak_public_text_length($msg)<10 || pvdak_public_text_length($msg)>3500 ||
     !in_array($_POST['contact_consent']??null,array('on','1'),true)) {
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
