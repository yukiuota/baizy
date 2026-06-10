<?php 
if ( !defined( 'ABSPATH' ) ) exit; 

// body上部に追加するタグ
// カスタマイザーで設定されたコードを出力
// GTM等のscriptタグを含むため head_top.php と同様に生出力する（管理者専用設定）
$body_top_code = get_theme_mod( 'baizy_body_top_code', '' );
if ( !empty( $body_top_code ) ) {
    echo wp_unslash( $body_top_code ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
?>