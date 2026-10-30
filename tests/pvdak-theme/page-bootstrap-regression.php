<?php
/**
 * Synthetic admin-only Page bootstrap regression.
 * No WordPress, filesystem mutation, network or production database access.
 */
define('ABSPATH', __DIR__);
define('OBJECT', 'OBJECT'); // Real WP_Query get_page_by_path(..., OBJECT, ...).
class WP_Post {
    public $ID, $post_type = 'page', $post_status = 'publish', $post_content = '';
    public $post_name = '', $page_template = '';
    public function __construct( $fields = array() ) {
        foreach ( $fields as $key => $value ) { $this->$key = $value; }
    }
}
$GLOBALS['seed_marker'] = '0.8.26';
$GLOBALS['fake_posts'] = array(
    'about-us' => new WP_Post(array('post_status'=>'publish','ID'=>10,
        'post_content'=>'<p>Editor-authored content must be preserved.</p>')),
    'legal-faq' => new WP_Post(array('post_status'=>'draft','ID'=>11,'post_content'=>'')),
    'account-register' => new WP_Post(array('post_status'=>'trash','ID'=>12,'post_content'=>'')),
);
$GLOBALS['fake_created'] = array();
function add_action(...$args) {}
function add_filter(...$args) {}
$GLOBALS['pvdak_bootstrap_shortcodes'] = array();
function add_shortcode( $tag, $callback ) {
    $GLOBALS['pvdak_bootstrap_shortcodes'][ $tag ] = $callback;
}
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
function get_posts( $args ) {
    if ( ( $args['post_type'] ?? '' ) !== 'page' ||
         ( $args['meta_key'] ?? '' ) !== '_wp_page_template' ) {
        throw new RuntimeException( 'Unexpected template query in bootstrap fixture' );
    }
    $matches = array();
    foreach ( $GLOBALS['fake_posts'] as $post ) {
        if ( $post instanceof WP_Post &&
             $post->page_template === $args['meta_value'] &&
             in_array( $post->post_status, (array) $args['post_status'], true ) ) {
            $matches[] = $post;
        }
    }
    usort( $matches, static function ( $a, $b ) { return $a->ID <=> $b->ID; } );
    return array_slice( $matches, 0, $args['posts_per_page'] );
}
function get_page_template_slug( $id ) {
    foreach ( $GLOBALS['fake_posts'] as $post ) {
        if ( $post->ID === $id ) { return $post->page_template; }
    }
    return '';
}
function get_page_by_path($slug,$output=null,$type=null) {
    return isset($GLOBALS['fake_posts'][$slug]) ? $GLOBALS['fake_posts'][$slug] : null;
}
function wp_insert_post($args,$wp_error=false) {
    if(isset($GLOBALS['fake_posts'][$args['post_name']])) {
        fwrite(STDERR,"BOOTSTRAP_TEST_FAIL: overwriting existing Page\n");exit(1);
    }
    $id=100+count($GLOBALS['fake_created']);
    $GLOBALS['fake_posts'][$args['post_name']]=new WP_Post(array(
        'ID'=>$id, 'post_type'=>$args['post_type'], 'post_name'=>$args['post_name'],
        'post_status'=>$args['post_status'], 'page_template'=>$args['page_template'],
        'post_content'=>$args['post_content']));
    $GLOBALS['fake_created'][]=$args['post_name'];
    return $id;
}
function is_wp_error($v) { return false; }
function update_post_meta($id,$key,$value) {return true;}
require dirname(__DIR__,2).'/inc/pvdak-public-pages.php';
function pvdak_bootstrap_assert($condition,$label) {
    if(!$condition){fwrite(STDERR,"BOOTSTRAP_TEST_FAIL: ".$label."\n");exit(1);}
}
pvdak_bootstrap_assert(
    isset( $GLOBALS['pvdak_bootstrap_shortcodes']['pvdak_forum'] ) &&
    is_callable( $GLOBALS['pvdak_bootstrap_shortcodes']['pvdak_forum'] ),
    'Real public-pages source registers forum shortcode in the isolated stub' );
pvdak_public_create_missing_pages();
pvdak_bootstrap_assert(count($GLOBALS['fake_created'])===7,
    'Seven absent Pages are created even when legacy marker says done');
pvdak_bootstrap_assert($GLOBALS['fake_posts']['about-us']->post_content==='<p>Editor-authored content must be preserved.</p>',
    'Published custom editor content is never overwritten');
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
// A renamed account Page must never be recreated, even if unpublished or
// another Page has the same account template. No real WP content is touched.
$renamed_login = $GLOBALS['fake_posts']['account-login'];
unset( $GLOBALS['fake_posts']['account-login'] );
$renamed_login->post_name = 'renamed-login';
$GLOBALS['fake_posts']['renamed-login'] = $renamed_login;
pvdak_public_create_missing_pages();
pvdak_bootstrap_assert( count( $GLOBALS['fake_created'] ) === 7 &&
    ! isset( $GLOBALS['fake_posts']['account-login'] ),
    'Published renamed login is not duplicated' );
$renamed_login->post_status = 'draft';
pvdak_public_create_missing_pages();
pvdak_bootstrap_assert( count( $GLOBALS['fake_created'] ) === 7 &&
    'draft' === $renamed_login->post_status,
    'Unpublished renamed login stays untouched' );
$renamed_login->post_status = 'publish';
$GLOBALS['fake_posts']['duplicate-login'] = new WP_Post(array(
    'ID' => 901, 'post_name' => 'duplicate-login', 'post_status' => 'publish',
    'page_template' => 'page-templates/pvdak-login.php'
));
pvdak_public_create_missing_pages();
pvdak_bootstrap_assert( count( $GLOBALS['fake_created'] ) === 7 &&
    ! isset( $GLOBALS['fake_posts']['account-login'] ),
    'Ambiguous account template cannot seed a new login Page' );
unset( $GLOBALS['fake_posts']['duplicate-login'] );
unset($GLOBALS['fake_posts']['contact-us']);
pvdak_public_create_missing_pages();
pvdak_bootstrap_assert(count($GLOBALS['fake_created'])===8,
    'Missing Page is restored without resetting current version marker');
pvdak_bootstrap_assert(end($GLOBALS['fake_created'])==='contact-us',
    'Only absent Page was recreated');
echo "PVDAK_PAGE_BOOTSTRAP_OK: 13 synthetic assertions, no WordPress writes.\n";
