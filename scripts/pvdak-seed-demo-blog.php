<?php
/**
 * One-time opt-in WP-CLI seed of five clearly labelled lorem-ipsum posts.
 * Command: wp eval-file <PRIVATE_ARCHIVED_SCRIPT> --path=<WP_ROOT>
 * Never run from a public web request, cron or theme activation.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI || 'cli' !== PHP_SAPI ) {
    exit( "WP-CLI_ONLY\n" );
}
function pvdak_demo_abort( $reason ) { WP_CLI::error( 'PVDAK_DEMO_STOP: ' . $reason ); }
if ( rtrim( site_url(), '/' ) !== 'https://pvdak.com' || get_stylesheet() !== 'pvdakcom-main' ) {
    pvdak_demo_abort( 'Unexpected target site or theme' );
}
if ( ! function_exists( 'pvdak_maintenance_enabled' ) || ! pvdak_maintenance_enabled() ||
    ( function_exists( 'pvdak_core_intake_open' ) && pvdak_core_intake_open() ) ||
    ( function_exists( 'pvdak_core_member_intake_open' ) && pvdak_core_member_intake_open() ) ) {
    pvdak_demo_abort( 'Maintenance must be ON and all legal/member intake OFF' );
}
if ( ! function_exists( 'imagecreatetruecolor' ) || ! function_exists( 'imagejpeg' ) ||
     ! function_exists( 'wp_generate_attachment_metadata' ) ) {
    pvdak_demo_abort( 'GD / WordPress image processing required for sample featured JPEGs' );
}
if ( ! function_exists( 'update_field' ) ) {
    pvdak_demo_abort( 'ACF required so the magazine category can be selected on the homepage' );
}
$users = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
$author = ! empty( $users ) ? (int) $users[0] : 0;
if ( ! $author ) { pvdak_demo_abort( 'No administrator available as post author' ); }
$front = (int) get_option( 'page_on_front' );
if ( 'page' !== get_option( 'show_on_front' ) || ! $front ) {
    pvdak_demo_abort( 'Static front page is not configured' );
}
$category = function_exists( 'pvdak_home_selected_category' ) ?
    (int) pvdak_home_selected_category( 'magazine_category' ) : 0;
if ( ! $category ) {
    $term = get_term_by( 'slug', 'legal-magazine', 'category' );
    if ( $term && ! is_wp_error( $term ) ) {
        $category = (int) $term->term_id;
    } else {
        $term = wp_insert_term( 'مجله حقوقی', 'category', array( 'slug' => 'legal-magazine' ) );
        if ( is_wp_error( $term ) ) { pvdak_demo_abort( $term->get_error_message() ); }
        $category = (int) $term['term_id'];
    }
    update_field( 'field_pvdak_home_magazine_category', $category, $front );
    $verify = (int) pvdak_home_selected_category( 'magazine_category' );
    if ( $verify !== $category ) { pvdak_demo_abort( 'Homepage magazine category could not be linked' ); }
}
$demo = array(
    array( 'pvdak-demo-family', 'نمونه طراحی: پرونده‌های خانواده', 'چیدمان آزمایشی بخش خانواده و صفحات تحریریه', 0 ),
    array( 'pvdak-demo-contract', 'نمونه طراحی: قراردادها و اسناد', 'نمایش نمونه قالب مقاله درباره قراردادها', 1 ),
    array( 'pvdak-demo-property', 'نمونه طراحی: موضوعات ملکی', 'آزمایش کارت و تصویر شاخص مطالب ملکی', 2 ),
    array( 'pvdak-demo-business', 'نمونه طراحی: حقوق شرکت‌ها', 'بررسی ظاهر مطالب تجاری و شرکت‌ها', 3 ),
    array( 'pvdak-demo-work', 'نمونه طراحی: حقوق کار و بیمه', 'مطلب آزمایشی برای سنجش خوانایی و تصاویر', 4 ),
);
function pvdak_demo_color( $im, $rgb ) { return imagecolorallocate( $im, $rgb[0], $rgb[1], $rgb[2] ); }
function pvdak_demo_cover( $slug, $index ) {
    $uploads = wp_upload_dir();
    if ( ! empty( $uploads['error'] ) || ! is_dir( $uploads['path'] ) || ! is_writable( $uploads['path'] ) ) {
        return new WP_Error( 'pvdak_demo_media', 'Uploads path unavailable' );
    }
    $themes = array(
        array( array( 14, 54, 68 ), array( 11, 120, 108 ) ),
        array( array( 22, 41, 73 ), array( 41, 97, 133 ) ),
        array( array( 42, 43, 69 ), array( 109, 88, 135 ) ),
        array( array( 28, 58, 67 ), array( 110, 106, 76 ) ),
        array( array( 44, 65, 73 ), array( 32, 111, 117 ) ),
    );
    $im = imagecreatetruecolor( 1200, 675 );
    if ( ! $im ) { return new WP_Error( 'pvdak_demo_image', 'JPEG canvas unavailable' ); }
    $from = $themes[$index][0]; $to = $themes[$index][1];
    for ( $y = 0; $y < 675; $y++ ) {
        $v = $y / 675;
        $r = (int) ( $from[0] * (1 - $v) + $to[0] * $v );
        $g = (int) ( $from[1] * (1 - $v) + $to[1] * $v );
        $b = (int) ( $from[2] * (1 - $v) + $to[2] * $v );
        imageline( $im, 0, $y, 1200, $y, imagecolorallocate( $im, $r, $g, $b ) );
    }
    $gold = pvdak_demo_color( $im, array( 234, 198, 133 ) );
    $pale = pvdak_demo_color( $im, array( 224, 243, 239 ) );
    $soft = pvdak_demo_color( $im, array( 80, 133, 139 ) );
    $dark = pvdak_demo_color( $im, array( 16, 54, 67 ) );
    imagefilledellipse( $im, 210, 88, 500, 500, $soft );
    imagefilledellipse( $im, 1040, 620, 760, 760, $dark );
    imagesetthickness( $im, 9 );
    imageline( $im, 130, 550, 1070, 550, $gold );
    imagesetthickness( $im, 7 );
    // Five distinct original editorial vector-like legal illustrations.
    if ( 0 === $index ) {
        imageellipse( $im, 600, 264, 155, 155, $pale ); imageellipse( $im, 730, 315, 110, 110, $pale );
        imagearc( $im, 622, 575, 370, 300, 185, 355, $gold );
        imagearc( $im, 792, 540, 220, 235, 184, 360, $pale );
    } elseif ( 1 === $index ) {
        imagerectangle( $im, 470, 125, 760, 490, $pale );
        imageline( $im, 515, 245, 705, 245, $gold );
        imageline( $im, 515, 308, 700, 308, $pale );
        imageline( $im, 515, 370, 640, 370, $pale );
        imageline( $im, 650, 440, 825, 300, $gold );
    } elseif ( 2 === $index ) {
        imagepolygon( $im, array( 425, 325, 615, 145, 805, 325 ), 3, $gold );
        imagerectangle( $im, 470, 328, 760, 545, $pale );
        imagerectangle( $im, 570, 407, 655, 543, $gold );
    } elseif ( 3 === $index ) {
        imagerectangle( $im, 468, 165, 750, 545, $gold );
        for ( $row=0; $row<4; $row++ ) {
            imageline( $im, 520, 230+$row*72, 690, 230+$row*72, $pale );
        }
        imagerectangle( $im, 750, 300, 880, 545, $pale );
    } else {
        imagearc( $im, 630, 270, 320, 220, 180, 360, $gold );
        imagerectangle( $im, 475, 265, 785, 530, $pale );
        imageline( $im, 475, 340, 785, 340, $gold );
        imagerectangle( $im, 590, 320, 670, 395, $gold );
    }
    imagestring( $im, 5, 90, 65, 'PVDAK  /  EDITORIAL', $pale );
    imagestring( $im, 4, 94, 591, 'DEMO CONTENT  -  ' . sprintf( '%02d', $index + 1 ), $gold );
    $filename = wp_unique_filename( $uploads['path'], $slug . '-cover.jpg' );
    $filepath = trailingslashit( $uploads['path'] ) . $filename;
    $written = imagejpeg( $im, $filepath, 88 );
    imagedestroy( $im );
    if ( ! $written || ! is_file( $filepath ) ) {
        return new WP_Error( 'pvdak_demo_image', 'JPEG output failed' );
    }
    return $filepath;
}
require_once ABSPATH . 'wp-admin/includes/image.php';
$made = 0; $present = 0; $fail = 0; $medias = 0;
foreach ( $demo as $entry ) {
    list( $slug, $title, $excerpt, $idx ) = $entry;
    $existing = get_page_by_path( $slug, OBJECT, 'post' );
    if ( $existing ) {
        if ( '1' !== (string) get_post_meta( $existing->ID, '_pvdak_demo_blog', true ) ) {
            WP_CLI::warning( 'Skipped existing non-demo post: ' . $slug ); $fail++; continue;
        }
        $post_id = (int) $existing->ID;
        $present++;
    } else {
        $lorem = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer tincidunt erat vel dolor tempus, at ullamcorper urna finibus. Praesent fermentum, mauris ut facilisis dapibus, purus enim feugiat augue, sed tincidunt lectus justo at ligula.';
        $lorem2 = 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium. Maecenas tristique faucibus dolor, vel auctor arcu aliquet vitae. Cras efficitur, est in fermentum congue, felis elit dignissim metus, nec rhoncus lorem velit id nisl.';
        $body = '<p><strong>محتوای نمونه برای آزمون طراحی سایت؛ این متن مشاوره یا مطلب حقوقی واقعی نیست.</strong></p>' .
            '<p>' . $lorem . '</p><h2>عنوان آزمایشی اول</h2><p>' . $lorem2 . '</p>' .
            '<blockquote><p>' . $lorem . '</p></blockquote>' .
            '<h2>عنوان آزمایشی دوم</h2><p>' . $lorem . '</p><ul><li>بند آزمایشی اول</li><li>بند آزمایشی دوم</li></ul>' .
            '<p>' . $lorem2 . '</p>';
        $post_id = wp_insert_post( array(
            'post_type' => 'post',
            'post_name' => $slug,
            'post_title' => $title,
            'post_content' => $body,
            'post_excerpt' => '[نمونه آزمایشی] ' . $excerpt,
            'post_status' => 'publish',
            'post_author' => $author,
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_category' => array( $category ),
        ), true );
        if ( is_wp_error( $post_id ) || ! $post_id ) {
            WP_CLI::warning( 'Post creation failed: ' . $slug ); $fail++; continue;
        }
        update_post_meta( $post_id, '_pvdak_demo_blog', '1' );
        $made++;
    }
    if ( has_post_thumbnail( $post_id ) ) {
        WP_CLI::log( 'DEMO_POST_OK id=' . $post_id . ' featured=yes' ); continue;
    }
    $filepath = pvdak_demo_cover( $slug, $idx );
    if ( is_wp_error( $filepath ) ) {
        WP_CLI::warning( 'Image generation failed for ' . $slug . ': ' . $filepath->get_error_message() );
        $fail++; continue;
    }
    $attachment_id = wp_insert_attachment( array(
        'post_mime_type' => 'image/jpeg',
        'post_title' => $title . ' — تصویر آزمایشی',
        'post_content' => '',
        'post_status' => 'inherit',
    ), $filepath, $post_id, true );
    if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
        @unlink( $filepath );
        WP_CLI::warning( 'Media registration failed: ' . $slug ); $fail++; continue;
    }
    $metadata = wp_generate_attachment_metadata( $attachment_id, $filepath );
    if ( is_array( $metadata ) ) { wp_update_attachment_metadata( $attachment_id, $metadata ); }
    update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'تصویر گرافیکی آزمایشی: ' . $title );
    update_post_meta( $attachment_id, '_pvdak_demo_cover', '1' );
    set_post_thumbnail( $post_id, $attachment_id );
    $medias++;
    WP_CLI::log( 'DEMO_POST_OK id=' . $post_id . ' featured=' . ( has_post_thumbnail( $post_id ) ? 'yes' : 'no' ) );
}
WP_CLI::log( sprintf( 'PVDAK_DEMO_BLOG_RESULT created=%d existing=%d new_covers=%d failed=%d category=%d', $made, $present, $medias, $fail, $category ) );
if ( $fail ) { pvdak_demo_abort( 'Some demo posts or images need review' ); }
