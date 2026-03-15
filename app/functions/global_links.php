<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// クラス実装は app/services/external_links_manager.php を参照

function external_url_shortcode( $atts ): string {
    $atts = shortcode_atts( [ 'key' => '' ], $atts );

    if ( empty( $atts['key'] ) || ! is_string( $atts['key'] ) ) {
        return defined( 'WP_DEBUG' ) && WP_DEBUG ? '<!-- エラー: リンクキーが指定されていません -->' : '';
    }

    $key = sanitize_key( $atts['key'] );
    $url = \Baizy\Services\ExternalLinksManager::get_url( $key );

    if ( $url === '' && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        return '<!-- エラー: リンク "' . esc_html( $key ) . '" が見つかりません -->';
    }

    return $url;
}
add_shortcode( 'external_url', 'external_url_shortcode' );
