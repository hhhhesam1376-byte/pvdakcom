<?php
/**
 * Private non-public support ticket scaffolding. Administrators own ticket
 * operations; each user may submit and view only their own ticket summaries.
 * Never use this endpoint for legal case documents or private client data.
 */
if (!defined('ABSPATH')){exit;}
function pvdak_support_ticket_register(){
    $admincaps=array(
        'edit_post'=>'manage_options','read_post'=>'manage_options','delete_post'=>'manage_options',
        'edit_posts'=>'manage_options','edit_others_posts'=>'manage_options',
        'publish_posts'=>'manage_options','read_private_posts'=>'manage_options',
        'delete_posts'=>'manage_options','delete_others_posts'=>'manage_options',
        'edit_published_posts'=>'manage_options','create_posts'=>'manage_options'
    );
    register_post_type('pvdak_support_ticket',array(
        'labels'=>array('name'=>'پیام‌های پشتیبانی','singular_name'=>'پیام پشتیبانی',
            'menu_name'=>'پشتیبانی PVDak','edit_item'=>'پاسخ به پیام'),
        'public'=>false,'publicly_queryable'=>false,'exclude_from_search'=>true,
        'show_ui'=>true,'show_in_menu'=>true,'show_in_rest'=>false,'rewrite'=>false,
        'query_var'=>false,'capabilities'=>$admincaps,'map_meta_cap'=>false,
        'supports'=>array('title','editor','author'),'menu_icon'=>'dashicons-email-alt2'
    ));
}
add_action('init','pvdak_support_ticket_register');
/** Count Persian/Unicode characters rather than UTF-8 bytes when available. */
function pvdak_support_length($value) {
    if (function_exists('mb_strlen'))return mb_strlen($value, 'UTF-8');
    $count=preg_match_all('/./us',$value);
    return false===$count ? strlen($value) : $count;
}
/** Keep all client-visible status labels on an explicit allowlist. */
function pvdak_support_status_label($status) {
    $labels = array('open'=>'نیازمند پاسخ', 'answered'=>'پاسخ داده شده', 'closed'=>'بسته شده');
    return isset($labels[$status]) ? $labels[$status] : 'در انتظار بررسی';
}
function pvdak_support_status_key($status) {
    return in_array($status, array('open','answered','closed'), true) ? $status : 'open';
}

/**
 * Serialize ticket replies and status transitions on the same DB connection.
 * A missing/contended DB advisory lock fails closed; no transient is a mutex.
 */
function pvdak_support_ticket_lock($ticket_id) {
    global $wpdb;
    $ticket_id=absint($ticket_id);
    if (!$ticket_id || !isset($wpdb) || !is_object($wpdb))return false;
    $name='pvdak_support_ticket_'.$ticket_id;
    $held=$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 3)', $name));
    return (string)$held==='1' ? $name : false;
}
function pvdak_support_ticket_unlock($lock_name) {
    global $wpdb;
    if ($lock_name && isset($wpdb) && is_object($wpdb)) {
        $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
    }
}

function pvdak_support_ticket_form(){
    if(!is_user_logged_in()){return '<p>برای پشتیبانی ابتدا وارد حساب شوید.</p>';}
    $user=get_current_user_id();
    $raw_page=isset($_GET['ticket_page']) && is_scalar($_GET['ticket_page']) ? wp_unslash($_GET['ticket_page']) : '1';
    $page=max(1,min(1000,absint($raw_page)));
    $raw_status=isset($_GET['ticket_status']) && is_string($_GET['ticket_status']) ? sanitize_key(wp_unslash($_GET['ticket_status'])) : '';
    $filter=in_array($raw_status,array('open','answered','closed'),true) ? $raw_status : '';
    $raw_order=isset($_GET['ticket_order']) && is_string($_GET['ticket_order']) ? sanitize_key(wp_unslash($_GET['ticket_order'])) : '';
    $order='asc'===$raw_order ? 'ASC' : 'DESC';
    $raw_search=isset($_GET['ticket_search']) && is_string($_GET['ticket_search']) ? sanitize_text_field(wp_unslash($_GET['ticket_search'])) : '';
    $search=pvdak_support_length($raw_search)<=80 ? trim($raw_search) : '';
    $query=array('post_type'=>'pvdak_support_ticket','post_status'=>'private',
        'posts_per_page'=>11,'offset'=>($page-1)*10,'author'=>$user,
        'orderby'=>'date','order'=>$order,'no_found_rows'=>true,'suppress_filters'=>false);
    if($filter)$query['meta_query']=array(array('key'=>'_pvdak_support_status','value'=>$filter,'compare'=>'='));
    if($search!=='')$query['s']=$search;
    $items=get_posts($query);
    // Query hooks may modify the author, type or private-status constraints.
    // Apply ownership and model isolation again before counts, pagination or HTML.
    $items=array_values(array_filter(is_array($items)?$items:array(),static function($item)use($user){
        return $item instanceof WP_Post &&
            'pvdak_support_ticket'===$item->post_type &&
            'private'===$item->post_status &&
            (int)$item->post_author===$user;
    }));
    $filters=array();
    if($filter)$filters['ticket_status']=$filter;
    if('ASC'===$order)$filters['ticket_order']='asc';
    if($search!=='')$filters['ticket_search']=$search;
    $filtered_base=add_query_arg($filters,pvdak_auth_account_url());
    // In Plain permalink mode the account URL uses ?page_id=... or
    // ?pagename=.... Native GET forms replace action query parameters.
    // Preserve only the canonical WordPress Page selector as a hidden input.
    $account_route=array();
    $route_query=wp_parse_url(pvdak_auth_account_url(),PHP_URL_QUERY);
    if(is_string($route_query))parse_str($route_query,$account_route);
    $open_raw=isset($_GET['ticket_open']) && is_scalar($_GET['ticket_open']) ? wp_unslash($_GET['ticket_open']) : 0;
    $open_id=absint($open_raw);
    $has_next=count($items)>10;
    $items=array_slice($items,0,10);
    // A direct link to an owned ticket remains usable even after its status
    // changes and it no longer matches the currently selected filters.
    $focused_ticket=false;
    if($open_id && !in_array($open_id,wp_list_pluck($items,'ID'),true)) {
        $focused=get_post($open_id);
        if($focused && 'pvdak_support_ticket'===$focused->post_type &&
            'private'===$focused->post_status && (int)$focused->post_author===$user) {
            array_unshift($items,$focused);
            $focused_ticket=true;
        }
    }
    $url=admin_url('admin-post.php');
    ob_start(); ?>
    <section class="pvdak-support" aria-labelledby="pvdak-support-title">
      <h2 id="pvdak-support-title">پشتیبانی من</h2>
      <p>برای مشکلات حساب و استفاده از سایت پیام بفرستید؛ اطلاعات پرونده قضایی وارد نکنید.</p>
      <?php $notice=isset($_GET['support']) && is_string($_GET['support']) ? sanitize_key(wp_unslash($_GET['support'])) : ''; ?>
      <?php if(in_array($notice,array('sent','new_sent','followup_sent'),true)):?>
        <p role="status" class="pvdak-static-note" tabindex="-1" id="pvdak-support-notice"><?php echo esc_html('followup_sent'===$notice?'پاسخ شما به گفتگو اضافه شد.':'پیام پشتیبانی شما ثبت شد.'); ?></p>
      <?php elseif('slow'===$notice):?><p role="alert" class="pvdak-static-error" tabindex="-1" id="pvdak-support-notice">برای جلوگیری از ارسال تکراری، کمی بعد دوباره تلاش کنید.</p>
      <?php elseif('invalid'===$notice):?><p role="alert" class="pvdak-static-error" tabindex="-1" id="pvdak-support-notice">عنوان یا متن پیام معتبر نیست؛ طول ورودی را بررسی کنید.</p>
      <?php elseif('failed'===$notice):?><p role="alert" class="pvdak-static-error" tabindex="-1" id="pvdak-support-notice">ارسال پیام انجام نشد؛ دوباره تلاش کنید.</p><?php endif;?>
      <form class="pvdak-forms pvdak-ticket-new" data-pvdak-support-form action="<?php echo esc_url($url); ?>" method="post">
      <input type="hidden" name="action" value="pvdak_support_new">
      <?php wp_nonce_field('pvdak_support_new','_pvdak_support_nonce'); ?>
      <label for="support-subject">عنوان پیام</label><input id="support-subject" name="support_subject" minlength="3" maxlength="120" autocomplete="off" required>
      <label for="support-message">توضیح</label><textarea id="support-message" name="support_message" minlength="10" maxlength="2500" rows="4" aria-describedby="pvdak-ticket-privacy pvdak-ticket-count" required></textarea>
      <p id="pvdak-ticket-privacy" class="pvdak-ticket-privacy">فقط مشکلات حساب و سایت را شرح دهید؛ نام اشخاص، مدارک و اطلاعات پرونده قضایی را ارسال نکنید.</p>
      <small id="pvdak-ticket-count" class="pvdak-ticket-counter" data-pvdak-count-for="support-message">حداکثر ۲۵۰۰ نویسه</small>
      <button type="submit">ثبت پیام پشتیبانی</button></form>

      <h3 id="pvdak-ticket-list-title">پیام‌های پشتیبانی</h3>
      <form method="get" action="<?php echo esc_url(pvdak_auth_account_url()); ?>" class="pvdak-ticket-filters" role="search" aria-label="جستجو و فیلتر پیام‌های خودم">
        <?php if(isset($account_route['page_id']) && is_scalar($account_route['page_id']) && absint($account_route['page_id'])): ?>
          <input type="hidden" name="page_id" value="<?php echo esc_attr(absint($account_route['page_id'])); ?>">
        <?php elseif(isset($account_route['pagename']) && is_string($account_route['pagename']) && $account_route['pagename']!==''): ?>
          <input type="hidden" name="pagename" value="<?php echo esc_attr(sanitize_text_field($account_route['pagename'])); ?>">
        <?php endif; ?>
        <label for="pvdak-ticket-search">جستجو در پیام‌ها
          <input id="pvdak-ticket-search" type="search" name="ticket_search" value="<?php echo esc_attr($search); ?>" maxlength="80" placeholder="عنوان یا متن پیام">
        </label>
        <label for="pvdak-ticket-filter">وضعیت
          <select id="pvdak-ticket-filter" name="ticket_status">
            <option value="">همه وضعیت‌ها</option>
            <?php foreach(array('open','answered','closed') as $option): ?>
              <option value="<?php echo esc_attr($option); ?>" <?php selected($filter,$option); ?>><?php echo esc_html(pvdak_support_status_label($option)); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label for="pvdak-ticket-order">ترتیب
          <select id="pvdak-ticket-order" name="ticket_order">
            <option value="desc" <?php selected($order,'DESC'); ?>>جدیدترین</option>
            <option value="asc" <?php selected($order,'ASC'); ?>>قدیمی‌ترین</option>
          </select>
        </label>
        <button type="submit">اعمال فیلتر</button>
        <?php if($filters): ?><a class="pvdak-ticket-reset" href="<?php echo esc_url(pvdak_auth_account_url().'#pvdak-support'); ?>">پاک‌کردن فیلترها</a><?php endif; ?>
      </form>
      <p class="pvdak-ticket-result-summary" role="status">
        <?php echo esc_html(number_format_i18n(count($items)-($focused_ticket?1:0))); ?> پیام در این صفحه
        <?php if($filters): ?> (نتیجه فیلتر فعلی)<?php endif; ?>
        <?php if($focused_ticket): ?>؛ گفتگو انتخاب‌شده نیز به‌صورت جداگانه در ابتدای فهرست نمایش داده شده است.<?php endif; ?>
      </p>
      <?php if(!$items): ?>
        <p class="pvdak-ticket-empty"><?php echo esc_html($page>1?'در این صفحه پیامی وجود ندارد؛ به صفحه قبل بازگردید.':($filters?'پیامی با این شرایط پیدا نشد؛ فیلترها را تغییر دهید.':'هنوز پیامی ثبت نشده است؛ می‌توانید از فرم بالا اولین پیام خود را بفرستید.')); ?></p>
      <?php else: ?>
      <ul class="pvdak-ticket-list" aria-labelledby="pvdak-ticket-list-title"><?php foreach($items as $item):
        if(!($item instanceof WP_Post) || 'pvdak_support_ticket'!==$item->post_type ||
           'private'!==$item->post_status || (int)$item->post_author!==$user)continue;
        $reply=get_post_meta($item->ID,'_pvdak_support_reply',true);
        $state=pvdak_support_status_key(get_post_meta($item->ID,'_pvdak_support_status',true));
        $messages=get_post_meta($item->ID,'_pvdak_support_message',false);
        $message_count=is_array($messages)?count($messages):0;
        ?>
        <li id="pvdak-ticket-<?php echo esc_attr($item->ID); ?>" class="pvdak-ticket-item">
          <details class="pvdak-ticket-details"<?php echo $open_id===(int)$item->ID ? ' open' : ''; ?>>
            <summary class="pvdak-ticket-summary">
              <span class="pvdak-ticket-title"><?php echo esc_html(get_the_title($item->ID)); ?></span>
              <span class="pvdak-ticket-status pvdak-ticket-status--<?php echo esc_attr($state); ?>"><?php echo esc_html(pvdak_support_status_label($state)); ?></span>
              <small class="pvdak-ticket-date"><?php echo esc_html(get_the_date('j F Y',$item->ID)); ?></small>
              <?php if($message_count):?><small class="pvdak-ticket-count"><?php echo esc_html(number_format_i18n($message_count)); ?> پیام در گفتگو</small><?php endif;?>
            </summary>
            <div class="pvdak-ticket-body">
              <p class="pvdak-ticket-original"><strong>پیام اولیه شما</strong><br><?php echo esc_html(get_post_field('post_content',$item->ID)); ?></p>
              <?php if($messages): ?>
                <?php if($message_count>30):?><p class="pvdak-ticket-history-note">۳۰ پیام آخر این گفتگو نمایش داده می‌شود.</p><?php endif;?>
                <ol class="pvdak-ticket-conversation" aria-label="گفتگوی پشتیبانی">
                <?php foreach(array_slice($messages,-30) as $entry):
                  if(!is_array($entry) || !isset($entry['text'],$entry['role']) ||
                    !is_string($entry['text']) || !in_array($entry['role'],array('user','staff'),true))continue;
                  $timestamp=isset($entry['at']) && is_string($entry['at']) ? strtotime($entry['at'].' UTC') : false; ?>
                  <li class="<?php echo 'staff'===$entry['role']?'pvdak-ticket-staff':'pvdak-ticket-user'; ?>">
                    <strong><?php echo 'staff'===$entry['role']?'پشتیبانی':'شما'; ?></strong>
                    <?php if($timestamp):?><time datetime="<?php echo esc_attr(gmdate('c',$timestamp)); ?>"><?php echo esc_html(wp_date('j F Y، H:i',$timestamp)); ?></time><?php endif;?>
                    <p><?php echo esc_html($entry['text']); ?></p>
                  </li>
                <?php endforeach; ?></ol>
              <?php elseif(is_string($reply) && $reply!==''): ?><p class="pvdak-ticket-legacy-reply">پاسخ پشتیبانی: <?php echo esc_html($reply); ?></p><?php endif; ?>
              <?php if('closed'===$state): ?>
                <p class="pvdak-static-note">این گفتگو بسته شده است؛ برای موضوع تازه تیکت جدید ثبت کنید.</p>
              <?php else: ?>
                <form method="post" action="<?php echo esc_url($url); ?>" class="pvdak-forms pvdak-ticket-followup" data-pvdak-support-form>
                  <input type="hidden" name="action" value="pvdak_support_followup">
                  <input type="hidden" name="ticket_id" value="<?php echo esc_attr($item->ID); ?>">
                  <input type="hidden" name="ticket_page" value="<?php echo esc_attr($page); ?>">
                  <input type="hidden" name="ticket_status" value="<?php echo esc_attr($filter); ?>">
                  <input type="hidden" name="ticket_order" value="<?php echo esc_attr(strtolower($order)); ?>">
                  <input type="hidden" name="ticket_search" value="<?php echo esc_attr($search); ?>">
                  <?php wp_nonce_field('pvdak_support_followup_'.$item->ID,'_pvdak_followup_nonce'); ?>
                  <label for="pvdak-followup-<?php echo esc_attr($item->ID); ?>">پاسخ یا توضیح تکمیلی</label>
                  <textarea id="pvdak-followup-<?php echo esc_attr($item->ID); ?>" name="followup_body" minlength="2" maxlength="2500" rows="3" aria-describedby="pvdak-followup-count-<?php echo esc_attr($item->ID); ?>" required></textarea>
                  <small id="pvdak-followup-count-<?php echo esc_attr($item->ID); ?>" class="pvdak-ticket-counter" data-pvdak-count-for="pvdak-followup-<?php echo esc_attr($item->ID); ?>" aria-live="polite">حداکثر ۲۵۰۰ نویسه</small>
                  <p class="pvdak-ticket-privacy">در پاسخ تکمیلی نیز اطلاعات یا مدارک پرونده قضایی را وارد نکنید.</p>
                  <button type="submit">ارسال پاسخ</button>
                </form>
              <?php endif; ?>
            </div>
          </details>
        </li>
      <?php endforeach; ?></ul>
      <?php endif; ?>
      <?php if($page>1 || $has_next): ?>
      <nav class="pvdak-ticket-pagination" aria-label="صفحه‌بندی پیام‌های پشتیبانی">
        <?php if($page>1):?><a rel="prev" href="<?php echo esc_url(add_query_arg('ticket_page',$page-1,$filtered_base).'#pvdak-support'); ?>">صفحه قبل</a><?php endif;?>
        <span aria-current="page">صفحه <?php echo esc_html(number_format_i18n($page)); ?></span>
        <?php if($has_next):?><a rel="next" href="<?php echo esc_url(add_query_arg('ticket_page',$page+1,$filtered_base).'#pvdak-support'); ?>">صفحه بعد</a><?php endif;?>
      </nav>
      <?php endif; ?>
    </section>
    <?php return ob_get_clean();
}
add_shortcode('pvdak_support_tickets','pvdak_support_ticket_form');
function pvdak_support_new(){
    if(!is_user_logged_in()){wp_die('ابتدا وارد شوید.','',array('response'=>403));}
    if('POST'!==($_SERVER['REQUEST_METHOD']??'')){wp_die('Invalid method','',array('response'=>405));}
    check_admin_referer('pvdak_support_new','_pvdak_support_nonce');
    $base=pvdak_auth_account_url();
    $subjectRaw=wp_unslash($_POST['support_subject']??'');
    $bodyRaw=wp_unslash($_POST['support_message']??'');
    $subject=is_string($subjectRaw)?sanitize_text_field($subjectRaw):'';
    $body=is_string($bodyRaw)?sanitize_textarea_field($bodyRaw):'';
    $user=get_current_user_id();
    $key='pvdak_ticket_'. $user;
    $subject=trim($subject);
    $body=trim($body);
    if(pvdak_support_length($subject)<3||pvdak_support_length($subject)>120||
       pvdak_support_length($body)<10||pvdak_support_length($body)>2500){
        wp_safe_redirect(add_query_arg('support','invalid',$base).'#pvdak-support');exit;
    }
    if(get_transient($key)){
        wp_safe_redirect(add_query_arg('support','slow',$base).'#pvdak-support');exit;
    }
    set_transient($key,1,90);
    $id=wp_insert_post(array('post_type'=>'pvdak_support_ticket','post_status'=>'private',
        'post_author'=>$user,'post_title'=>$subject,'post_content'=>$body,
        'comment_status'=>'closed'),true);
    if(is_wp_error($id)||!$id){delete_transient($key);}
    elseif(!add_post_meta($id,'_pvdak_support_status','open',true)) {
        // Do not expose a ticket with an uninitialized workflow state.
        wp_delete_post($id,true);
        delete_transient($key);
        $id=0;
    }
    $success=!is_wp_error($id)&&$id;
    $destination=add_query_arg('support',$success?'new_sent':'failed',$base);
    if($success)$destination=add_query_arg('ticket_open',absint($id),$destination);
    wp_safe_redirect($destination.($success?'#pvdak-ticket-'.absint($id):'#pvdak-support'));exit;
}
add_action('admin_post_pvdak_support_new','pvdak_support_new');
/**
 * Atomic append-only message storage: no overwrite if staff and user reply
 * close together. The CPT is private and has no public REST endpoint.
 */
function pvdak_support_append_message($ticket_id, $actor, $body) {
    if (!in_array($actor,array('user','staff'),true) || !is_string($body))return false;
    $text=sanitize_textarea_field($body);
    $text=trim($text);
    if ($text==='' || pvdak_support_length($text)>2500)return false;
    return (bool)add_post_meta(absint($ticket_id),'_pvdak_support_message',array(
        'role'=>$actor,'text'=>$text,'at'=>current_time('mysql',true)
    ),false);
}
function pvdak_support_followup() {
    if (!is_user_logged_in())wp_die('ابتدا وارد حساب شوید.','',array('response'=>403));
    if ('POST'!==($_SERVER['REQUEST_METHOD']??''))wp_die('Invalid method','',array('response'=>405));
    $id_raw=wp_unslash($_POST['ticket_id']??'');
    $id=is_scalar($id_raw)?absint($id_raw):0;
    check_admin_referer('pvdak_support_followup_'.$id,'_pvdak_followup_nonce');
    $ticket=$id?get_post($id):null;
    if (!$ticket || 'pvdak_support_ticket'!==$ticket->post_type ||
        'private'!==$ticket->post_status || (int)$ticket->post_author!==get_current_user_id() ||
        'closed'===get_post_meta($id,'_pvdak_support_status',true)) {
        wp_die('دسترسی به این پیام مجاز نیست.','',array('response'=>403));
    }
    $raw=wp_unslash($_POST['followup_body']??'');
    $text=is_string($raw)?trim(sanitize_textarea_field($raw)):'';
    $key='pvdak_followup_'.get_current_user_id().'_'.$id;
    $ok=false;
    if (pvdak_support_length($text)>=2 && pvdak_support_length($text)<=2500) {
        $lock=pvdak_support_ticket_lock($id);
        if ($lock!==false) {
            try {
                // Recheck ownership and closure under the same lock as staff updates.
                $current=get_post($id);
                if ($current && 'pvdak_support_ticket'===$current->post_type &&
                    'private'===$current->post_status &&
                    (int)$current->post_author===get_current_user_id() &&
                    'closed'!==get_post_meta($id,'_pvdak_support_status',true) &&
                    !get_transient($key)) {
                    set_transient($key,'1',45);
                    $ok=pvdak_support_append_message($id,'user',$text);
                    if ($ok) {
                        $previous=pvdak_support_status_key(get_post_meta($id,'_pvdak_support_status',true));
                        if ($previous!=='open') {
                            $changed=update_post_meta($id,'_pvdak_support_status','open');
                            if (!$changed) {
                                $ok=false;
                            } else {
                                add_post_meta($id,'_pvdak_support_status_event',array(
                                    'from'=>$previous,'to'=>'open','at'=>current_time('mysql',true),
                                    'actor'=>get_current_user_id()
                                ),false);
                            }
                        }
                    }
                    if (!$ok)delete_transient($key);
                }
            } finally {
                pvdak_support_ticket_unlock($lock);
            }
        }
    }
    // Rebuild return URL from allowlisted controls, never accept a client-supplied URL.
    $return=array('support'=>$ok?'followup_sent':'failed');
    $posted_page=isset($_POST['ticket_page']) && is_scalar($_POST['ticket_page']) ? absint(wp_unslash($_POST['ticket_page'])) : 1;
    if($posted_page>1 && $posted_page<=1000)$return['ticket_page']=$posted_page;
    $posted_status=isset($_POST['ticket_status']) && is_string($_POST['ticket_status']) ? sanitize_key(wp_unslash($_POST['ticket_status'])) : '';
    if(in_array($posted_status,array('open','answered','closed'),true))$return['ticket_status']=$posted_status;
    $posted_order=isset($_POST['ticket_order']) && is_string($_POST['ticket_order']) ? sanitize_key(wp_unslash($_POST['ticket_order'])) : '';
    if('asc'===$posted_order)$return['ticket_order']='asc';
    $posted_search=isset($_POST['ticket_search']) && is_string($_POST['ticket_search']) ? sanitize_text_field(wp_unslash($_POST['ticket_search'])) : '';
    if($posted_search!=='' && pvdak_support_length($posted_search)<=80)$return['ticket_search']=$posted_search;
    $return['ticket_open']=$id;
    wp_safe_redirect(add_query_arg($return,pvdak_auth_account_url()).'#pvdak-ticket-'.$id);
    exit;
}
add_action('admin_post_pvdak_support_followup','pvdak_support_followup');

function pvdak_support_reply_metabox(){
    add_meta_box('pvdak_support_reply_box','پاسخ پشتیبانی','pvdak_support_reply_box_html','pvdak_support_ticket','normal','default');
}
add_action('add_meta_boxes','pvdak_support_reply_metabox');
function pvdak_support_reply_box_html($post){
    if(!current_user_can('manage_options'))return;
    wp_nonce_field('pvdak_support_reply_'.$post->ID,'_pvdak_reply_nonce');
    $reply=get_post_meta($post->ID,'_pvdak_support_reply',true);
    echo '<label for="pvdak_support_answer">پاسخ قابل مشاهده فقط برای فرستنده</label>';
    echo '<textarea id="pvdak_support_answer" name="pvdak_support_answer" style="width:100%" rows="5" maxlength="2500" placeholder="پاسخ جدید را اینجا بنویسید؛ متن قبلی دوباره ارسال نمی‌شود."></textarea>';
    echo '<p class="description">این گفتگو فقط برای صاحب حساب و مدیر قابل مشاهده است. مدارک پرونده قضایی را اینجا دریافت نکنید.</p>';
    if(is_string($reply) && $reply!=='')echo '<p><strong>آخرین پاسخ ثبت‌شده:</strong> '.esc_html($reply).'</p>';
    $thread=get_post_meta($post->ID,'_pvdak_support_message',false);
    if(is_array($thread) && $thread) {
        echo '<h4>تاریخچه گفتگو (حداکثر ۳۰ پیام اخیر)</h4><ol style="max-height:360px;overflow:auto;direction:rtl">';
        foreach(array_slice($thread,-30) as $entry) {
            if(!is_array($entry) || !isset($entry['role'],$entry['text']) || !is_string($entry['text']) || !in_array($entry['role'],array('staff','user'),true))continue;
            $who='staff'===$entry['role']?'پشتیبانی':'کاربر';
            $when=isset($entry['at']) && is_string($entry['at']) ? $entry['at'] : '';
            echo '<li style="margin:8px 0;padding:8px;border:1px solid #ddd"><strong>'.esc_html($who).'</strong> <small>'.esc_html($when).'</small><p style="white-space:pre-wrap">'.esc_html($entry['text']).'</p></li>';
        }
        echo '</ol>';
    }
    $events=get_post_meta($post->ID,'_pvdak_support_status_event',false);
    if(is_array($events) && $events) {
        echo '<details><summary>تاریخچه تغییر وضعیت</summary><ol>';
        foreach(array_slice($events,-15) as $event) {
            if(!is_array($event))continue;
            echo '<li>'.esc_html(pvdak_support_status_label($event['from']??'')).' ← '.esc_html(pvdak_support_status_label($event['to']??'')).' — '.esc_html($event['at']??'').'</li>';
        }
        echo '</ol></details>';
    }
    $state=pvdak_support_status_key(get_post_meta($post->ID,'_pvdak_support_status',true));
    echo '<input type="hidden" name="pvdak_support_previous_state" value="'.esc_attr($state).'">';
    echo '<p><label for="pvdak_support_state">وضعیت گفتگو</label><select id="pvdak_support_state" name="pvdak_support_state">';
    foreach(array('open'=>'نیازمند پاسخ','answered'=>'پاسخ داده شده','closed'=>'بسته شده') as $key=>$label){
        echo '<option value="'.esc_attr($key).'" '.selected($state,$key,false).'>'.esc_html($label).'</option>';
    }
    echo '</select></p>';
}
function pvdak_support_save_reply($post_id,$post){
    static $processed=array();
    if(isset($processed[$post_id]))return;
    if(!current_user_can('manage_options') || !current_user_can('edit_post',$post_id) ||
        'pvdak_support_ticket'!==get_post_type($post_id) ||
        'private'!==get_post_status($post_id) ||
        wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) ||
        !isset($_POST['_pvdak_reply_nonce']) || !is_string($_POST['_pvdak_reply_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_pvdak_reply_nonce'])),'pvdak_support_reply_'.$post_id))return;
    $raw=wp_unslash($_POST['pvdak_support_answer']??'');
    $raw_state=wp_unslash($_POST['pvdak_support_state']??'');
    $raw_previous=wp_unslash($_POST['pvdak_support_previous_state']??'');
    if(!is_string($raw) || !is_string($raw_state) || !is_string($raw_previous))return;
    $reply=trim(sanitize_textarea_field($raw));
    if (pvdak_support_length($reply)>2500)return;
    $requested_state=sanitize_key($raw_state);
    $expected_state=sanitize_key($raw_previous);
    if (!in_array($requested_state,array('open','answered','closed'),true) ||
        !in_array($expected_state,array('open','answered','closed'),true))return;
    $lock=pvdak_support_ticket_lock($post_id);
    if ($lock===false)return;
    $notify=false;
    try {
        // Reject a stale admin tab and never resurrect a trashed ticket.
        if ('private'!==get_post_status($post_id))return;
        $previous=pvdak_support_status_key(get_post_meta($post_id,'_pvdak_support_status',true));
        if ($previous!==$expected_state)return;
        $processed[$post_id]=true;
        if ($reply!=='') {
            if (!pvdak_support_append_message($post_id,'staff',$reply))return;
        }
        $state=$requested_state;
        // Sending a staff reply resolves the waiting state unless the operator closes it.
        if($reply!=='' && 'open'===$state)$state='answered';
        if($reply!=='')update_post_meta($post_id,'_pvdak_support_reply',$reply);
        if ($state!==$previous) {
            if (!update_post_meta($post_id,'_pvdak_support_status',$state))return;
            if (!add_post_meta($post_id,'_pvdak_support_status_event',array(
                'from'=>$previous,'to'=>$state,'at'=>current_time('mysql',true),
                'actor'=>get_current_user_id()
            ),false))return;
        }
        $notify=$reply!=='' || $state!==$previous;
    } finally {
        pvdak_support_ticket_unlock($lock);
    }
    // Sending outside the DB lock avoids holding it during mail transport.
    if($notify && function_exists('pvdak_core_notify_owner')) {
        pvdak_core_notify_owner($post_id,'pvdak_support_ticket');
    }
}
add_action('save_post_pvdak_support_ticket','pvdak_support_save_reply',10,2);

/** Staff-only list columns, with no ticket text or user email in the list. */
function pvdak_support_admin_columns($columns) {
    if(!current_user_can('manage_options'))return $columns;
    $columns['pvdak_support_status']='وضعیت گفتگو';
    $columns['pvdak_support_message_count']='پیام‌ها';
    return $columns;
}
add_filter('manage_pvdak_support_ticket_posts_columns','pvdak_support_admin_columns');
function pvdak_support_admin_column($column,$post_id) {
    if(!current_user_can('manage_options'))return;
    if('pvdak_support_status'===$column){
        $state=get_post_meta($post_id,'_pvdak_support_status',true);
        $labels=array('open'=>'نیازمند پاسخ','answered'=>'پاسخ داده‌شده','closed'=>'بسته‌شده');
        echo esc_html($labels[$state]??'در انتظار بررسی');
    } elseif('pvdak_support_message_count'===$column){
        echo esc_html(count(get_post_meta($post_id,'_pvdak_support_message',false)));
    }
}
add_action('manage_pvdak_support_ticket_posts_custom_column','pvdak_support_admin_column',10,2);

/** Admin-only status filter; never broadens access to private ticket records. */
function pvdak_support_admin_status_filter($post_type) {
    if('pvdak_support_ticket'!==$post_type || !current_user_can('manage_options'))return;
    $chosen=isset($_GET['pvdak_ticket_status']) && is_string($_GET['pvdak_ticket_status']) ?
        sanitize_key(wp_unslash($_GET['pvdak_ticket_status'])) : '';
    echo '<label class="screen-reader-text" for="pvdak-ticket-status-filter">فیلتر وضعیت گفتگو</label>';
    echo '<select id="pvdak-ticket-status-filter" name="pvdak_ticket_status"><option value="">همه وضعیت‌ها</option>';
    foreach(array('open'=>'نیازمند پاسخ','answered'=>'پاسخ داده شده','closed'=>'بسته شده') as $key=>$label) {
        echo '<option value="'.esc_attr($key).'" '.selected($chosen,$key,false).'>'.esc_html($label).'</option>';
    }
    echo '</select>';
}
add_action('restrict_manage_posts','pvdak_support_admin_status_filter');
function pvdak_support_admin_filter_query($query) {
    if(!is_admin() || !$query->is_main_query() || !current_user_can('manage_options') ||
       'pvdak_support_ticket'!==$query->get('post_type'))return;
    $raw=isset($_GET['pvdak_ticket_status']) && is_string($_GET['pvdak_ticket_status']) ?
        sanitize_key(wp_unslash($_GET['pvdak_ticket_status'])) : '';
    if(in_array($raw,array('open','answered','closed'),true)) {
        $query->set('meta_query',array(array('key'=>'_pvdak_support_status','value'=>$raw,'compare'=>'=')));
    }
}
add_action('pre_get_posts','pvdak_support_admin_filter_query');
