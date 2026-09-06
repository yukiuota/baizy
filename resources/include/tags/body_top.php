<?php 
if ( !defined( 'ABSPATH' ) ) exit; 

// body上部に追加するタグ（GTM等のscriptを含むため生出力・管理者専用設定）
$body_top_code = get_theme_mod( 'baizy_body_top_code', '' );
if ( !empty( $body_top_code ) ) {
    echo wp_unslash( $body_top_code ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
?>