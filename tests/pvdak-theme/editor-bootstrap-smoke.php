<?php
/**
 * Synthetic, read-only smoke tests for the PVDak editorial starter.
 * Does not bootstrap WordPress or connect to a real database.
 *
 * Run: php tests/pvdak-theme/editor-bootstrap-smoke.php
 */
define( 'ABSPATH', __DIR__ );
define( 'OBJECT', 'OBJECT' );
$GLOBALS['pages'] = array();
$GLOBALS['categories'] = array();
$GLOBALS['fields'] = array();
$GLOBALS['next_page_id'] = 100;
$GLOBALS['next_term_id'] = 200;
$GLOBALS['post_changes'] = 0;
$GLOBALS['field_changes'] = 0;
$GLOBALS['sample_meta'] = array();
$GLOBALS['queried_page'] = null;

class WP_Post {}
function is_singular( $type = '' ) { return 'page' === $type; }
function get_queried_object() { return $GLOBALS['queried_page']; }
function add_action() {}
function add_filter() {}
function get_post_status( $id ) {
    return isset( $GLOBALS['pages'][ $id ] ) ? $GLOBALS['pages'][ $id ]->post_status : false;
}
function update_post_meta( $id, $key, $value ) {
    if ( ! isset( $GLOBALS['sample_meta'][ $id ] ) ) { $GLOBALS['sample_meta'][ $id ] = array(); }
    $GLOBALS['sample_meta'][ $id ][ $key ] = $value;
}
function get_post_meta( $id, $key, $single = true ) {
    return isset( $GLOBALS['sample_meta'][ $id ][ $key ] ) ? $GLOBALS['sample_meta'][ $id ][ $key ] : '';
}
function absint( $v ) { return abs( (int) $v ); }
function current_user_can() { return true; }
function get_current_user_id() { return 1; }
function get_template_directory() { return '/path-not-accessible'; }
function is_wp_error( $v ) { return false; }
function get_page_by_path( $path, $output, $type ) {
    foreach ( $GLOBALS['pages'] as $id => $post ) {
        $name = $post->post_parent ?
            $GLOBALS['pages'][ $post->post_parent ]->post_name . '/' . $post->post_name :
            $post->post_name;
        if ( $name === $path ) { return $post; }
    }
    return null;
}
function get_post_field( $field, $id ) {
    return isset( $GLOBALS['pages'][ $id ] ) ? $GLOBALS['pages'][ $id ]->$field : null;
}
function wp_insert_post( $args, $return_error = false ) {
    $id = ++$GLOBALS['next_page_id'];
    $GLOBALS['post_changes']++;
    $post = new WP_Post();
    foreach ( array_merge( $args, array( 'ID' => $id ) ) as $key => $value ) {
        $post->$key = $value;
    }
    $GLOBALS['pages'][ $id ] = $post;
    return $id;
}
function get_term_by( $field, $slug, $taxonomy ) {
    return isset( $GLOBALS['categories'][ $slug ] ) ?
        (object) array( 'term_id' => $GLOBALS['categories'][ $slug ] ) : false;
}
function wp_insert_term( $label, $tax, $args ) {
    $id = ++$GLOBALS['next_term_id'];
    $GLOBALS['categories'][ $args['slug'] ] = $id;
    return array( 'term_id' => $id );
}
function get_field( $field, $home_id ) {
    return isset( $GLOBALS['fields'][ $home_id ][ $field ] ) ?
        $GLOBALS['fields'][ $home_id ][ $field ] : null;
}
function update_field( $key, $value, $home_id ) {
    $field = str_replace( 'field_pvdak_home_', '', $key );
    $GLOBALS['fields'][ $home_id ][ $field ] = $value;
    $GLOBALS['field_changes']++;
    return true;
}

require dirname( __DIR__, 2 ) . '/inc/pvdak-editor-bootstrap.php';

$checks = 0;
function pvdak_editor_expect( $condition, $label ) {
    global $checks;
    $checks++;
    if ( ! $condition ) { fwrite( STDERR, "FAIL: {$label}\n" ); exit( 1 ); }
    echo "PASS: {$label}\n";
}

$parent = pvdak_editor_find_or_create_page( 'legal-topics', 'موضوعات حقوقی' );
pvdak_editor_expect( $parent > 0, 'Created legal landing root Page' );
pvdak_editor_expect( pvdak_editor_find_or_create_page( 'legal-topics', 'عنوان جدید' ) === $parent, 'Second pass reuses existing Page' );
pvdak_editor_expect( 1 === $GLOBALS['post_changes'], 'Existing Page never rewritten or duplicated' );
pvdak_editor_expect( 'موضوعات حقوقی' === $GLOBALS['pages'][ $parent ]->post_title, 'Existing Page title preserved' );

$child = pvdak_editor_find_or_create_page( 'family-law', 'حقوق خانواده', 'draft', $parent );
pvdak_editor_expect( $child > 0 && 'draft' === $GLOBALS['pages'][ $child ]->post_status, 'Sample landing stays draft' );
pvdak_editor_expect( $child === pvdak_editor_find_or_create_page( 'family-law', 'عنوان دیگر', 'publish', $parent ), 'Existing draft is never silently published' );
pvdak_editor_expect( 2 === $GLOBALS['post_changes'], 'Child draft idempotence confirmed' );

$term = pvdak_editor_find_or_create_category( 'legal-magazine', 'مجله حقوقی' );
pvdak_editor_expect( $term > 0, 'Created native WordPress post category' );
pvdak_editor_expect( $term === pvdak_editor_find_or_create_category( 'legal-magazine', 'عنوان دیگر' ), 'Existing category reused' );
pvdak_editor_expect( count( $GLOBALS['categories'] ) === 1, 'No duplicate categories created' );

pvdak_editor_expect( pvdak_editor_prefill_acf( 9, 'legal_topics_parent_page', $parent ), 'Empty ACF selector pre-filled once' );
pvdak_editor_expect( ! pvdak_editor_prefill_acf( 9, 'legal_topics_parent_page', $child ), 'Existing ACF selection never overwritten' );
pvdak_editor_expect( 1 === $GLOBALS['field_changes'], 'ACF edits are non-destructive' );
pvdak_editor_expect( ! pvdak_editor_prefill_acf( 0, 'faq_parent_page', 5 ), 'No homepage ID means no ACF writes' );

// New 17-title landing sample fixture: separate titles, stable slugs,
// published without legal text, and never modify existing editorial records.
$samples = pvdak_editor_example_legal_topics();
pvdak_editor_expect( 17 === count( $samples ), 'Exactly 17 requested legal sample landing titles' );
pvdak_editor_expect( 17 === count( array_unique( array_values( $samples ) ) ), 'All 17 original labels remain distinct' );
pvdak_editor_expect( 17 === count( array_unique( array_keys( $samples ) ) ), 'All 17 sample slugs are unique' );
$requested = array(
    'حقوقی', 'کیفری', 'خانواده', 'ثبتی', 'ملکی', 'حقوق اطفال و نوجوانان',
    'حقوق اراضی', 'حقوق مالیات', 'حقوق بشر', 'حقوق شرکت ها',
    'حقوق مالکیت معنوی', 'حقوق ثبت', 'حقوق خانواده', 'حقوق کیفری',
    'حقوق بیمه', 'حقوق ورزشی', 'حقوق کار',
);
pvdak_editor_expect( $requested === array_values( $samples ), 'Requested topic titles and their order are preserved' );
$seed = pvdak_editor_seed_example_legal_topics( $parent );
pvdak_editor_expect( 17 === $seed['created'] && 0 === $seed['existing'] && 0 === $seed['failed'],
    'All missing sample landing Pages are generated' );
$published = true;
$unwritten = true;
$tagged = true;
foreach ( $samples as $slug => $title ) {
    $item = get_page_by_path( 'legal-topics/' . $slug, OBJECT, 'page' );
    if ( ! $item || $item->post_title !== $title || 'publish' !== $item->post_status ||
         (int) $item->post_parent !== $parent ) {
        $published = false;
    }
    if ( ! $item || '' !== $item->post_content ) { $unwritten = false; }
    if ( ! $item || '1' !== get_post_meta( $item->ID, '_pvdak_sample_landing', true ) ) {
        $tagged = false;
    }
}
pvdak_editor_expect( $published, 'Every sample belongs to root and has published Page status' );
pvdak_editor_expect( $unwritten, 'No fabricated substantive legal advice is published' );
pvdak_editor_expect( $tagged, 'Every new empty sample is marked for search-engine noindex' );
$GLOBALS['queried_page'] = get_page_by_path( 'legal-topics/tax-law', OBJECT, 'page' );
$robots_empty = pvdak_editor_sample_landing_robots( array() );
pvdak_editor_expect( isset( $robots_empty['noindex'] ) && $robots_empty['noindex'] === true,
    'An empty published sample landing is noindex' );
$GLOBALS['queried_page']->post_content = 'Reviewed editorial article';
$robots_edited = pvdak_editor_sample_landing_robots( array() );
pvdak_editor_expect( ! isset( $robots_edited['noindex'] ),
    'Adding substantive content removes sample-specific noindex without altering metadata' );
$before_repeat = $GLOBALS['post_changes'];
$repeat = pvdak_editor_seed_example_legal_topics( $parent );
pvdak_editor_expect( 0 === $repeat['created'] && 17 === $repeat['existing'] &&
    $before_repeat === $GLOBALS['post_changes'], 'Repeated sample action is idempotent' );
$draft_sample = get_page_by_path( 'legal-topics/company-law', OBJECT, 'page' );
$draft_sample->post_status = 'draft';
$draft_sample->post_content = 'Existing editorial content';
$protected = pvdak_editor_seed_example_legal_topics( $parent );
pvdak_editor_expect( 17 === $protected['existing'] && 0 === $protected['created'] &&
    'draft' === $draft_sample->post_status && 'Existing editorial content' === $draft_sample->post_content,
    'A pre-existing edited draft is never automatically published or rewritten' );
$root_draft = $GLOBALS['pages'][ $parent ]->post_status;
$GLOBALS['pages'][ $parent ]->post_status = 'draft';
$blocked = pvdak_editor_seed_example_legal_topics( $parent );
pvdak_editor_expect( 17 === $blocked['failed'], 'Unpublished legal topics parent prevents publishing children' );
$GLOBALS['pages'][ $parent ]->post_status = $root_draft;

echo "PVDAK_EDITOR_BOOTSTRAP_OK: {$checks} checks (synthetic WordPress only).\n";
