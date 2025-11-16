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

/**
 * Composerオートローダーを読み込み
 */
if ( file_exists( get_template_directory() . '/vendor/autoload.php' ) ) {
    require_once get_template_directory() . '/vendor/autoload.php';
}