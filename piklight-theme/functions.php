<?php
/**
 * Theme setup and block registration.
 *
 * @package PikLight
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PIKLIGHT_VERSION', '1.0.0' );
define( 'PIKLIGHT_DIR', get_template_directory() );
define( 'PIKLIGHT_URI', get_template_directory_uri() );

require_once PIKLIGHT_DIR . '/inc/customizer.php';
require_once PIKLIGHT_DIR . '/inc/blocks.php';

function piklight_setup() {
	load_theme_textdomain( 'piklight', PIKLIGHT_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_editor_style( 'assets/css/style.css' );

	register_nav_menus(
		array(
			'primary' => __( 'Menu główne', 'piklight' ),
			'footer'  => __( 'Menu w stopce', 'piklight' ),
		)
	);
}
add_action( 'after_setup_theme', 'piklight_setup' );

function piklight_enqueue_assets() {
	wp_enqueue_style( 'piklight-style', PIKLIGHT_URI . '/assets/css/style.css', array(), PIKLIGHT_VERSION );
}
add_action( 'wp_enqueue_scripts', 'piklight_enqueue_assets' );

function piklight_get_theme_option( $key, $default = '' ) {
	$value = get_theme_mod( $key, $default );
	return is_string( $value ) ? trim( $value ) : $value;
}
