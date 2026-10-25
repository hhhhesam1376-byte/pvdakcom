<?php
/** Static regression for ticket/account experience; does not touch WordPress or DB. */
$root=dirname(__DIR__,2);
$files=array(
  'support'=>'inc/pvdak-support-tickets.php',
  'js'=>'assets/js/pvdak-account.js',
  'css'=>'assets/css/pvdak-support.css',
  'assets'=>'functions.php'
);
$s=array();
foreach($files as $key=>$path) {
  $full=$root.'/'.$path;
  if(!is_readable($full)){fwrite(STDERR,"TICKET_UX_FAIL: missing ".$path."\n");exit(2);}
  $s[$key]=file_get_contents($full);
}
$checks=array(
 'status allowlist'=>strpos($s['support'],'function pvdak_support_status_key(')!==false,
 'status badges'=>strpos($s['support'],'pvdak-ticket-status--')!==false,
 'creation date'=>strpos($s['support'],"get_the_date('j F Y'")!==false,
 'conversation count'=>strpos($s['support'],'number_format_i18n($message_count)')!==false,
 'native disclosure'=>strpos($s['support'],'class="pvdak-ticket-details"')!==false,
 'timestamp'=>strpos($s['support'],"wp_date('j F Y، H:i'")!==false,
 'separate roles'=>strpos($s['support'],'pvdak-ticket-staff')!==false,
 'history limit notice'=>strpos($s['support'],'$message_count>30')!==false,
 'private pagination'=>strpos($s['support'],"'posts_per_page'=>11")!==false && strpos($s['support'],"'author'=>\$user")!==false,
 'next and previous'=>strpos($s['support'],"add_query_arg('ticket_page',\$page+1")!==false && strpos($s['support'],"add_query_arg('ticket_page',\$page-1")!==false,
 'empty state'=>strpos($s['support'],'pvdak-ticket-empty')!==false,
 'distinct notifications'=>strpos($s['support'],"'followup_sent'")!==false && strpos($s['support'],"'new_sent'")!==false,
 'unicode length'=>strpos($s['support'],"mb_strlen(\$value, 'UTF-8')")!==false,
 'trim messages'=>strpos($s['support'],'$body=trim($body);')!==false,
 'privacy hint'=>strpos($s['support'],'pvdak-ticket-privacy')!==false,
 'new counter'=>strpos($s['support'],'data-pvdak-count-for="support-message"')!==false,
 'reply counter'=>strpos($s['support'],'data-pvdak-count-for="pvdak-followup-')!==false,
 'double submit protection'=>strpos($s['js'],"button.disabled = true")!==false,
 'account nav active section'=>strpos($s['js'],"window.addEventListener('hashchange'")!==false,
 'private status filter'=>strpos($s['support'],"'meta_query'")!==false && strpos($s['support'],"'author'=>\$user")!==false,
 'ticket keyword search'=>strpos($s['support'],"\$query['s']=\$search")!==false,
 'ticket search limit'=>strpos($s['support'],"pvdak_support_length(\$raw_search)<=80")!==false,
 'ticket sort allowlist'=>strpos($s['support'],"'asc'===\$raw_order")!==false,
 'filtered page navigation'=>strpos($s['support'],"\$filtered_base")!==false,
 'filter reset'=>strpos($s['support'],'pvdak-ticket-reset')!==false,
 'filter empty state'=>strpos($s['support'],'پیامی با این شرایط پیدا نشد')!==false,
 'filtered page count'=>strpos($s['support'],'pvdak-ticket-result-summary')!==false,
 'ticket deep link'=>strpos($s['support'],"\$open_id===(int)\$item->ID")!==false,
 'return URL allowlist'=>strpos($s['support'],'$return=array(')!==false && strpos($s['support'],"\$return['ticket_open']=\$id")!==false,
 'followup context fields'=>strpos($s['support'],'name="ticket_page"')!==false && strpos($s['support'],'name="ticket_search"')!==false,
 'reply privacy'=>strpos($s['support'],'در پاسخ تکمیلی نیز')!==false,
 'staff thread history'=>strpos($s['support'],'تاریخچه گفتگو (حداکثر ۳۰ پیام اخیر)')!==false,
 'staff reply blank composer'=>strpos($s['support'],'placeholder="پاسخ جدید را اینجا بنویسید')!==false,
 'staff reply length cap'=>strpos($s['support'],'maxlength="2500" placeholder=')!==false,
 'staff auto answered'=>strpos($s['support'],"if(\$reply!=='' && 'open'===\$state)")!==false,
 'status audit trail'=>strpos($s['support'],'_pvdak_support_status_event')!==false,
 'autosave guard'=>strpos($s['support'],'wp_is_post_autosave')!==false,
 'dynamic counter limit'=>strpos($s['js'],'field.maxLength > 0')!==false,
 'near limit visual'=>strpos($s['css'],'.pvdak-ticket-counter--near')!==false,
 'focus server notice'=>strpos($s['js'],"params.has('support')")!==false,
 'ticket lists enforce private owner and model after query filters'=>
  strpos($s['support'],'$items=array_values(array_filter(is_array($items)?$items:array()')!==false &&
  strpos($s['support'],"'pvdak_support_ticket'===\$item->post_type")!==false &&
  strpos($s['support'],"'private'===\$item->post_status")!==false &&
  strpos($s['support'],"(int)\$item->post_author===\$user")!==false &&
  strpos($s['support'],'$items=array_values(array_filter(') <
  strpos($s['support'],'$has_next=count($items)>10'),
 'no duplicate account support target on a single dashboard page'=>
  strpos($s['support'],'<section id="pvdak-support"')===false &&
  strpos($s['support'],'class="pvdak-support" aria-labelledby="pvdak-support-title"')!==false,
 'per-owner support creation uses DB lock and rate ceiling atomically'=>
  strpos($s['support'],'function pvdak_support_creation_lock($user_id)')!==false &&
  strpos($s['support'],"'pvdak_support_new_'")!==false &&
  strpos($s['support'],'$lock=pvdak_support_creation_lock($user);')!==false &&
  strpos($s['support'],'pvdak_support_ticket_unlock($lock);')!==false,
 'private tickets have a six-per-hour owner limit after successful save'=>
  strpos($s['support'],"'pvdak_ticket_hour_'")!==false &&
  strpos($s['support'],'get_transient($hourly_key)>=6')!==false &&
  strpos($s['support'],'set_transient($hourly_key,min(6,$count+1),HOUR_IN_SECONDS)')!==false,
 'user feedback distinguishes validation and throttling failures'=>
  strpos($s['support'],"'support','invalid'")!==false &&
  strpos($s['support'],"\$result_state='slow'")!==false &&
  strpos($s['support'],"add_query_arg('support',\$result_state,\$base)")!==false &&
  strpos($s['support'],"'support','failed'")!==false,
 'owned focused ticket'=>strpos($s['support'],"(int)\$focused->post_author===\$user")!==false,
 'followup audit'=>strpos($s['support'],"'to'=>'open'")!==false,
 'save idempotency'=>strpos($s['support'],'static $processed=array()')!==false,
 'plain permalink ticket filters preserve the dashboard Page selector'=>
  strpos($s['support'],'wp_parse_url(pvdak_auth_account_url(),PHP_URL_QUERY)')!==false &&
  strpos($s['support'],'parse_str($route_query,$account_route)')!==false &&
  strpos($s['support'],'name="page_id"')!==false &&
  strpos($s['support'],'name="pagename"')!==false,
 'ticket DB lock and guaranteed release'=>strpos($s['support'],"SELECT GET_LOCK(%s, 3)")!==false &&
  strpos($s['support'],"SELECT RELEASE_LOCK(%s)")!==false &&
  substr_count($s['support'],'pvdak_support_ticket_unlock($lock);')===3,
 'followup ownership and closure rechecked under lock'=>strpos($s['support'],'$current=get_post($id);')!==false &&
  strpos($s['support'],'(int)$current->post_author===get_current_user_id()')!==false &&
  strpos($s['support'],"'closed'!==get_post_meta(\$id,'_pvdak_support_status',true)")!==false,
 'staff rejects stale status and nonprivate tickets'=>strpos($s['support'],'name="pvdak_support_previous_state"')!==false &&
  strpos($s['support'],'$previous!==$expected_state')!==false &&
  strpos($s['support'],"'private'!==get_post_status(\$post_id)")!==false,
 'new ticket requires initial workflow state'=>strpos($s['support'],"!add_post_meta(\$created,'_pvdak_support_status','open',true)")!==false &&
  strpos($s['support'],"wp_delete_post(\$created,true)")!==false,
 'admin filter and scoped assets'=>strpos($s['support'],"add_action('restrict_manage_posts'")!==false &&
  strpos($s['support'],"add_action('pre_get_posts'")!==false &&
  strpos($s['assets'],"'pvdak-support-tickets'")!==false &&
  strpos($s['css'],'.pvdak-ticket-pagination')!==false
);
foreach($checks as $name=>$ok) {
 if(!$ok){fwrite(STDERR,"TICKET_UX_FAIL: ".$name."\n");exit(1);}
}
echo "PVDAK_TICKET_EXPERIENCE_OK: ".count($checks)." source checks (no WordPress/browser tests)\n";
