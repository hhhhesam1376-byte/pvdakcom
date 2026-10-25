<?php
/** WordPress-backed branded PVDak login; WP administrators retain their native access. */
if(!defined('ABSPATH')){exit;}
function pvdak_auth_login_url(){
  $page = pvdak_public_template_page_url('account-login', 'page-templates/pvdak-login.php');
  // If the administrator has not published the branded Page yet, use the
  // actual WordPress entry point; never send visitors to a guessed 404 slug.
  // site_url() deliberately avoids calling wp_login_url() and its login_url filter.
  return $page ?: site_url('wp-login.php', 'login');
}
function pvdak_auth_account_url(){
  $core=function_exists('pvdak_core_account_url')?pvdak_core_account_url():'';
  $page = pvdak_public_template_page_url('my-pvdak-account', 'page-templates/pvdak-account.php');
  // WordPress's own protected profile is a safe emergency destination.
  // A missing dashboard Page must not turn a successful login into a 404.
  return $page ?: ($core ?: admin_url('profile.php'));
}
function pvdak_frontend_login_url($url,$redirect,$force_reauth){
  if(is_admin()||wp_doing_ajax()||(defined('WP_CLI')&&WP_CLI))return $url;
  return pvdak_auth_login_url();
}
add_filter('login_url','pvdak_frontend_login_url',10,3);
/**
 * Narrow rate limit on the branded entry point only. Keep wp-admin logins
 * available to site operators. No raw IP, username or password is persisted.
 */
function pvdak_auth_attempt_key($identity) {
  $remote=(string)($_SERVER['REMOTE_ADDR']??'');
  return 'pvdak_login_'.substr(hash_hmac('sha256',strtolower(trim($identity)).'|'.$remote,wp_salt('nonce')),0,40);
}
/** A separate IP-wide ceiling prevents rotating usernames from evading login throttling. */
function pvdak_auth_ip_attempt_key() {
  $remote=isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
  return 'pvdak_login_ip_'.substr(hash_hmac('sha256',$remote,wp_salt('nonce')),0,40);
}
function pvdak_auth_record_failure($key) {
  $attempts=min(20,(int)get_transient($key)+1);
  set_transient($key,$attempts,15*MINUTE_IN_SECONDS);
  $ip_key=pvdak_auth_ip_attempt_key();
  $ip_attempts=min(30,(int)get_transient($ip_key)+1);
  set_transient($ip_key,$ip_attempts,15*MINUTE_IN_SECONDS);
}
function pvdak_login_handle(){
  if('POST'!==($_SERVER['REQUEST_METHOD']??''))wp_die('Invalid method','',array('response'=>405));
  check_admin_referer('pvdak_login','_pvdak_login_nonce');
  if(is_user_logged_in()){wp_safe_redirect(pvdak_auth_account_url());exit;}
  $raw_identity=wp_unslash($_POST['login_identity']??'');
  $raw_pass=wp_unslash($_POST['login_password']??'');
  $identity=is_string($raw_identity)?trim($raw_identity):'';
  $pass=is_string($raw_pass)?$raw_pass:'';
  $key=pvdak_auth_attempt_key($identity);
  if((int)get_transient($key)>=6 || (int)get_transient(pvdak_auth_ip_attempt_key())>=30){
    wp_safe_redirect(add_query_arg('error','slow',pvdak_auth_login_url()));exit;
  }
  if(!$identity||!$pass||strlen($identity)>250){
    pvdak_auth_record_failure($key);
    wp_safe_redirect(add_query_arg('error','1',pvdak_auth_login_url()));exit;
  }
  $user=wp_signon(array('user_login'=>$identity,'user_password'=>$pass,
     'remember'=>isset($_POST['login_remember'])),is_ssl());
  if(is_wp_error($user)){
    pvdak_auth_record_failure($key);
    wp_safe_redirect(add_query_arg('error','1',pvdak_auth_login_url()));exit;
  }
  delete_transient($key);
  wp_set_current_user($user->ID);
  wp_safe_redirect(pvdak_auth_account_url());exit;
}
add_action('admin_post_nopriv_pvdak_login','pvdak_login_handle');
add_action('admin_post_pvdak_login','pvdak_login_handle');
