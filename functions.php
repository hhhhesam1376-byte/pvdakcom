<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_setup() {
	load_theme_textdomain( 'pvdak', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 240, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	register_nav_menus( array( 'primary' => __( 'منوی اصلی', 'pvdak' ), 'footer' => __( 'منوی فوتر', 'pvdak' ) ) );
}
add_action( 'after_setup_theme', 'pvdak_setup' );

function pvdak_assets() {
	wp_enqueue_style( 'pvdak-style', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'pvdak_assets' );

// Keep unfinished pages hidden from public visitors while development is in progress.
require_once get_template_directory() . '/pvdak-maintenance.php';

/* Homepage content and editor (ACF Free-compatible). */
require_once get_template_directory() . '/inc/pvdak-home-acf.php';

function pvdak_home_assets() {
    if ( is_front_page() ) {
        wp_enqueue_style(
            'pvdak-home',
            get_template_directory_uri() . '/assets/css/pvdak-home.css',
            array( 'pvdak-style' ),
            wp_get_theme()->get( 'Version' )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'pvdak_home_assets' );
