<?php
/** No WP/DB: privacy-safe tests for hero, four editor-picked Pages & topics carousel. */
define( 'ABSPATH', __DIR__ );
class WP_Post { public $ID; public $post_content = ''; function __construct( $id ) { $this->ID = $id; } }
$GLOBALS['quick_test_selection'] = array();
$GLOBALS['quick_test_menu'] = array();
$GLOBALS['quick_test_pages'] = array();
for ( $i = 1; $i <= 17; $i++ ) {
    $GLOBALS['quick_test_pages'][ $i ] = 'publish';
    $GLOBALS['quick_test_menu'][] = (object) array(
        'object' => 'page', 'object_id' => $i, 'title' => 'Menu ' . $i,
        'url' => 'https://example.invalid/page-' . $i
    );
}
function pvdak_home_value( $key, $default = 0 ) {
    return isset( $GLOBALS['quick_test_selection'][ $key ] ) ?
        $GLOBALS['quick_test_selection'][ $key ] : $default;
}
function absint( $v ) { return abs( (int) $v ); }
function get_nav_menu_locations() { return array( 'pvdak_quick' => 21 ); }
function wp_get_nav_menu_items( $id ) { return $GLOBALS['quick_test_menu']; }
function get_post_type( $id ) { return isset( $GLOBALS['quick_test_pages'][ $id ] ) ? 'page' : false; }
function get_post_status( $id ) {
    return isset( $GLOBALS['quick_test_pages'][ $id ] ) ? $GLOBALS['quick_test_pages'][ $id ] : false;
}
function get_the_title( $id ) { return 'Published Page ' . $id; }
function get_permalink( $id ) { return 'https://example.invalid/page-' . $id; }
require dirname( __DIR__, 2 ) . '/inc/pvdak-home-quick-links.php';
$checks = 0;
function pvdak_layout_expect( $okay, $message ) {
    global $checks;
    $checks++;
    if ( ! $okay ) { fwrite( STDERR, "LAYOUT_FAIL: " . $message . "\n" ); exit( 1 ); }
}
$links = pvdak_home_quick_pages();
pvdak_layout_expect( count( $links ) === 4, '17 WP menu entries get capped to four' );
pvdak_layout_expect( array_column( $links, 'id' ) === array( 1, 2, 3, 4 ),
    'Fallback preserves menu order for the first four published Pages' );
pvdak_layout_expect( $links[0]['title'] === 'Published Page 1' &&
    $links[0]['url'] === 'https://example.invalid/page-1',
    'Titles and URLs come from real Page data' );

$GLOBALS['quick_test_selection'] = array(
    'quick_page_1' => 9, 'quick_page_2' => new WP_Post( 7 ),
    'quick_page_3' => 3, 'quick_page_4' => 5,
);
$links = pvdak_home_quick_pages();
pvdak_layout_expect( array_column( $links, 'id' ) === array( 9, 7, 3, 5 ),
    'Explicit ACF page slots override the fallback menu and preserve slot order' );
$GLOBALS['quick_test_selection']['quick_page_4'] = 9;
pvdak_layout_expect( count( pvdak_home_quick_pages() ) === 3, 'Duplicates are rejected' );
$GLOBALS['quick_test_selection']['quick_page_4'] = 15;
$GLOBALS['quick_test_pages'][15] = 'draft';
pvdak_layout_expect( count( pvdak_home_quick_pages() ) === 3,
    'Unpublished Page is never linked; no fallback menu item gets mixed in' );
$GLOBALS['quick_test_selection'] = array( 'quick_page_1' => 9 );
pvdak_layout_expect( array_column( pvdak_home_quick_pages(), 'id' ) === array( 9 ),
    'Incomplete explicit selection never pulls the rest of the old 17-link menu' );
$GLOBALS['quick_test_selection'] = array();
array_unshift( $GLOBALS['quick_test_menu'], (object) array( 'object' => 'custom', 'object_id' => 0 ) );
pvdak_layout_expect( count( pvdak_home_quick_pages() ) === 4,
    'Custom menu links are ignored; four published Pages still used' );
$root = dirname( __DIR__, 2 );
$front = file_get_contents( $root . '/front-page.php' );
$js = file_get_contents( $root . '/assets/js/pvdak-home.js' );
$css = file_get_contents( $root . '/style.css' );
$acf = file_get_contents( $root . '/inc/pvdak-home-acf.php' );
$functions = file_get_contents( $root . '/functions.php' );
pvdak_layout_expect( false === strpos( $front, 'class="pvdak-home-hero-actions"' ),
    'Three hero service tiles were removed' );
pvdak_layout_expect( false !== strpos( $front, 'pvdak_home_quick_pages()' ) &&
    false === strpos( $front, 'foreach ( $quick_items' ),
    'Hero ribbon uses only the capped four-Page resolver' );
pvdak_layout_expect( false !== strpos( $front, 'data-pvdak-swiper-track' ) &&
    false !== strpos( $front, 'swiper-wrapper' ) &&
    false !== strpos( $front, 'swiper-slide' ) &&
    false !== strpos( $front, 'data-pvdak-topic' ),
    'Legal landing Page links use official swiper/swiper-wrapper/swiper-slide HTML' );
pvdak_layout_expect( false !== strpos( $front, 'data-pvdak-topic-prev' ) &&
    false !== strpos( $front, 'data-pvdak-topic-next' ),
    'Carousel has labelled manual navigation controls' );
pvdak_layout_expect( false !== strpos( $css, '--pvdak-topic-visible: 6' ) &&
    false !== strpos( $css, '--pvdak-topic-visible: 4' ) &&
    false !== strpos( $css, '--pvdak-topic-visible: 2' ),
    'Six desktop cards and responsive three/two slide counts' );
pvdak_layout_expect( false !== strpos( $css, 'flex-flow: row nowrap' ) &&
    false !== strpos( $css, '.pvdak-topics-swiper.is-ready' ) &&
    false !== strpos( $css, '.pvdak-home-topic.swiper-slide' ),
    'Official Swiper slide widths override the previous fixed-scroll layout' );
pvdak_layout_expect( false !== strpos( $js, 'new window.Swiper(container' ) &&
    false !== strpos( $js, 'loop: true' ) &&
    false !== strpos( $js, 'autoplay:' ) &&
    false !== strpos( $js, 'delay: 4700' ) &&
    false !== strpos( $js, 'slidesPerView: 6' ) &&
    false !== strpos( $js, '701: { slidesPerView: 3' ) &&
    false !== strpos( $js, 'breakpoints:' ) &&
    false === strpos( $js, 'track.appendChild(first)' ),
    'Native Swiper runtime owns autoplay, responsive layout and infinite loop' );
pvdak_layout_expect(
    false !== strpos( $js, 'Math.max(3, visibleSlots() + 1)' ) &&
    false !== strpos( $js, 'updateAutoplay();' ),
    'D06: mobile Swiper avoids two-slide loop clones and restores paused state'
);
pvdak_layout_expect( false !== strpos( $js, 'prefers-reduced-motion' ) &&
    false !== strpos( $js, 'document.hidden' ),
    'Reduced motion and background pause are supported' );
pvdak_layout_expect( false !== strpos( $js, 'swiper.destroy(true, true)' ) &&
    false !== strpos( $js, 'filterTopics()' ) &&
    false !== strpos( $js, 'syncSwiper()' ),
    'Search safely destroys and restores true Swiper loop' );
pvdak_layout_expect( false !== strpos( $functions, 'get_template_directory_uri()' ) &&
    false !== strpos( $functions, '/assets/vendor/swiper-14.3.0' ) &&
    false !== strpos( $functions, 'swiper-bundle.min.css' ) &&
    false !== strpos( $functions, 'swiper-bundle.min.js' ) &&
    false !== strpos( $functions, "'pvdak-swiper'" ),
    'Actual pinned Swiper files are enqueued directly from the versioned theme' );
pvdak_layout_expect( false !== strpos( $functions, "array( 'pvdak-swiper' )" ) &&
    false === strpos( $functions, 'cdn.jsdelivr.net' ) &&
    false === strpos( $functions, 'unpkg.com' ) &&
    false === strpos( $js, 'unpkg.com' ) &&
    false === strpos( $js, 'cdn.jsdelivr.net' ),
    'Initializer is ordered after local Swiper and no browser CDN dependency remains' );
$vendor = $root . '/assets/vendor/swiper-14.3.0';
pvdak_layout_expect( is_file( $vendor . '/swiper-bundle.min.js' ) &&
    filesize( $vendor . '/swiper-bundle.min.js' ) >= 120000 &&
    is_file( $vendor . '/swiper-bundle.min.css' ) &&
    filesize( $vendor . '/swiper-bundle.min.css' ) >= 10000 &&
    is_file( $vendor . '/LICENSE' ) &&
    filesize( $vendor . '/LICENSE' ) >= 500,
    'The version-controlled theme contains real official JS, CSS and MIT license' );
pvdak_layout_expect( false !== strpos( file_get_contents( $vendor . '/swiper-bundle.min.js' ), 'Swiper 14.3.0' ) &&
    false !== strpos( file_get_contents( $vendor . '/swiper-bundle.min.css' ), 'Swiper 14.3.0' ) &&
    false !== strpos( file_get_contents( $vendor . '/LICENSE' ), 'MIT License' ),
    'Vendored files identify the pinned version and license' );
pvdak_layout_expect( false === strpos( $functions, 'inc/pvdak-swiper-local.php' ) &&
    false === strpos( $functions, 'wp_get_upload_dir()' ) &&
    false === strpos( $functions, 'wp_safe_remote_get' ),
    'No dynamic Swiper installer or remote downloader is executed' );
pvdak_layout_expect( false !== strpos( $front, 'pvdak_topic_card_svg( $legal_page )' ) &&
    false !== strpos( $front, 'pvdak_topic_card_caption( $legal_page )' ) &&
    false !== strpos( $css, '.pvdak-topic-card__icon' ),
    'Topic cards include dedicated vectors, titles and captions' );
$icons_file = file_get_contents( $root . '/inc/pvdak-topic-card.php' );
pvdak_layout_expect( substr_count( $icons_file, "=> '<path" ) >= 10 &&
    false !== strpos( $icons_file, "'family' =>" ) &&
    false !== strpos( $icons_file, "'labor-law' =>" ),
    'Hand-authored vectors cover the legal landing slugs' );
pvdak_layout_expect( false !== strpos( $front, 'pvdak-topics-head-actions' ) &&
    false !== strpos( $front, 'pvdak-topics-control--next' ) &&
    false !== strpos( $front, 'pvdak-topics-control--prev' ) &&
    false !== strpos( $front, 'viewBox="0 0 24 24"' ),
    'Swiper controls use prominent, keyboard-accessible custom SVG arrows' );
pvdak_layout_expect( false !== strpos( $css, '.pvdak-topics-section .pvdak-topic-card__icon svg' ) &&
    false !== strpos( $css, 'text-align:center' ) &&
    false !== strpos( $css, 'margin-inline:auto' ),
    'Topic SVG badge and all card text are horizontally centered' );
pvdak_layout_expect( false !== strpos( $front, "'magazine_category', 5" ) &&
    false !== strpos( $front, 'array_slice( $magazine_other, 0, 4 )' ) &&
    false !== strpos( $front, 'pvdak-magazine-aside-picture' ) &&
    false !== strpos( $front, '<a class="pvdak-magazine-feature' ) &&
    false !== strpos( $front, '<a class="pvdak-magazine-aside-card' ),
    'Magazine shows one linked feature plus four linked image cards' );
$single = file_get_contents( $root . '/single.php' );
$related = file_get_contents( $root . '/template-parts/related-posts.php' );
$comments_file = file_get_contents( $root . '/comments.php' );
$article = file_get_contents( $root . '/template-parts/content-single.php' );
pvdak_layout_expect( false !== strpos( $single, "get_template_part( 'template-parts/related-posts' )" ) &&
    false !== strpos( $single, 'comments_template()' ) &&
    false !== strpos( $article, 'pvdak-article-cover' ) &&
    false !== strpos( $article, 'pvdak-article-byline' ),
    'Single article has a feature image, metadata, related posts and comments' );
pvdak_layout_expect( false !== strpos( $related, "'post_status' => 'publish'" ) &&
    false !== strpos( $related, "'category__in'" ) &&
    false !== strpos( $related, "'post__not_in'" ) &&
    false !== strpos( $related, "'posts_per_page' => 3" ),
    'Related articles use only published same-category posts excluding current item' );
pvdak_layout_expect( false !== strpos( $comments_file, 'have_comments()' ) &&
    false !== strpos( $comments_file, 'wp_list_comments' ) &&
    false !== strpos( $comments_file, 'comments_open()' ) &&
    false !== strpos( $comments_file, 'comment_form(' ),
    'Comments use WordPress-native pagination and permission-aware form' );
$favicon = file_get_contents( $root . '/assets/icons/pvdak-pv.svg' );
$maintenance = file_get_contents( $root . '/pvdak-maintenance.php' );
pvdak_layout_expect( false !== strpos( $favicon, '<svg' ) &&
    false !== strpos( $favicon, 'aria-label="PV"' ) &&
    false !== strpos( $favicon, 'fill="url(#pvdak-pv-bg)"' ),
    'Bundled small PV favicon artwork exists as a real local SVG' );
pvdak_layout_expect( false !== strpos( $functions, 'pvdak_pv_favicon_markup' ) &&
    false !== strpos( $functions, "add_action( 'wp_head', 'pvdak_pv_favicon_markup'" ) &&
    false !== strpos( $functions, "add_action( 'login_head', 'pvdak_pv_favicon_markup'" ) &&
    false !== strpos( $maintenance, 'assets/icons/pvdak-pv.svg' ),
    'PV favicon is loaded on normal site, login and standalone maintenance pages' );
pvdak_layout_expect( false !== strpos( $front, 'class="pvdak-home-process pvdak-process-timeline"' ) &&
    false !== strpos( $front, 'pvdak-process-step-icon' ) &&
    false !== strpos( $front, '<ol class="pvdak-home-grid pvdak-home-grid--4 pvdak-process-grid"' ) &&
    false !== strpos( $front, 'pvdak_home_value( "step_{$i}_title" )' ) &&
    false !== strpos( $front, 'pvdak_home_value( "step_{$i}_description" )' ),
    'Process timeline keeps four editable ACF steps and accessible ordered structure' );
// Stage four uses a filled badge (fill="#134a51") inside an SVG whose
// outlines intentionally inherit currentColor. Both variants are valid.
pvdak_layout_expect(
    ( false !== strpos( $front, 'fill="#134a51"' ) ||
      false !== strpos( $front, 'stroke="#134a51"' ) ) &&
    false !== strpos( $front, 'm15 23 6 6 12-12' ) &&
    false !== strpos( $front, 'm32 35 3 3 5-6' ) &&
    false !== strpos( $front, 'pvdak-process-step-icon' ) &&
    false !== strpos( $css, '.pvdak-process-timeline .pvdak-process-step-icon svg' ),
    'All process stages have dedicated native SVG iconography' );
pvdak_layout_expect( false === strpos( $css, '.pvdak-process-timeline .pvdak-process-grid::before' ) &&
    false !== strpos( $css, 'grid-template-columns:repeat(2,minmax(0,1fr))' ) &&
    false !== strpos( $css, '@media(max-width:350px)' ) &&
    false !== strpos( $css, '@media(prefers-reduced-motion:reduce)' ),
    'Process tiles have no overlapping connector line; responsive and reduced-motion styling remains' );
$demo_gate = file_get_contents( $root . '/inc/pvdak-demo-comment-preview.php' );
pvdak_layout_expect( false !== strpos( $functions, 'inc/pvdak-demo-comment-preview.php' ) &&
    false !== strpos( $demo_gate, "add_filter( 'comments_open'" ) &&
    false !== strpos( $demo_gate, 'pvdak_maintenance_can_preview_site()' ) &&
    false !== strpos( $demo_gate, "'_pvdak_demo_blog'" ) &&
    false !== strpos( $demo_gate, "'publish' !== " ),
    'Marked demo posts allow WP-native comments only for admins during maintenance' );
pvdak_layout_expect( false !== strpos( $single, 'comments_template();' ) &&
    strpos( $single, 'comments_template();' ) < strpos( $single, "get_template_part( 'template-parts/related-posts' )" ) &&
    false !== strpos( $article, 'href="#respond"' ),
    'Native comments are placed after the article and linked from its heading' );
pvdak_layout_expect( false !== strpos( $comments_file, 'pvdak-comments-compose' ) &&
    false !== strpos( $comments_file, 'comment_form( array(' ) &&
    false !== strpos( $comments_file, 'pvdak-comments-preview-hint' ) &&
    false !== strpos( $comments_file, 'comments_open()' ),
    'WordPress-native comment submission is visible where permissions allow' );
pvdak_layout_expect( false === strpos( $related, 'ادامه مطالعه' ) &&
    false !== strpos( $related, 'مقالات مرتبط' ) &&
    false !== strpos( $css, 'font-size:clamp(24px,2.45vw,33px)' ) &&
    false !== strpos( $css, '.pvdak-comments-compose' ),
    'Related articles have a clear heading and editorial type scale is balanced' );
pvdak_layout_expect( false !== strpos( $front, 'pvdak-start-here-grid' ) &&
    false !== strpos( $front, 'می‌خواهید چه کاری انجام دهید؟' ) &&
    false !== strpos( $front, "'url' => home_url( '/#magazine' )" ) &&
    false === strpos( $front, 'از کدام مسیر وارد می‌شوید؟' ),
    'Three clear homepage destinations replace the confusing old role selector' );
// The repeated field is generated by a four-iteration PHP loop, not four
// duplicated static ACF definitions.
pvdak_layout_expect( false !== strpos( $acf, 'for ( $quick_slot = 1; $quick_slot <= 4; $quick_slot++ )' ) &&
    false !== strpos( $acf, "'type' => 'post_object'" ),
    'Four ACF Page selectors are generated in the homepage editor' );
pvdak_layout_expect(
    false !== strpos( $front, 'class="pvdak-community-illustration"' ) &&
    false !== strpos( $front, 'viewBox="0 0 320 194"' ) &&
    false === strpos( $front, 'pvdak-community-placeholder' ),
    'Community section uses bundled scalable vector fallback rather than an empty gradient box'
);
pvdak_layout_expect(
    false !== strpos( $front, 'if ( $faq_pages )' ) &&
    false === strpos( $front, "if ( $faq_pages || current_user_can( 'edit_pages' ) )" ) &&
    false === strpos( $front, 'یک برگه مادر «پرسش‌های متداول»' ),
    'FAQ section stays hidden without published FAQ pages and never shows internal setup text'
);
pvdak_layout_expect(
    false === strpos( $front, 'pvdak-quick-admin-note' ) &&
    false === strpos( $front, 'pvdak-legal-topics-setup' ) &&
    false === strpos( $front, "|| current_user_can( 'edit_posts' )" ) &&
    false === strpos( $front, 'برای این بخش دسته «اطلاعیه‌ها»' ),
    'Unconfigured quick links, editorial and news areas never expose manager instructions'
);
pvdak_layout_expect(
    false !== strpos( $css, '.pvdak-home-community .pvdak-community-illustration' ) &&
    preg_match( '/Version:\s*0[.]8[.](?:3[6-9]|[4-9][0-9])/', $css ) === 1,
    'Theme version and responsive community vector styles exist'
);
// D08: Confirm admin checks the same published quick links and substantive FAQ.
$readiness = file_get_contents( dirname( __DIR__, 2 ) . '/inc/pvdak-home-readiness.php' );
pvdak_layout_expect(
    false !== strpos( $readiness, "function_exists( 'pvdak_home_quick_pages' )" ) &&
    false !== strpos( $readiness, '4 === $quick_count' ) &&
    false !== strpos( $readiness, "'چهار دسترسی سریع منتشرشده'" ),
    'Admin readiness requires four real published quick Pages' );
pvdak_layout_expect(
    false !== strpos( $readiness, 'strip_shortcodes( $faq_page->post_content )' ) &&
    false !== strpos( $readiness, '$faq_written === $faq_count' ),
    'Admin readiness rejects empty FAQ answers, without writing content' );

pvdak_layout_expect(
    false !== strpos( $readiness, '$topics_with_text >= 3' ) &&
    false !== strpos( $readiness, 'strip_shortcodes( $topic_page->post_content )' ) &&
    false !== strpos( $readiness, "'حداقل سه لندینگ دارای متن'" ),
    'D09: admin demo readiness cannot mistake empty published legal samples for authored pages' );

pvdak_layout_expect(
    false !== strpos( $readiness, "get_page_template_slug( $page->ID )" ) &&
    false !== strpos( $readiness, 'count( $public_templates ) === $public_ready' ) &&
    false !== strpos( $readiness, "'my-pvdak-account' => 'page-templates/pvdak-account.php'" ),
    'D05: required public Pages must be published with their actual native templates' );

pvdak_layout_expect(
    false !== strpos( $readiness, 'pvdak_home_faq_pages( true )' ) &&
    false !== strpos( $readiness, '$faq_written > 0' ) &&
    false !== strpos( $readiness, '$faq_written === $faq_count' ),
    'D08: admin readiness counts published blank FAQ Pages as incomplete'
);
pvdak_layout_expect(
    false !== strpos( file_get_contents( dirname( __DIR__, 2 ) . '/inc/pvdak-home-collections.php' ), 'if ( $include_empty ) { return $published; }' ),
    'D08: FAQ public and admin source selection remain distinct'
);

pvdak_layout_expect(
    false !== strpos( $front, "shortcode_exists( 'pvdak_forum' )" ) &&
    false !== strpos( $front, "has_shortcode( $forum_page->post_content, 'pvdak_forum' )" ) &&
    false !== strpos( $front, "get_permalink( $forum_page->ID )" ),
    'D10: an editor URL alone cannot advertise an unimplemented forum'
);

// D05/D08/D10: execute the actual selectors against a synthetic *closed*
// intake with misleading manual ACF URLs. Neither button may navigate.
function pvdak_core_intake_open() { return ! empty( $GLOBALS['pvdak_test_intake_open'] ); }
function pvdak_core_member_intake_open() { return ! empty( $GLOBALS['pvdak_test_member_open'] ); }
require dirname( __DIR__, 2 ) . '/inc/pvdak-home-collections.php';
// Exercise the actual FAQ selector with one empty published Page.
function get_option( $name ) { return 'page_on_front' === $name ? 1 : null; }
function get_field( $name, $page_id ) {
    return 'faq_parent_page' === $name && 1 === (int) $page_id ? 20 : null;
}
function get_posts( $args ) { return $GLOBALS['pvdak_faq_fixture']; }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function strip_shortcodes( $value ) { return preg_replace( '/\\[[^\\]]+\\]/', '', $value ); }
$GLOBALS['quick_test_pages'][20] = 'publish';
$faq_a = new WP_Post( 21 ); $faq_a->post_content = '<p>پاسخ واقعی اول</p>';
$faq_b = new WP_Post( 22 ); $faq_b->post_content = '   ';
$faq_c = new WP_Post( 23 ); $faq_c->post_content = '<p>پاسخ دوم</p>';
$GLOBALS['pvdak_faq_fixture'] = array( $faq_a, $faq_b, $faq_c );
pvdak_layout_expect(
    count( pvdak_home_faq_pages() ) === 2 &&
    count( pvdak_home_faq_pages( true ) ) === 3,
    'D08: public selector removes the empty FAQ while admin sees all published Pages'
);
$GLOBALS['quick_test_selection']['service_1_url'] = 'https://example.invalid/manual-request';
$GLOBALS['quick_test_selection']['community_url'] = 'https://example.invalid/manual-registration';
pvdak_layout_expect(
    '' === pvdak_home_service_url( 1 ) &&
    '' === pvdak_home_join_url(),
    'Closed legal/member intake must win over explicit editor URL overrides'
);

// D05: enabled CTAs also require an actual published internal shortcode Page,
// and each legal service must match its own request type (not a foreign form).
$GLOBALS['pvdak_test_intake_open'] = true;
$GLOBALS['pvdak_test_member_open'] = true;
$GLOBALS['pvdak_test_shortcodes'] = true;
$GLOBALS['pvdak_cta_pages'] = array(
    40 => array( 'slug' => 'request-lawyer', 'content' => '[pvdak_request_form]', 'state' => 'publish' ),
    41 => array( 'slug' => 'request-consultation', 'content' => '[pvdak_request_form type="consultation"]', 'state' => 'publish' ),
    42 => array( 'slug' => 'request-question', 'content' => '[pvdak_request_form type="question"]', 'state' => 'publish' ),
    43 => array( 'slug' => 'join-legal-community', 'content' => '[pvdak_professional_application]', 'state' => 'publish' ),
    44 => array( 'slug' => 'wrong-type', 'content' => '[pvdak_request_form type="question"]', 'state' => 'publish' ),
    45 => array( 'slug' => 'draft-join', 'content' => '[pvdak_professional_application]', 'state' => 'draft' ),
);
function get_post( $id ) {
    $d = $GLOBALS['pvdak_cta_pages'][ $id ] ?? null;
    if ( ! $d ) { return null; }
    $post = new WP_Post( $id );
    $post->post_type = 'page';
    $post->post_status = $d['state'];
    $post->post_content = $d['content'];
    return $post;
}
function get_page_by_path( $slug, $output = OBJECT, $type = 'page' ) {
    foreach ( $GLOBALS['pvdak_cta_pages'] as $id => $data ) {
        if ( $slug === $data['slug'] ) { return get_post( $id ); }
    }
    return null;
}
function url_to_postid( $url ) {
    if ( ! is_string( $url ) || ! preg_match( '~^https://example[.]invalid/page-([0-9]+)$~', $url, $m ) ) { return 0; }
    return (int) $m[1];
}
function shortcode_exists( $tag ) {
    return ! empty( $GLOBALS['pvdak_test_shortcodes'] ) &&
        in_array( $tag, array( 'pvdak_request_form', 'pvdak_professional_application' ), true );
}
function has_shortcode( $content, $tag ) { return false !== strpos( $content, '[' . $tag ); }
function get_shortcode_regex( $tags ) { return '(\\[?)(pvdak_request_form)([^\\]]*)(\\]?)'; }
function shortcode_parse_atts( $raw ) {
    return preg_match( '~type\\s*=\\s*["\\x27]?([a-z]+)~', $raw, $m ) ? array( 'type' => $m[1] ) : array();
}
function sanitize_key( $key ) { return strtolower( preg_replace( '/[^a-z0-9_-]/', '', $key ) ); }
pvdak_layout_expect(
    pvdak_home_service_url( 1 ) === 'https://example.invalid/page-40' &&
    pvdak_home_service_url( 2 ) === 'https://example.invalid/page-41' &&
    pvdak_home_service_url( 3 ) === 'https://example.invalid/page-42',
    'D05: invalid manually entered URLs fall back to the matching published request forms'
);
$GLOBALS['quick_test_selection']['service_1_url'] = 'https://example.invalid/page-44';
pvdak_layout_expect(
    pvdak_home_service_url( 1 ) === 'https://example.invalid/page-40',
    'D05: a published question form cannot masquerade as a lawyer-request form'
);
$GLOBALS['quick_test_selection']['service_1_url'] = 'https://example.invalid/page-40';
pvdak_layout_expect(
    pvdak_home_service_url( 1 ) === 'https://example.invalid/page-40',
    'D05: published internal forms can be used as manual destinations'
);
$GLOBALS['quick_test_selection']['community_url'] = 'https://example.invalid/page-45';
pvdak_layout_expect(
    pvdak_home_join_url() === 'https://example.invalid/page-43',
    'D05: draft registration Page is ignored in favor of published application'
);
$GLOBALS['pvdak_cta_pages'][43]['state'] = 'draft';
pvdak_layout_expect(
    pvdak_home_join_url() === '',
    'D05: no active registration CTA without an accessible published form'
);
$GLOBALS['pvdak_test_shortcodes'] = false;
pvdak_layout_expect(
    pvdak_home_service_url( 1 ) === '' && pvdak_home_join_url() === '',
    'D05: missing Core shortcodes never generate working-looking links'
);
$GLOBALS['pvdak_test_intake_open'] = false;
$GLOBALS['pvdak_test_member_open'] = false;

$collection_source = file_get_contents( dirname( __DIR__, 2 ) . '/inc/pvdak-home-collections.php' );
pvdak_layout_expect(
    false !== strpos( $collection_source, "array_filter( $published" ) &&
    false !== strpos( $collection_source, 'strip_shortcodes( $page->post_content )' ) &&
    false !== strpos( $collection_source, 'array_slice( $answered, 0, 12 )' ),
    'Published but empty FAQ Pages cannot appear as blank public accordions'
);

echo "PVDAK_HOMEPAGE_LAYOUT_OK: {$checks} checks (synthetic + source; no WP data)\n";
