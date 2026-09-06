<?php
/**
 * テーマのエントリーポイント（定数定義と Composer オートローダー読み込み）
 *
 * @package baizy
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// パス・URIの定数化
define( 'BAIZY_THEME_PATH', get_template_directory() );
define( 'BAIZY_THEME_URI', get_template_directory_uri() );

// Composerオートローダーを読み込み（テーマの全機能がここ経由のため、無い場合は停止する）
if ( ! file_exists( BAIZY_THEME_PATH . '/vendor/autoload.php' ) ) {
	wp_die( 'baizy テーマ: vendor/autoload.php が見つかりません。テーマディレクトリで <code>composer install</code> を実行してください。' );
}
require_once BAIZY_THEME_PATH . '/vendor/autoload.php';
