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
function pvdak_support_ticket_form(){
    if(!is_user_logged_in()){return '<p>برای پشتیبانی ابتدا وارد حساب شوید.</p>';}
    $user=get_current_user_id();
    $items=get_posts(array('post_type'=>'pvdak_support_ticket','post_status'=>'private',
        'posts_per_page'=>10,'author'=>$user,'orderby'=>'date','order'=>'DESC',
        'no_found_rows'=>true,'suppress_filters'=>false));
    $url=admin_url('admin-post.php');
    ob_start(); ?>
    <section id="pvdak-support" class="pvdak-support" aria-labelledby="pvdak-support-title">
      <h2 id="pvdak-support-title">پشتیبانی من</h2>
      <p>برای مشکلات حساب و استفاده از سایت پیام بفرستید؛ اطلاعات پرونده قضایی وارد نکنید.</p>
      <?php if(isset($_GET['support']) && 'sent'===(string)$_GET['support']):?><p role="status" class="pvdak-static-note">پیام شما ثبت شد.</p><?php endif;?>
      <?php if(isset($_GET['support']) && 'failed'===(string)$_GET['support']):?><p role="alert" class="pvdak-static-error">ثبت پیام انجام نشد؛ دوباره تلاش کنید.</p><?php endif;?>
      <form class="pvdak-forms" action="<?php echo esc_url($url); ?>" method="post">
      <input type="hidden" name="action" value="pvdak_support_new">
      <?php wp_nonce_field('pvdak_support_new','_pvdak_support_nonce'); ?>
      <label for="support-subject">عنوان پیام</label><input id="support-subject" name="support_subject" maxlength="120" required>
      <label for="support-message">توضیح</label><textarea id="support-message" name="support_message" minlength="10" maxlength="2500" rows="4" required></textarea>
      <button type="submit">ثبت پیام پشتیبانی</button></form>
      <h3>پیام‌های اخیر</h3>
      <?php if(!$items): ?><p>هنوز پیامی ثبت نشده است.</p><?php else: ?>
      <ul class="pvdak-ticket-list"><?php foreach($items as $item):
        if((int)$item->post_author!==$user)continue;
        $reply=get_post_meta($item->ID,'_pvdak_support_reply',true);
        $state=get_post_meta($item->ID,'_pvdak_support_status',true);
        ?>
        <li><strong><?php echo esc_html(get_the_title($item->ID)); ?></strong>
        <small><?php echo esc_html('answered'===$state?'پاسخ داده شده':'در انتظار بررسی'); ?></small>
        <p class="pvdak-ticket-original"><?php echo esc_html(get_post_field('post_content',$item->ID)); ?></p>
        <?php $messages=get_post_meta($item->ID,'_pvdak_support_message',false);
          if($messages): ?>
          <ol class="pvdak-ticket-conversation" aria-label="گفتگوی پشتیبانی">
          <?php foreach(array_slice($messages,-30) as $entry):
            if(!is_array($entry) || !isset($entry['text'],$entry['role']) ||
               !is_string($entry['text']) || !in_array($entry['role'],array('user','staff'),true))continue; ?>
            <li><strong><?php echo 'staff'===$entry['role']?'پشتیبانی':'شما'; ?></strong>
            <span><?php echo esc_html($entry['text']); ?></span></li>
          <?php endforeach; ?></ol>
        <?php elseif(is_string($reply) && $reply!==''): ?><p>پاسخ پشتیبانی: <?php echo esc_html($reply); ?></p><?php endif; ?>
        <form method="post" action="<?php echo esc_url($url); ?>" class="pvdak-forms pvdak-ticket-followup">
          <input type="hidden" name="action" value="pvdak_support_followup">
          <input type="hidden" name="ticket_id" value="<?php echo esc_attr($item->ID); ?>">
          <?php wp_nonce_field('pvdak_support_followup_'.$item->ID,'_pvdak_followup_nonce'); ?>
          <label for="pvdak-followup-<?php echo esc_attr($item->ID); ?>">پاسخ یا توضیح تکمیلی</label>
          <textarea id="pvdak-followup-<?php echo esc_attr($item->ID); ?>" name="followup_body" minlength="2" maxlength="2500" rows="3" required></textarea>
          <button type="submit">ارسال پاسخ</button>
        </form></li>
      <?php endforeach; ?></ul><?php endif;?>
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
    if(!trim($subject)||strlen($subject)>120||strlen($body)<10||strlen($body)>2500||get_transient($key)){
        wp_safe_redirect(add_query_arg('support','failed',$base).'#pvdak-support');exit;
    }
    set_transient($key,1,90);
    $id=wp_insert_post(array('post_type'=>'pvdak_support_ticket','post_status'=>'private',
        'post_author'=>$user,'post_title'=>$subject,'post_content'=>$body,
        'comment_status'=>'closed'),true);
    if(is_wp_error($id)||!$id){delete_transient($key);}
    else{update_post_meta($id,'_pvdak_support_status','open');}
    wp_safe_redirect(add_query_arg('support',is_wp_error($id)||!$id?'failed':'sent',$base).'#pvdak-support');exit;
}
add_action('admin_post_pvdak_support_new','pvdak_support_new');
/**
 * Atomic append-only message storage: no overwrite if staff and user reply
 * close together. The CPT is private and has no public REST endpoint.
 */
function pvdak_support_append_message($ticket_id, $actor, $body) {
    if (!in_array($actor,array('user','staff'),true) || !is_string($body))return false;
    $text=sanitize_textarea_field($body);
    if ($text==='' || strlen($text)>2500)return false;
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
        'private'!==$ticket->post_status || (int)$ticket->post_author!==get_current_user_id()) {
        wp_die('دسترسی به این پیام مجاز نیست.','',array('response'=>403));
    }
    $raw=wp_unslash($_POST['followup_body']??'');
    $text=is_string($raw)?sanitize_textarea_field($raw):'';
    $key='pvdak_followup_'.get_current_user_id().'_'.$id;
    $ok=false;
    if (strlen($text)>=2 && strlen($text)<=2500 && !get_transient($key)) {
        set_transient($key,'1',45);
        $ok=pvdak_support_append_message($id,'user',$text);
        if($ok)update_post_meta($id,'_pvdak_support_status','open');
        else delete_transient($key);
    }
    wp_safe_redirect(add_query_arg('support',$ok?'sent':'failed',pvdak_auth_account_url()).'#pvdak-support');
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
}
function pvdak_support_save_reply($post_id,$post){
    if(!current_user_can('manage_options')||wp_is_post_revision($post_id)||
        !isset($_POST['_pvdak_reply_nonce']) || !is_string($_POST['_pvdak_reply_nonce'])||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_pvdak_reply_nonce'])),'pvdak_support_reply_'.$post_id))return;
    $raw=wp_unslash($_POST['pvdak_support_answer']??'');
    if(!is_string($raw))return;
    $reply=sanitize_textarea_field($raw);
    if (strlen($reply)>2500)return;
    $old=get_post_meta($post_id,'_pvdak_support_reply',true);
    // Editing other ticket properties does not send a duplicate response.
    if ($reply !== $old && $reply !== '') {
        if (!pvdak_support_append_message($post_id,'staff',$reply))return;
    }
    update_post_meta($post_id,'_pvdak_support_reply',$reply);
    update_post_meta($post_id,'_pvdak_support_status', $reply?'answered':'open');
}
add_action('save_post_pvdak_support_ticket','pvdak_support_save_reply',10,2);
