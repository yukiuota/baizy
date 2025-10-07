<?php
/**
 * WP Template Theme Functions
 *
 * @package baizy
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * パス・URIの定数化
 */
define( 'BAIZY_THEME_PATH', get_template_directory() );
define( 'BAIZY_THEME_URI', get_template_directory_uri() );


function baizy_setup() {
    // 国際化対応
    load_theme_textdomain( 'baizy', get_template_directory() . '/app/languages' );
    
    // ブロックエディタサポート
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'editor-styles' );
    
    // ブロックパターンサポート(WordPress 5.5以降)
    add_theme_support( 'block-patterns' );
}
add_action( 'after_setup_theme', 'baizy_setup' );

/**
 * Composerオートローダーを読み込み
 */
if ( file_exists( get_template_directory() . '/vendor/autoload.php' ) ) {
    require_once get_template_directory() . '/vendor/autoload.php';
}