<?php
if ( !defined( 'ABSPATH' ) ) exit;

// ----------------------------------------------------- //
// 外部リンク管理クラス
// ----------------------------------------------------- //
class ExternalLinksManager {
    private static $links = [
        'company_site' => [
            'name' => '会社サイト',
            'url' => 'https://example.com/'
        ],
        'contact_form' => [
            'name' => 'お問い合わせ',
            'url' => 'https://example.com/contact/'
        ],
        'privacy_policy' => [
            'name' => 'プライバシーポリシー',
            'url' => 'https://example.com/privacy/'
        ],
        // 従来のサンプルリンク（後方互換性のため）
        'sample' => [
            'name' => 'サンプルリンク',
            'url' => 'https://xxx.com/'
        ]
    ];
    
    /**
     * リンク情報を取得
     * @param string $key リンクのキー
     * @return array|null リンク情報
     */
    public static function get_link($key) {
        return isset(self::$links[$key]) ? self::$links[$key] : null;
    }
    
    /**
     * リンクURLのみを取得（エスケープ済み）
     * @param string $key リンクのキー
     * @return string|null エスケープされたリンクURL
     */
    public static function get_url($key) {
        $link = self::get_link($key);
        return $link ? esc_url($link['url']) : null;
    }
    
    /**
     * 全リンクのリストを取得
     * @return array 全リンクの配列
     */
    public static function get_all_links() {
        return self::$links;
    }
}

// ----------------------------------------------------- //
// ショートコード関数
// ----------------------------------------------------- //
function external_url_shortcode($atts) {
    $atts = shortcode_atts(['key' => ''], $atts);
    
    if (empty($atts['key'])) {
        return '<!-- エラー: リンクキーが指定されていません -->';
    }
    
    return ExternalLinksManager::get_url($atts['key']) ?: '';
}
add_shortcode('external_url', 'external_url_shortcode');

// ----------------------------------------------------- //
// 使用例
// ----------------------------------------------------- //

/*
PHP での使用例:
<?php echo ExternalLinksManager::get_url('company_site'); ?>

ショートコードでの使用例:
[external_url key="company_site"]
*/