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



// Contact Form 7 の必須エラーを項目ごとの文言にする
// CF7 標準の必須エラーは全項目共通の 1 文言（フォームの「メッセージ」設定）しか持てないため、
// CF7 本体（優先度 10）より先に項目別メッセージ付きの required ルールを追加する。
// SWV はクライアント・サーバーとも 1 項目につき最初のエラーだけを採用するので、こちらの文言が優先される。
add_action( 'wpcf7_swv_create_schema', 'baizy_cf7_add_required_rules', 9, 2 );
function baizy_cf7_add_required_rules( WPCF7_SWV_Schema $schema, WPCF7_ContactForm $contact_form ): void {
	if ( ! function_exists( 'wpcf7_swv_create_rule' ) ) {
		return;
	}

	$labels = baizy_cf7_field_labels();

	foreach ( $contact_form->scan_form_tags() as $tag ) {
		// radio は「*」なしでも CF7 側で必須扱いになるので、同じ条件にそろえる
		$is_required = $tag->is_required() || 'radio' === $tag->type;
		if ( ! $is_required || ! isset( $labels[ $tag->name ] ) ) {
			continue;
		}

		// 選択式の項目は「入力」ではなく「選択」と案内する
		$verb  = in_array( $tag->basetype, array( 'select', 'checkbox', 'radio' ), true ) ? '選択' : '入力';
		$error = sprintf( '%sを%sしてください。', $labels[ $tag->name ], $verb );

		$schema->add_rule(
			wpcf7_swv_create_rule(
				'required',
				array(
					'field' => $tag->name,
					'error' => $error,
				)
			)
		);
	}
}

// 必須エラーに表示する項目名（フォームタグの name => 項目名）
// ここにない項目は、フォームの「メッセージ」設定の共通文言が表示される
function baizy_cf7_field_labels(): array {
	return array(
		'your-name'    => '氏名',
		'your-email'   => 'メールアドレス',
		'url'          => 'URL',
		'tel'          => '電話番号',
		'number'       => '数値',
		'date'         => '日付',
		'your-message' => 'お問い合わせ内容',
		'select'       => 'ドロップダウンメニュー',
		'checkbox'     => 'チェックボックス',
		'radio'        => 'ラジオボタン',
	);
}



// Contact Form 7で自動挿入されるPタグ、brタグを削除
add_filter( 'wpcf7_autop_or_not', 'baizy_wpcf7_autop_return_false' );
function baizy_wpcf7_autop_return_false() {
	return false;
}
