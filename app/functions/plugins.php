<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// プラグインカスタマイズ

// Contact Form7

// Contact Form7 の JS と CSS を全ページで読み込むのを無効化
add_filter( 'wpcf7_load_js', '__return_false' );
add_filter( 'wpcf7_load_css', '__return_false' );

// ショートコードがあるページだけ自動判定して Contact Form7 の JS と CSS を読み込む
function baizy_enqueue_cf7_assets() {
	global $post;
	if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'contact-form-7' ) ) {
		if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
			wpcf7_enqueue_scripts();
		}
		if ( function_exists( 'wpcf7_enqueue_styles' ) ) {
			wpcf7_enqueue_styles();
		}
	}
}
add_action( 'wp_enqueue_scripts', 'baizy_enqueue_cf7_assets' );

// Contact Form7のカスタマイズするCSS・JSの読み込み
function baizy_enqueue_custom_assets_for_specific_page() {
	global $post;
	if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'contact-form-7' ) ) {
		// CSSファイルの読み込み
		wp_enqueue_style( 'custom-page-style', BAIZY_THEME_URI . '/app/plugins/p_cf7/style.css', array(), '1.0.0' );

		// JavaScriptファイルの読み込み（jQuery非依存・defer付与）
		wp_enqueue_script(
			'custom-page-script',
			BAIZY_THEME_URI . '/app/plugins/p_cf7/form.js',
			array(),
			'1.0.0',
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		// JavaScriptにサンクスページのURLを渡す
		wp_localize_script(
			'custom-page-script',
			'cf7CustomSettings',
			array(
				'thanksPageUrl' => esc_url( home_url( '/thanks/' ) ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'baizy_enqueue_custom_assets_for_specific_page' );



// Contact Form 7で自動挿入されるPタグ、brタグを削除
add_filter( 'wpcf7_autop_or_not', 'baizy_wpcf7_autop_return_false' );
function baizy_wpcf7_autop_return_false() {
	return false;
}
