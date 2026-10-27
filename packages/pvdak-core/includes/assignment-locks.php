<?php
/**
 * Database advisory locks for *internal* referral decisions.
 *
 * The same WordPress database connection must hold both locks throughout
 * eligibility checks and writes. If MariaDB/MySQL does not support them, fail
 * closed. This does NOT replace an end-to-end staging concurrency test.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Short, stable names: MySQL lock-name length restrictions apply. */
function pvdak_core_assignment_lock_names( $request_id, $profile_id ) {
    // The lock helper is also used by internal callers: never let absint()
    // turn a forged negative or malformed identifier into another case's lock.
    // Reuse the same bounded parser as the assignment GET/POST handlers.
    if ( ! function_exists( 'pvdak_core_assignment_positive_id' ) ) { return array(); }
    $request_id = pvdak_core_assignment_positive_id( $request_id );
    $profile_id = pvdak_core_assignment_positive_id( $profile_id );
    if ( ! $request_id || ! $profile_id ) { return array(); }
    $names = array(
        'pvdak:req:' . $request_id,
        'pvdak:pro:' . $profile_id,
    );
    sort( $names, SORT_STRING ); // Deterministic order avoids lock-order deadlocks.
    return $names;
}

/**
 * A false result means a lock timeout / unavailable DB capability. The callback
 * must return a result code, never redirect or exit from inside the lock.
 */
function pvdak_core_with_assignment_locks( $request_id, $profile_id, $callback ) {
    global $wpdb;
    $names = pvdak_core_assignment_lock_names( $request_id, $profile_id );
    if ( count( $names ) !== 2 || ! is_callable( $callback ) || ! isset( $wpdb ) ||
         ! method_exists( $wpdb, 'get_var' ) || ! method_exists( $wpdb, 'prepare' ) ) {
        return 'lock_unavailable';
    }
    $held = array();
    try {
        foreach ( $names as $name ) {
            // Lock acquisition never interpolates user-controlled identifiers.
            $result = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $name, 3 ) );
            // WPDB returns "1" for an acquired MySQL/MariaDB named lock.
            // Casting an unexpected result like "1invalid" to (int) 1 would
            // falsely treat a lock as held and allow an unprotected write.
            if ( 1 !== $result && '1' !== $result ) {
                return 'lock_unavailable';
            }
            $held[] = $name;
        }
        return call_user_func( $callback );
    } finally {
        foreach ( array_reverse( $held ) as $name ) {
            $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
        }
    }
}

/** Clear potentially stale metadata before re-checking inside a held lock. */
function pvdak_core_refresh_assignment_meta( $request_id, $profile_id ) {
    if ( function_exists( 'wp_cache_delete' ) ) {
        wp_cache_delete( absint( $request_id ), 'post_meta' );
        wp_cache_delete( absint( $profile_id ), 'post_meta' );
    }
}
