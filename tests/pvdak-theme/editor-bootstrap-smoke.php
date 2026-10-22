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

function add_action() {}
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
    $GLOBALS['pages'][ $id ] = (object) array_merge( $args, array( 'ID' => $id ) );
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

echo "PVDAK_EDITOR_BOOTSTRAP_OK: {$checks} checks (synthetic WordPress only).\n";
