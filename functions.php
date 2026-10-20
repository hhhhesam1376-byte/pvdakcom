<?php
/**
 * Theme setup. Business features belong in plugins.
 * @package PVDak
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
function pvdak_setup() {
	load_theme_textdomain( 'pvdak', get_template_directory() . '/languages' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'pvdak_setup' );

function pvdak_enqueue_styles() {
	$theme = wp_get_theme( get_template() );
	wp_enqueue_style( 'pvdak-style', get_template_directory_uri() . '/style.css', array(), $theme->get( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'pvdak_enqueue_styles' );

function pvdak_pattern_categories() {
	register_block_pattern_category( 'pvdak', array( 'label' => __( 'PVDak sections', 'pvdak' ) ) );
}
add_action( 'init', 'pvdak_pattern_categories' );
