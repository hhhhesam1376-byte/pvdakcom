<?php
/**
 * Synthetic admin-only Page bootstrap regression.
 * No WordPress, filesystem mutation, network or production database access.
 */
define('ABSPATH', __DIR__);
$GLOBALS['seed_marker'] = '0.8.26';
$GLOBALS['fake_posts'] = array(
    'about-us' => (object)array('post_status'=>'publish','ID'=>10),
    'legal-faq' => (object)array('post_status'=>'draft','ID'=>11),
    'account-register' => (object)array('post_status'=>'trash','ID'=>12),
);
$GLOBALS['fake_created'] = array();
function add_action(...$args) {}
function add_filter(...$args) {}
function is_admin() { return true; }
function wp_doing_ajax() { return false; }
function current_user_can($cap) { return true; }
function get_current_user_id() { return 1; }
function get_option($key, $default=false) {
    return '_pvdak_public_pages_bootstrap'===$key ? $GLOBALS['seed_marker'] : $default;
}
function update_option($key,$value,$autoload=null) {
    if('_pvdak_public_pages_bootstrap'===$key)$GLOBALS['seed_marker']=$value;
    return true;
}
function get_page_by_path($slug,$output=null,$type=null) {
    return isset($GLOBALS['fake_posts'][$slug]) ? $GLOBALS['fake_posts'][$slug] : null;
}
function wp_insert_post($args,$wp_error=false) {
    if(isset($GLOBALS['fake_posts'][$args['post_name']])) {
        fwrite(STDERR,"BOOTSTRAP_TEST_FAIL: overwriting existing Page\n");exit(1);
    }
    $id=100+count($GLOBALS['fake_created']);
    $GLOBALS['fake_posts'][$args['post_name']]=(object)array(
        'ID'=>$id,'post_status'=>$args['post_status']);
    $GLOBALS['fake_created'][]=$args['post_name'];
    return $id;
}
function is_wp_error($v) { return false; }
function update_post_meta($id,$key,$value) {return true;}
require dirname(__DIR__,2).'/inc/pvdak-public-pages.php';
function pvdak_bootstrap_assert($condition,$label) {
    if(!$condition){fwrite(STDERR,"BOOTSTRAP_TEST_FAIL: ".$label."\n");exit(1);}
}
pvdak_public_create_missing_pages();
pvdak_bootstrap_assert(count($GLOBALS['fake_created'])===7,
    'Seven absent Pages are created even when legacy marker says done');
pvdak_bootstrap_assert($GLOBALS['fake_posts']['legal-faq']->post_status==='draft',
    'Draft remains untouched');
pvdak_bootstrap_assert($GLOBALS['fake_posts']['account-register']->post_status==='trash',
    'Trashed Page remains untouched');
pvdak_bootstrap_assert($GLOBALS['seed_marker']==='0.8.26',
    'Marker cannot be refreshed while existing Pages remain unpublished');
$GLOBALS['fake_posts']['legal-faq']->post_status='publish';
$GLOBALS['fake_posts']['account-register']->post_status='publish';
pvdak_public_create_missing_pages();
pvdak_bootstrap_assert(count($GLOBALS['fake_created'])===7,
    'Reconciliation is idempotent with full Page set');
pvdak_bootstrap_assert($GLOBALS['seed_marker']==='0.8.29',
    'Completion marker refreshed only after every expected Page is published');
unset($GLOBALS['fake_posts']['contact-us']);
pvdak_public_create_missing_pages();
pvdak_bootstrap_assert(count($GLOBALS['fake_created'])===8,
    'Missing Page is restored without resetting current version marker');
pvdak_bootstrap_assert(end($GLOBALS['fake_created'])==='contact-us',
    'Only absent Page was recreated');
echo "PVDAK_PAGE_BOOTSTRAP_OK: 8 synthetic assertions, no WordPress writes.\n";
