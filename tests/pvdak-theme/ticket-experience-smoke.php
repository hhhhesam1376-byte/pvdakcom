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
 'native disclosure'=>strpos($s['support'],'<details class="pvdak-ticket-details">')!==false,
 'timestamp'=>strpos($s['support'],"wp_date('j F Y، H:i'")!==false,
 'separate roles'=>strpos($s['support'],'pvdak-ticket-staff')!==false,
 'history limit notice'=>strpos($s['support'],'$message_count>30')!==false,
 'private pagination'=>strpos($s['support'],"'posts_per_page'=>11")!==false && strpos($s['support'],"'author'=>$user")!==false,
 'next and previous'=>strpos($s['support'],"add_query_arg('ticket_page',$page+1")!==false && strpos($s['support'],"add_query_arg('ticket_page',$page-1")!==false,
 'empty state'=>strpos($s['support'],'pvdak-ticket-empty')!==false,
 'distinct notifications'=>strpos($s['support'],"'followup_sent'")!==false && strpos($s['support'],"'new_sent'")!==false,
 'unicode length'=>strpos($s['support'],"mb_strlen($value, 'UTF-8')")!==false,
 'trim messages'=>strpos($s['support'],'$body=trim($body);')!==false,
 'privacy hint'=>strpos($s['support'],'pvdak-ticket-privacy')!==false,
 'new counter'=>strpos($s['support'],'data-pvdak-count-for="support-message"')!==false,
 'reply counter'=>strpos($s['support'],'data-pvdak-count-for="pvdak-followup-')!==false,
 'double submit protection'=>strpos($s['js'],"button.disabled = true")!==false,
 'account nav active section'=>strpos($s['js'],"window.addEventListener('hashchange'")!==false,
 'admin filter and scoped assets'=>strpos($s['support'],"add_action('restrict_manage_posts'")!==false &&
  strpos($s['support'],"add_action('pre_get_posts'")!==false &&
  strpos($s['assets'],"'pvdak-support-tickets'")!==false &&
  strpos($s['css'],'.pvdak-ticket-pagination')!==false
);
foreach($checks as $name=>$ok) {
 if(!$ok){fwrite(STDERR,"TICKET_UX_FAIL: ".$name."\n");exit(1);}
}
echo "PVDAK_TICKET_EXPERIENCE_OK: ".count($checks)." source checks (no WordPress/browser tests)\n";
