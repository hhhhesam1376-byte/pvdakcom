<?php
/**
 * Opt-in registration scaffolding, hard OFF until manager enables it AFTER
 * removing maintenance AND WordPress permits registration.
 * Applicant-selected profession is a self-report; NEVER grants lawyer rights.
 */
if (!defined('ABSPATH')) { exit; }
function pvdak_registration_open() {
    return ! ( function_exists('pvdak_maintenance_enabled') && pvdak_maintenance_enabled() ) &&
        '1' === (string) get_option('pvdak_registration_enabled', '0') &&
        (bool) get_option('users_can_register', false);
}
function pvdak_register_submit() {
    if (!pvdak_registration_open()) { wp_die('ثبت‌نام فعلاً غیرفعال است.','',array('response'=>403)); }
    if ('POST' !== ($_SERVER['REQUEST_METHOD']??'')) { wp_die('Method not allowed','',array('response'=>405)); }
    check_admin_referer('pvdak_register','_pvdak_register_nonce');
    $url=pvdak_public_page_url('account-register')?:home_url('/account-register/');
    if (!empty($_POST['register_site'])) { wp_safe_redirect(add_query_arg('state','received',$url));exit; }
    $values=array();
    foreach(array('register_username','register_email','register_password','register_role') as $field){
        $raw=wp_unslash($_POST[$field]??'');
        $values[$field]=is_string($raw)?$raw:'';
    }
    $username=sanitize_user($values['register_username'],true);
    $email=sanitize_email($values['register_email']);
    $password=$values['register_password'];
    $claim=sanitize_key($values['register_role']);
    $allowed=array('client','lawyer','trainee','student');
    $rate='pvdak_signup_'.substr(hash_hmac('sha256',($_SERVER['REMOTE_ADDR']??'').'|'.$email,wp_salt('nonce')),0,30);
    if (get_transient($rate)) { wp_safe_redirect(add_query_arg('state','slow',$url));exit; }
    if (!$username || strlen($username)<3 || strlen($username)>50 ||
        !validate_username($username) || !is_email($email) ||
        strlen($password)<12 || strlen($password)>256 || !in_array($claim,$allowed,true) ||
        empty($_POST['register_consent']) || username_exists($username) || email_exists($email)) {
        wp_safe_redirect(add_query_arg('state','invalid',$url));exit;
    }
    set_transient($rate,1,180);
    // A professional claim does not create a verified professional account.
    $role=get_role('pvdak_client')?'pvdak_client':'subscriber';
    $user=wp_insert_user(array('user_login'=>$username,'user_email'=>$email,
        'user_pass'=>$password,'role'=>$role,'display_name'=>$username));
    if (is_wp_error($user)) {
        wp_safe_redirect(add_query_arg('state','invalid',$url));exit;
    }
    update_user_meta($user,'_pvdak_self_reported_category',$claim);
    update_user_meta($user,'_pvdak_email_verification_pending','1');
    wp_safe_redirect(add_query_arg('state','received',$url));exit;
}
add_action('admin_post_nopriv_pvdak_register','pvdak_register_submit');
add_action('admin_post_pvdak_register','pvdak_register_submit');
