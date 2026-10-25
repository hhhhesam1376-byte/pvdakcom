<?php
/**
 * PVDak mobile OTP authentication (native WordPress users, no parallel accounts).
 * No SMS is sent until a real provider explicitly registers BOTH filters:
 * pvdak_sms_auth_enabled (true) and pvdak_sms_otp_deliver (true on delivery).
 * Provider receives E.164 phone, one-time code and intent; never log the code.
 */
if (!defined('ABSPATH')) { exit; }

function pvdak_sms_auth_ready() {
    return (bool) apply_filters('pvdak_sms_auth_enabled', false) &&
        (bool) has_filter('pvdak_sms_otp_deliver');
}
function pvdak_sms_normalize_phone($raw) {
    if (!is_string($raw) || strlen($raw)>24) { return ''; }
    $raw=strtr($raw, array('۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'));
    $raw=preg_replace('/[\s\-()]/u','',trim($raw));
    if (strpos($raw,'+98')===0) { $raw='0'.substr($raw,3); }
    elseif (strpos($raw,'0098')===0) { $raw='0'.substr($raw,4); }
    elseif (strpos($raw,'98')===0 && strlen($raw)===12) { $raw='0'.substr($raw,2); }
    return preg_match('/^09[0-9]{9}$/D',$raw) ? $raw : '';
}
function pvdak_sms_key($kind,$phone) {
    return 'pvdak_sms_'.substr(hash_hmac('sha256',$kind.'|'.$phone,wp_salt('auth')),0,40);
}
function pvdak_sms_user($phone) {
    $ids=get_users(array('meta_key'=>'_pvdak_phone_verified','meta_value'=>$phone,
        'number'=>2,'fields'=>'ID','count_total'=>false));
    if (!is_array($ids) || count($ids)!==1) { return false; }
    $user=get_userdata((int)$ids[0]);
    return $user instanceof WP_User ? $user : false;
}
function pvdak_sms_redirect($intent,$status) {
    $url='bind'===$intent ? pvdak_auth_account_url() : pvdak_auth_login_url();
    if ('bind'===$intent) {
        wp_safe_redirect(add_query_arg('sms_status',$status,$url).'#pvdak-security');
        exit;
    }
    wp_safe_redirect(add_query_arg(array('auth'=> 'register'===$intent ? 'sms-register' : 'sms-login',
        'sms_status'=>$status),$url));
    exit;
}
/** Send and verify are explicitly gated, rate-limited and require WordPress nonces. */
function pvdak_sms_send() {
    if ('POST'!==($_SERVER['REQUEST_METHOD']??'')) { wp_die('Method not allowed','',array('response'=>405)); }
    check_admin_referer('pvdak_sms_send','_pvdak_sms_nonce');
    $intent=isset($_POST['sms_intent']) && is_string($_POST['sms_intent']) ?
        sanitize_key(wp_unslash($_POST['sms_intent'])) : '';
    if (!in_array($intent,array('login','register','bind'),true)) { wp_die('Invalid intent','',array('response'=>400)); }
    if (!pvdak_sms_auth_ready()) { pvdak_sms_redirect($intent,'unavailable'); }
    if ($intent==='bind' && !is_user_logged_in()) { wp_die('Sign in required','',array('response'=>403)); }
    if ($intent==='register' && !pvdak_registration_open()) { pvdak_sms_redirect($intent,'closed'); }
    $raw=wp_unslash($_POST['sms_phone']??'');
    $phone=pvdak_sms_normalize_phone($raw);
    if (!$phone) { pvdak_sms_redirect($intent,'invalid'); }
    if (pvdak_registration_rate_exceeded('sms-send',12,HOUR_IN_SECONDS) ||
        get_transient(pvdak_sms_key('cooldown',$phone))) {
        pvdak_sms_redirect($intent,'slow');
    }
    $role='client';
    if ($intent==='register') {
        $role=isset($_POST['sms_role']) && is_string($_POST['sms_role']) ?
            sanitize_key(wp_unslash($_POST['sms_role'])) : '';
        if (!in_array($role,array('client','lawyer','trainee','student'),true) ||
            empty($_POST['sms_consent'])) { pvdak_sms_redirect($intent,'invalid'); }
        if (pvdak_sms_user($phone)) { pvdak_sms_redirect($intent,'sent'); }
    } elseif ($intent==='bind') {
        if (pvdak_sms_user($phone)) { pvdak_sms_redirect($intent,'invalid'); }
    } else {
        $user=pvdak_sms_user($phone);
        if (!$user || '1'===(string)get_user_meta($user->ID,'_pvdak_email_verification_pending',true) ||
            user_can($user,'manage_options')) { pvdak_sms_redirect($intent,'sent'); }
    }
    set_transient(pvdak_sms_key('cooldown',$phone),1,90);
    $code=(string)random_int(100000,999999);
    $delivered=apply_filters('pvdak_sms_otp_deliver',false,'+98'.substr($phone,1),$code,$intent);
    if (true!==$delivered) { pvdak_sms_redirect($intent,'unavailable'); }
    set_transient(pvdak_sms_key('challenge-'.$intent,$phone),array(
        'hash'=>wp_hash_password($code),'expires'=>time()+5*MINUTE_IN_SECONDS,
        'tries'=>0,'role'=>$role,'user_id'=>'bind'===$intent?get_current_user_id():0
    ),5*MINUTE_IN_SECONDS);
    pvdak_sms_redirect($intent,'sent');
}
add_action('admin_post_nopriv_pvdak_sms_send','pvdak_sms_send');
add_action('admin_post_pvdak_sms_send','pvdak_sms_send');

function pvdak_sms_verify() {
    if ('POST'!==($_SERVER['REQUEST_METHOD']??'')) { wp_die('Method not allowed','',array('response'=>405)); }
    check_admin_referer('pvdak_sms_verify','_pvdak_sms_verify_nonce');
    $intent=isset($_POST['sms_intent']) && is_string($_POST['sms_intent']) ?
        sanitize_key(wp_unslash($_POST['sms_intent'])) : '';
    if (!in_array($intent,array('login','register','bind'),true)) { wp_die('Invalid intent','',array('response'=>400)); }
    $phone=pvdak_sms_normalize_phone(wp_unslash($_POST['sms_phone']??''));
    $raw=wp_unslash($_POST['sms_code']??'');
    $code=is_string($raw)?trim(strtr($raw,array('۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9'))):'';
    if (!$phone || !preg_match('/^[0-9]{6}$/D',$code) ||
        pvdak_registration_rate_exceeded('sms-verify',25,HOUR_IN_SECONDS)) {
        pvdak_sms_redirect($intent,'invalid');
    }
    $key=pvdak_sms_key('challenge-'.$intent,$phone);
    $challenge=get_transient($key);
    if (!is_array($challenge) || !isset($challenge['hash'],$challenge['expires'],$challenge['tries']) ||
        $challenge['expires']<time() || $challenge['tries']>=5) {
        pvdak_sms_redirect($intent,'invalid');
    }
    $challenge['tries']++;
    if (!wp_check_password($code,$challenge['hash'])) {
        if ($challenge['tries']>=5) { delete_transient($key); }
        else { set_transient($key,$challenge,max(1,$challenge['expires']-time())); }
        pvdak_sms_redirect($intent,'invalid');
    }
    // Single-use challenge; no password or OTP is saved to user metadata.
    delete_transient($key);
    if ($intent==='bind') {
        if (!is_user_logged_in() ||
            (int)($challenge['user_id']??0)!==get_current_user_id() ||
            pvdak_sms_user($phone) ||
            '1'===(string)get_user_meta(get_current_user_id(),'_pvdak_email_verification_pending',true)) {
            pvdak_sms_redirect($intent,'invalid');
        }
        update_user_meta(get_current_user_id(),'_pvdak_phone_verified',$phone);
        pvdak_sms_redirect($intent,'bound');
    }
    if ($intent==='login') {
        $user=pvdak_sms_user($phone);
        if (!$user || user_can($user,'manage_options') ||
            '1'===(string)get_user_meta($user->ID,'_pvdak_email_verification_pending',true)) {
            pvdak_sms_redirect($intent,'invalid');
        }
    } else {
        if (!pvdak_registration_open() || pvdak_sms_user($phone)) {
            pvdak_sms_redirect($intent,'closed');
        }
        $role=isset($challenge['role']) && in_array($challenge['role'],
            array('client','lawyer','trainee','student'),true) ? $challenge['role'] : 'client';
        $created=wp_insert_user(array(
            'user_login'=>'pvdak_m_'.bin2hex(random_bytes(9)),
            'user_pass'=>wp_generate_password(32,true,true),
            'user_email'=>'',
            'display_name'=>'کاربر PVDak',
            'role'=>get_role('pvdak_client')?'pvdak_client':'subscriber',
        ));
        if (is_wp_error($created) || !$created) { pvdak_sms_redirect($intent,'invalid'); }
        update_user_meta($created,'_pvdak_phone_verified',$phone);
        update_user_meta($created,'_pvdak_self_reported_category',$role);
        $user=get_userdata($created);
    }
    if (!$user instanceof WP_User) { pvdak_sms_redirect($intent,'invalid'); }
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID,false,is_ssl());
    do_action('wp_login',$user->user_login,$user);
    wp_safe_redirect(pvdak_auth_account_url());
    exit;
}
add_action('admin_post_nopriv_pvdak_sms_verify','pvdak_sms_verify');
add_action('admin_post_pvdak_sms_verify','pvdak_sms_verify');
