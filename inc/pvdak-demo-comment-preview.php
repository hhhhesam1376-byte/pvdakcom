<?php
/**
 * Editor-only native WP comment testing on explicitly marked demo blog posts.
 * No post options/status/database changes. When maintenance ends, this exception
 * ends, and per-post WordPress discussion settings become authoritative.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_demo_comments_open_for_admin_preview( $open, $post_id ) {
    if ( $open ||
         ! function_exists( 'pvdak_maintenance_enabled' ) ||
         ! pvdak_maintenance_enabled() ||
         ! function_exists( 'pvdak_maintenance_can_preview_site' ) ||
         ! pvdak_maintenance_can_preview_site() ) {
        return $open;
    }
    $post = get_post( (int) $post_id );
    if ( ! $post instanceof WP_Post ||
         'post' !== $post->post_type ||
         'publish' !== $post->post_status ||
         post_password_required( $post ) ) {
        return $open;
    }
    return '1' === (string) get_post_meta( $post->ID, '_pvdak_demo_blog', true );
}
add_filter( 'comments_open', 'pvdak_demo_comments_open_for_admin_preview', 10, 2 );
