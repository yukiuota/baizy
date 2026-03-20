<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// -----------------------------------------------------
// 抜粋（the_excerpt）のカスタマイズ
// -----------------------------------------------------

// 省略記号を空に
add_filter( 'excerpt_more', function( $more ) {
    return '';
}, 999 );

// 文字数制限
add_filter( 'excerpt_length', function( $length ) {
    return 120;
}, 999 );

// 抜粋を改行対応にする
add_filter( 'get_the_excerpt', function( $excerpt ) {
    return nl2br( $excerpt );
}, 999 );

// 抜粋でwpautopを無効化して改行を保持
add_filter( 'get_the_excerpt', function( $excerpt ) {
    remove_filter( 'the_excerpt', 'wpautop' );
    return $excerpt;
}, 1 );

// 自動生成される抜粋でも改行を保持
add_filter( 'wp_trim_excerpt', function( $text, $raw_excerpt ) {
    if ( '' == $raw_excerpt ) {
        $text = get_the_content( '' );
        $text = strip_shortcodes( $text );
        $text = preg_replace( '/<br\s*\/?>/i', '|||LINEBREAK|||', $text );
        $text = apply_filters( 'the_content', $text );
        $text = str_replace( ']]>', ']]&gt;', $text );
        $text = wp_strip_all_tags( $text, true );
        $text = str_replace( array( "\r\n", "\r", "\n" ), '|||LINEBREAK|||', $text );

        $excerpt_length = apply_filters( 'excerpt_length', 100 );
        $excerpt_more   = apply_filters( 'excerpt_more', '' );
        $text = wp_trim_words( $text, $excerpt_length, $excerpt_more );
        $text = str_replace( '|||LINEBREAK|||', '<br>', $text );
    } else {
        $text = nl2br( $raw_excerpt );
    }
    return $text;
}, 10, 2 );

// 抜粋表示時にwpautopを無効化
remove_filter( 'the_excerpt', 'wpautop' );
