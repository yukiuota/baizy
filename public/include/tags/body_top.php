<?php 
if ( !defined( 'ABSPATH' ) ) exit; 

// body上部に追加するタグ
// カスタマイザーで設定されたコードを出力
$body_top_code = get_theme_mod( 'baizy_body_top_code', '' );
if ( !empty( $body_top_code ) ) {
    echo wp_kses_post( $body_top_code ) . "\n";
}
?>