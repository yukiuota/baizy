<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// 抜粋（the_excerpt）のカスタマイズ

// 省略記号を空に
add_filter(
	'excerpt_more',
	function ( $_more ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return '';
	},
	999
);

// 文字数制限
add_filter(
	'excerpt_length',
	function ( $_length ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return 120;
	},
	999
);

// 改行（<br>）を保持した抜粋を作る（wpautop 無効化は ThemeSetup 側）
// コアの wp_trim_excerpt() はタグをすべて取り除くため、同じ手順のまま <br> だけを残す処理に差し替える。
// wp_trim_excerpt フィルターには対象の投稿が渡らず、get_the_excerpt( $post ) で別の投稿を指定されたときに
// 本文を取り違えるので、投稿を受け取れる get_the_excerpt の段階で置き換えている
remove_filter( 'get_the_excerpt', 'wp_trim_excerpt', 10 );
add_filter( 'get_the_excerpt', 'baizy_trim_excerpt', 10, 2 );

/**
 * 抜粋を生成する（手入力の抜粋は改行を <br> に、自動生成は本文の <br> を保持する）
 *
 * @param string            $text 手入力の抜粋（未入力なら空文字）
 * @param \WP_Post|int|null $post 対象の投稿（省略時は現在の投稿）
 * @return string 抜粋（<br> を含む HTML）
 */
function baizy_trim_excerpt( $text = '', $post = null ): string {
	$raw_excerpt = (string) $text;

	if ( '' !== trim( $raw_excerpt ) ) {
		$text = nl2br( $raw_excerpt );
	} else {
		// 改行の目印。文字数の上限で切られても途中で分断されないよう 1 文字にする
		// （Unicode の私用領域の文字で、本文には現れない）
		$line_break = "\u{E000}";

		$text = get_the_content( '', false, get_post( $post ) );
		$text = strip_shortcodes( $text );
		$text = excerpt_remove_blocks( $text );
		$text = excerpt_remove_footnotes( $text );

		// 以降はコアの wp_trim_excerpt() と同じ手順（抜粋では不要なフィルターを一時的に外す）
		$filter_image_removed = remove_filter( 'the_content', 'wp_filter_content_tags', 12 );
		$filter_block_removed = remove_filter( 'the_content', 'do_blocks', 9 );

		/** This filter is documented in wp-includes/post-template.php */
		$text = apply_filters( 'the_content', $text );
		$text = str_replace( ']]>', ']]&gt;', $text );

		if ( $filter_block_removed ) {
			add_filter( 'the_content', 'do_blocks', 9 );
		}
		if ( $filter_image_removed ) {
			add_filter( 'the_content', 'wp_filter_content_tags', 12 );
		}

		// タグを取り除く前に <br> を目印へ置き換えておく
		$text = preg_replace( '/<br\s*\/?>/i', $line_break, $text );

		/** This filter is documented in wp-includes/formatting.php */
		$excerpt_length = (int) apply_filters( 'excerpt_length', 55 );
		/** This filter is documented in wp-includes/formatting.php */
		$excerpt_more = apply_filters( 'excerpt_more', ' [&hellip;]' );

		$text = wp_trim_words( $text, $excerpt_length, $excerpt_more );
		// 先頭・末尾の改行は余分な <br> になるため取り除いてから戻す
		// （trim() はバイト単位で削るためマルチバイト文字を壊す。u 修飾子付きの正規表現で文字単位に処理する）
		$text = (string) preg_replace( '/^[\x{E000}\s]+|[\x{E000}\s]+$/u', '', $text );
		$text = str_replace( $line_break, '<br>', $text );
	}

	/** This filter is documented in wp-includes/formatting.php */
	return (string) apply_filters( 'wp_trim_excerpt', $text, $raw_excerpt );
}
