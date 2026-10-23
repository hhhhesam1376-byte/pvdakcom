<?php
/** Offline mock tests: no real WordPress users, email or external services. */
define('ABSPATH', __DIR__);
define('DAY_IN_SECONDS', 86400);
class WP_User {
    public $ID;
    public $user_email;
    public function __construct($id, $email) { $this->ID=$id; $this->user_email=$email; }
}
class WP_Error {
    public $code;
    public function __construct($code, $message) { $this->code=$code; }
}
$GLOBALS['mock_user'] = new WP_User(42, 'example@invalid.test');
$GLOBALS['mock_meta'] = array(42=>array('_pvdak_email_verification_pending'=>'1'));
$GLOBALS['mock_mail'] = array();
function add_action(...$args) {}
function add_filter(...$args) {}
function absint($value) { return abs((int)$value); }
function get_userdata($id) { return 42===(int)$id?$GLOBALS['mock_user']:false; }
function get_user_meta($id,$key,$single=true) { return $GLOBALS['mock_meta'][$id][$key]??''; }
function update_user_meta($id,$key,$val) { $GLOBALS['mock_meta'][$id][$key]=$val; return true; }
function delete_user_meta($id,$key) { unset($GLOBALS['mock_meta'][$id][$key]); return true; }
function wp_generate_password($n,$special=false,$extra=false) {return str_repeat('A',$n);}
function wp_hash_password($token) {return password_hash($token,PASSWORD_DEFAULT);}
function wp_check_password($token,$hash,$id=0) {return password_verify($token,$hash);}
function pvdak_public_page_url($slug) {return 'https://example.invalid/'.$slug.'/';}
function home_url($path) {return 'https://example.invalid'.$path;}
function add_query_arg($args,$url) {return $url.'?'.http_build_query($args);}
function esc_url_raw($url) {return $url;}
function wp_mail($email,$subject,$body) {$GLOBALS['mock_mail'][]=array($email,$subject,$body);return true;}
require dirname(__DIR__,2).'/inc/pvdak-email-verification.php';
$count=0;
function test_check($yes,$what) {
    global $count;
    $count++;
    if (!$yes) {fwrite(STDERR,"PVD_NEXT_FAIL: ".$what."\n");exit(1);}
}
test_check(false===pvdak_email_verification_issue(999),'Unknown user must not receive email');
test_check(count($GLOBALS['mock_mail'])===0,'No mail to unknown user');
test_check(true===pvdak_email_verification_issue(42),'Issue verification email for pending account');
test_check(count($GLOBALS['mock_mail'])===1,'Only one email sent');
$hash=get_user_meta(42,'_pvdak_email_verification_token_hash',true);
test_check(is_string($hash)&&$hash!==str_repeat('A',48),'Plaintext verification token is never stored');
test_check(wp_check_password(str_repeat('A',48),$hash,42),'Generated token matches stored hash');
test_check((int)get_user_meta(42,'_pvdak_email_verification_expires',true)>time(),'Verification token has expiration');
test_check(false!==strpos($GLOBALS['mock_mail'][0][2],'pvdak_verify=1'),'Email carries verification link');
$user=$GLOBALS['mock_user'];
test_check(pvdak_email_verification_auth_gate($user,'') instanceof WP_Error,'Unverified sign-in rejected');
delete_user_meta(42,'_pvdak_email_verification_pending');
test_check(pvdak_email_verification_auth_gate($user,'')===$user,'Verified sign-in preserved');
$root=dirname(__DIR__,2);
$ticket=file_get_contents($root.'/inc/pvdak-support-tickets.php');
$registration=file_get_contents($root.'/inc/pvdak-registration.php');
$theme=file_get_contents($root.'/style.css');
test_check(false!==strpos($ticket,"'private'!==\$ticket->post_status") &&
    false!==strpos($ticket,'(int)$ticket->post_author!==get_current_user_id()'),
    'Reply ownership and private-status check present');
test_check(false!==strpos($ticket,"add_post_meta(absint(\$ticket_id)") &&
    false!==strpos($ticket,"'role'=>\$actor"),'Thread messages are append-only');
test_check(false!==strpos($ticket,"check_admin_referer('pvdak_support_followup_'"),
    'Follow-up requires nonce');
test_check(false!==strpos($registration,"get_option('pvdak_registration_enabled', '0')"),
    'Registration stays OFF by default');
test_check(false!==strpos($theme,'Version: 0.8.29'),'Draft version incremented');
echo "PVD_NEXT_PHASE_TEST_OK: {$count} checks (mocked email/token + static privacy checks).\n";
