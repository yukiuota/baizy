<?php
if ( !defined( 'ABSPATH' ) ) exit;

// ----------------------------------------------------- //
// プラグインカスタマイズ
// ----------------------------------------------------- //

// ----------------------------------------------------- //
// Contact Form7
// ----------------------------------------------------- //

// Contact Form7 の JS と CSS を全ページで読み込むのを無効化
add_filter( 'wpcf7_load_js', '__return_false' );
add_filter( 'wpcf7_load_css', '__return_false' );

// ショートコードがあるページだけ自動判定して Contact Form7 の JS と CSS を読み込む
function my_enqueue_cf7_assets() {
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'contact-form-7' ) ) {
        if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
            wpcf7_enqueue_scripts();
        }
        if ( function_exists( 'wpcf7_enqueue_styles' ) ) {
            wpcf7_enqueue_styles();
        }
    }
}
add_action( 'wp_enqueue_scripts', 'my_enqueue_cf7_assets' );

// Contact Form7のカスタマイズするCSS・JSの読み込み
function enqueue_custom_assets_for_specific_page() {
    if (is_page('contact')) { // 固定ページのスラッグ指定
        // CSSファイルの読み込み
        wp_enqueue_style('custom-page-style', BAIZY_THEME_URI . '/app/plugins/p_cf7/style.css', array(), '1.0.0');
        
        // JavaScriptファイルの読み込み
        wp_enqueue_script('custom-page-script', BAIZY_THEME_URI . '/app/plugins/p_cf7/form.js', array('jquery'), '1.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_assets_for_specific_page');



// Contact Form 7で自動挿入されるPタグ、brタグを削除
add_filter('wpcf7_autop_or_not', 'wpcf7_autop_return_false');
function wpcf7_autop_return_false() {
  return false;
}


// カスタムバリデーションエラーメッセージ設定
// ※エラーメッセージを変更するには、管理画面のContactform7の「*」を削除する必要あり
function my_wpcf7_custom_validation_error($result, $tag) {
    // エラーメッセージの設定 [ フィールド名 => エラーメッセージ ]
    $error_messages = array(
        // 'your-name'    => 'お名前は必須項目です。',
        // 'your-kana'    => 'フリガナは必須項目です。',
        // 'your-tel'     => '電話番号は必須項目です。',
        // 'your-type'    => 'お問い合わせ項目は必須項目です。',
        // 'your-message' => 'お問い合わせ内容は必須項目です。',
    );

    // 設定されたフィールドのエラーメッセージを適用
    if (isset($error_messages[$tag->name]) && empty($_POST[$tag->name])) {
        $result->invalidate($tag, $error_messages[$tag->name]);
    }

    return $result;
}

// 各フィールドタイプにフィルターを適用
add_filter('wpcf7_validate_text', 'my_wpcf7_custom_validation_error', 10, 2);
add_filter('wpcf7_validate_tel', 'my_wpcf7_custom_validation_error', 10, 2);
add_filter('wpcf7_validate_checkbox', 'my_wpcf7_custom_validation_error', 10, 2);
add_filter('wpcf7_validate_textarea', 'my_wpcf7_custom_validation_error', 10, 2);