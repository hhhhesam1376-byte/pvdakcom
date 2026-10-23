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

function pvdak_support_ticket_form(){
    if(!is_user_logged_in()){return '<p>برای پشتیبانی ابتدا وارد حساب شوید.</p>';}
    $user=get_current_user_id();
    $raw_page=isset($_GET['ticket_page']) && is_scalar($_GET['ticket_page']) ? wp_unslash($_GET['ticket_page']) : '1';
    $page=max(1,min(1000,absint($raw_page)));
    $items=get_posts(array('post_type'=>'pvdak_support_ticket','post_status'=>'private',
        'posts_per_page'=>11,'offset'=>($page-1)*10,'author'=>$user,
        'orderby'=>'date','order'=>'DESC','no_found_rows'=>true,'suppress_filters'=>false));
    $has_next=count($items)>10;
    $items=array_slice($items,0,10);
    $url=admin_url('admin-post.php');
    ob_start(); ?>
    <section id="pvdak-support" class="pvdak-support" aria-labelledby="pvdak-support-title">
      <h2 id="pvdak-support-title">پشتیبانی من</h2>
      <p>برای مشکلات حساب و استفاده از سایت پیام بفرستید؛ اطلاعات پرونده قضایی وارد نکنید.</p>
      <?php $notice=isset($_GET['support']) && is_string($_GET['support']) ? sanitize_key(wp_unslash($_GET['support'])) : ''; ?>
      <?php if(in_array($notice,array('sent','new_sent','followup_sent'),true)):?>
        <p role="status" class="pvdak-static-note" tabindex="-1" id="pvdak-support-notice"><?php echo esc_html('followup_sent'===$notice?'پاسخ شما به گفتگو اضافه شد.':'پیام پشتیبانی شما ثبت شد.'); ?></p>
      <?php elseif('failed'===$notice):?><p role="alert" class="pvdak-static-error" tabindex="-1" id="pvdak-support-notice">ارسال پیام انجام نشد؛ اطلاعات را بررسی کنید و دوباره تلاش کنید.</p><?php endif;?>
      <form class="pvdak-forms pvdak-ticket-new" data-pvdak-support-form action="<?php echo esc_url($url); ?>" method="post">
      <input type="hidden" name="action" value="pvdak_support_new">
      <?php wp_nonce_field('pvdak_support_new','_pvdak_support_nonce'); ?>
      <label for="support-subject">عنوان پیام</label><input id="support-subject" name="support_subject" minlength="3" maxlength="120" autocomplete="off" required>
      <label for="support-message">توضیح</label><textarea id="support-message" name="support_message" minlength="10" maxlength="2500" rows="4" aria-describedby="pvdak-ticket-privacy pvdak-ticket-count" required></textarea>
      <p id="pvdak-ticket-privacy" class="pvdak-ticket-privacy">فقط مشکلات حساب و سایت را شرح دهید؛ نام اشخاص، مدارک و اطلاعات پرونده قضایی را ارسال نکنید.</p>
      <small id="pvdak-ticket-count" class="pvdak-ticket-counter" data-pvdak-count-for="support-message">حداکثر ۲۵۰۰ نویسه</small>
      <button type="submit">ثبت پیام پشتیبانی</button></form>

      <h3 id="pvdak-ticket-list-title">پیام‌های اخیر</h3>
      <?php if(!$items): ?>
        <p class="pvdak-ticket-empty"><?php echo $page>1?'در این صفحه پیامی وجود ندارد؛ به صفحه قبل بازگردید.':'هنوز پیامی ثبت نشده است؛ می‌توانید از فرم بالا اولین پیام خود را بفرستید.'; ?></p>
      <?php else: ?>
      <ul class="pvdak-ticket-list" aria-labelledby="pvdak-ticket-list-title"><?php foreach($items as $item):
        if((int)$item->post_author!==$user)continue;
        $reply=get_post_meta($item->ID,'_pvdak_support_reply',true);
        $state=pvdak_support_status_key(get_post_meta($item->ID,'_pvdak_support_status',true));
        $messages=get_post_meta($item->ID,'_pvdak_support_message',false);
        $message_count=is_array($messages)?count($messages):0;
        ?>
        <li id="pvdak-ticket-<?php echo esc_attr($item->ID); ?>" class="pvdak-ticket-item">
          <details class="pvdak-ticket-details">
            <summary class="pvdak-ticket-summary">
              <span class="pvdak-ticket-title"><?php echo esc_html(get_the_title($item->ID)); ?></span>
              <span class="pvdak-ticket-status pvdak-ticket-status--<?php echo esc_attr($state); ?>"><?php echo esc_html(pvdak_support_status_label($state)); ?></span>
              <small class="pvdak-ticket-date"><?php echo esc_html(get_the_date('j F Y',$item->ID)); ?></small>
              <?php if($message_count):?><small class="pvdak-ticket-count"><?php echo esc_html(number_format_i18n($message_count)); ?> پیام در گفتگو</small><?php endif;?>
            </summary>
            <div class="pvdak-ticket-body">
              <p class="pvdak-ticket-original"><?php echo esc_html(get_post_field('post_content',$item->ID)); ?></p>
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
                  <?php wp_nonce_field('pvdak_support_followup_'.$item->ID,'_pvdak_followup_nonce'); ?>
                  <label for="pvdak-followup-<?php echo esc_attr($item->ID); ?>">پاسخ یا توضیح تکمیلی</label>
                  <textarea id="pvdak-followup-<?php echo esc_attr($item->ID); ?>" name="followup_body" minlength="2" maxlength="2500" rows="3" aria-describedby="pvdak-followup-count-<?php echo esc_attr($item->ID); ?>" required></textarea>
                  <small id="pvdak-followup-count-<?php echo esc_attr($item->ID); ?>" class="pvdak-ticket-counter" data-pvdak-count-for="pvdak-followup-<?php echo esc_attr($item->ID); ?>">حداکثر ۲۵۰۰ نویسه</small>
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
        <?php if($page>1):?><a rel="prev" href="<?php echo esc_url(add_query_arg('ticket_page',$page-1,pvdak_auth_account_url()).'#pvdak-support'); ?>">صفحه قبل</a><?php endif;?>
        <span aria-current="page">صفحه <?php echo esc_html(number_format_i18n($page)); ?></span>
        <?php if($has_next):?><a rel="next" href="<?php echo esc_url(add_query_arg('ticket_page',$page+1,pvdak_auth_account_url()).'#pvdak-support'); ?>">صفحه بعد</a><?php endif;?>
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
       pvdak_support_length($body)<10||pvdak_support_length($body)>2500||get_transient($key)){
        wp_safe_redirect(add_query_arg('support','failed',$base).'#pvdak-support');exit;
    }
    set_transient($key,1,90);
    $id=wp_insert_post(array('post_type'=>'pvdak_support_ticket','post_status'=>'private',
        'post_author'=>$user,'post_title'=>$subject,'post_content'=>$body,
        'comment_status'=>'closed'),true);
    if(is_wp_error($id)||!$id){delete_transient($key);}
    else{update_post_meta($id,'_pvdak_support_status','open');}
    wp_safe_redirect(add_query_arg('support',is_wp_error($id)||!$id?'failed':'new_sent',$base).'#pvdak-support');exit;
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
    if (pvdak_support_length($text)>=2 && pvdak_support_length($text)<=2500 && !get_transient($key)) {
        set_transient($key,'1',45);
        $ok=pvdak_support_append_message($id,'user',$text);
        if($ok)update_post_meta($id,'_pvdak_support_status','open');
        else delete_transient($key);
    }
    wp_safe_redirect(add_query_arg('support',$ok?'followup_sent':'failed',pvdak_auth_account_url()).'#pvdak-support');
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
    echo '<textarea id="pvdak_support_answer" name="pvdak_support_answer" style="width:100%" rows="5">'.esc_textarea($reply).'</textarea>';
    $state=get_post_meta($post->ID,'_pvdak_support_status',true);
    echo '<p><label for="pvdak_support_state">وضعیت گفتگو</label><select id="pvdak_support_state" name="pvdak_support_state">';
    foreach(array('open'=>'نیازمند پاسخ','answered'=>'پاسخ داده شده','closed'=>'بسته شده') as $key=>$label){
        echo '<option value="'.esc_attr($key).'" '.selected($state,$key,false).'>'.esc_html($label).'</option>';
    }
    echo '</select></p>';
}
function pvdak_support_save_reply($post_id,$post){
    if(!current_user_can('manage_options')||wp_is_post_revision($post_id)||
        !isset($_POST['_pvdak_reply_nonce']) || !is_string($_POST['_pvdak_reply_nonce'])||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_pvdak_reply_nonce'])),'pvdak_support_reply_'.$post_id))return;
    $raw=wp_unslash($_POST['pvdak_support_answer']??'');
    if(!is_string($raw))return;
    $reply=sanitize_textarea_field($raw);
    if (pvdak_support_length($reply)>2500)return;
    $old=get_post_meta($post_id,'_pvdak_support_reply',true);
    // Editing other ticket properties does not send a duplicate response.
    if ($reply !== $old && $reply !== '') {
        if (!pvdak_support_append_message($post_id,'staff',$reply))return;
    }
    $raw_state=wp_unslash($_POST['pvdak_support_state']??'');
    $requested_state=is_string($raw_state)?sanitize_key($raw_state):'';
    $state=in_array($requested_state,array('open','answered','closed'),true)?
        $requested_state:($reply?'answered':'open');
    update_post_meta($post_id,'_pvdak_support_reply',$reply);
    update_post_meta($post_id,'_pvdak_support_status',$state);
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
