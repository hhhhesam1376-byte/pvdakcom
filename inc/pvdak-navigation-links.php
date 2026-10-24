<?php
/**
 * Links for the primary fallback menu only when no menu was assigned by the editor.
 * Do not advertise empty hero sections, missing WordPress Pages or disabled intakes.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_default_navigation_links() {
    $links = array( array( 'label' => 'صفحه اصلی', 'url' => home_url( '/' ), 'home' => true ) );
    $page_links = array(
        'legal-topics' => 'موضوعات حقوقی',
        'legal-faq'    => 'پرسش‌های متداول',
        'about-us'     => 'درباره ما',
        'contact-us'   => 'تماس با ما',
    );
    foreach ( $page_links as $slug => $label ) {
        $url = pvdak_public_page_url( $slug );
        if ( $url ) {
            $links[] = array( 'label' => $label, 'url' => $url, 'slug' => $slug );
        }
    }
    // Editorial magazine link is shown only when an actual native category
    // was selected in the homepage editor and WordPress can resolve its URL.
    if ( function_exists( 'pvdak_home_selected_category' ) ) {
        $category_id = pvdak_home_selected_category( 'magazine_category' );
        if ( $category_id ) {
            $magazine = get_term_link( $category_id, 'category' );
            if ( ! is_wp_error( $magazine ) && is_string( $magazine ) && $magazine !== '' ) {
                array_splice( $links, min( 1, count( $links ) ), 0, array( array(
                    'label' => 'مجله حقوقی', 'url' => $magazine
                ) ) );
            }
        }
    }
    return $links;
}
