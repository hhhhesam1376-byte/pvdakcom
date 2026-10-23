<?php
/** Keep explicitly labelled Lorem ipsum sample posts out of search results. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_demo_blog_noindex( $robots ) {
    if ( ! is_singular( 'post' ) ) { return $robots; }
    $post = get_queried_object();
    if ( $post instanceof WP_Post && '1' === (string) get_post_meta( $post->ID, '_pvdak_demo_blog', true ) ) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }
    return $robots;
}
add_filter( 'wp_robots', 'pvdak_demo_blog_noindex' );
