<?php
/**
 * WP Template Theme Functions
 *
 * @package baizy
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * パス・URIの定数化
 */
define( 'BAIZY_THEME_PATH', get_template_directory() );
define( 'BAIZY_THEME_URI', get_template_directory_uri() );

/**
 * Composerオートローダーを読み込み
 *
 * テーマの全機能は vendor/autoload.php 経由で読み込まれるため、
 * 無い場合は原因が分かるメッセージで停止する（dist ビルドでは必ず同梱される）。
 */
if ( ! file_exists( BAIZY_THEME_PATH . '/vendor/autoload.php' ) ) {
	wp_die( 'baizy テーマ: vendor/autoload.php が見つかりません。テーマディレクトリで <code>composer install</code> を実行してください。' );
}
require_once BAIZY_THEME_PATH . '/vendor/autoload.php';
