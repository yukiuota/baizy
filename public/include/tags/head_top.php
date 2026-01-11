<?php 
if ( !defined( 'ABSPATH' ) ) exit; 

// head上部に追加するタグ
// カスタマイザーで設定されたコードを出力
$head_top_code = get_theme_mod( 'baizy_head_top_code', '' );
if ( !empty( $head_top_code ) ) {
    echo wp_kses_post( $head_top_code ) . "\n";
}
?>