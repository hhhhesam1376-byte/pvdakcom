<?php
/**
 * Install official, pinned Swiper on the existing WordPress host.
 * Runs only for a logged-in administrator, once per retry window.
 * Uses the SAME uploads/pvdak-static path as the private shell installer.
 * No frontend CDN, new cron, credentials, Core or legal data changes.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_swiper_vendor_directory() {
    $uploads = wp_get_upload_dir();
    if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) { return ''; }
    return rtrim( $uploads['basedir'], '/\\' ) . '/pvdak-static/swiper-14.3.0';
}

function pvdak_swiper_vendor_ready() {
    $dir = pvdak_swiper_vendor_directory();
    if ( ! $dir || is_link( $dir ) ) { return false; }
    foreach ( array(
        'swiper-bundle.min.js' => 120000,
        'swiper-bundle.min.css' => 10000,
        'LICENSE' => 500,
    ) as $name => $minimum ) {
        if ( ! is_file( $dir . '/' . $name ) ||
             filesize( $dir . '/' . $name ) < $minimum ) {
            return false;
        }
    }
    // The private host-side installer also writes this marker only on success.
    return is_file( $dir . '/SHA256SUMS' );
}

function pvdak_swiper_admin_ensure_local() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ||
         ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) ||
         pvdak_swiper_vendor_ready() ) {
        return;
    }
    // Failed network attempts must not delay every admin page load.
    if ( get_transient( 'pvdak_swiper_143_install_attempt' ) ) { return; }
    set_transient( 'pvdak_swiper_143_install_attempt', 1, 6 * HOUR_IN_SECONDS );

    $dir = pvdak_swiper_vendor_directory();
    $root = dirname( $dir );
    if ( ! $dir || is_link( $root ) || is_link( $dir ) ) { return; }
    if ( ! is_dir( $root ) && ! wp_mkdir_p( $root ) ) { return; }
    if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) { return; }

    $files = array(
        'swiper-bundle.min.js' => array( 120000, 'Swiper', array(
            'https://cdnjs.cloudflare.com/ajax/libs/Swiper/14.3.0/swiper-bundle.min.js',
            'https://cdn.jsdelivr.net/npm/swiper@14.3.0/swiper-bundle.min.js',
        ) ),
        'swiper-bundle.min.css' => array( 10000, '.swiper', array(
            'https://cdnjs.cloudflare.com/ajax/libs/Swiper/14.3.0/swiper-bundle.min.css',
            'https://cdn.jsdelivr.net/npm/swiper@14.3.0/swiper-bundle.min.css',
        ) ),
        'LICENSE' => array( 500, 'MIT', array(
            'https://cdn.jsdelivr.net/npm/swiper@14.3.0/LICENSE',
            'https://unpkg.com/swiper@14.3.0/LICENSE',
        ) ),
    );
    $downloads = array();
    foreach ( $files as $name => $settings ) {
        foreach ( $settings[2] as $url ) {
            // wp_safe_remote_get validates the HTTPS request and redirects.
            $response = wp_safe_remote_get( $url, array(
                'timeout' => 7,
                'redirection' => 2,
                'limit_response_size' => 240000,
            ) );
            if ( is_wp_error( $response ) ||
                 200 !== wp_remote_retrieve_response_code( $response ) ) {
                continue;
            }
            $body = wp_remote_retrieve_body( $response );
            if ( strlen( $body ) >= $settings[0] &&
                 false !== strpos( $body, $settings[1] ) ) {
                $downloads[ $name ] = $body;
                break;
            }
        }
        // Never publish incomplete, unverified vendor files.
        if ( ! isset( $downloads[ $name ] ) ) { return; }
    }

    // Stage every file first; publish a completion marker last.
    $temporary = array();
    foreach ( $downloads as $name => $body ) {
        $tmp = tempnam( $dir, '.pvdak-swiper-' );
        if ( ! $tmp ) { break; }
        $written = file_put_contents( $tmp, $body, LOCK_EX );
        if ( strlen( $body ) !== $written || ! chmod( $tmp, 0644 ) ) {
            @unlink( $tmp );
            break;
        }
        $temporary[ $name ] = $tmp;
    }
    if ( count( $temporary ) !== count( $downloads ) ) {
        foreach ( $temporary as $tmp ) { @unlink( $tmp ); }
        return;
    }

    @unlink( $dir . '/SHA256SUMS' );
    foreach ( $temporary as $name => $tmp ) {
        if ( ! rename( $tmp, $dir . '/' . $name ) ) {
            foreach ( $temporary as $unused ) { if ( is_file( $unused ) ) { @unlink( $unused ); } }
            return;
        }
    }
    $checksums = '';
    foreach ( $downloads as $name => $body ) {
        $checksums .= hash( 'sha256', $body ) . '  ' . $dir . '/' . $name . "\n";
    }
    $marker = tempnam( $dir, '.pvdak-swiper-' );
    if ( $marker && strlen( $checksums ) === file_put_contents( $marker, $checksums, LOCK_EX ) ) {
        @chmod( $marker, 0644 );
        rename( $marker, $dir . '/SHA256SUMS' );
    }
    if ( $marker && is_file( $marker ) ) { @unlink( $marker ); }
}
add_action( 'admin_init', 'pvdak_swiper_admin_ensure_local', 20 );
