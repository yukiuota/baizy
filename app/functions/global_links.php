<?php
if ( !defined( 'ABSPATH' ) ) exit;

// ----------------------------------------------------- //
// 外部リンク管理クラス
// ----------------------------------------------------- //
class ExternalLinksManager {
    private static $links = null;
    
    /**
     * JSONファイルからリンクデータを読み込む
     * @return array リンクデータの配列
     */
    private static function load_links() {
        if (self::$links !== null) {
            return self::$links;
        }
        
        $template_dir = get_template_directory();
        $json_path = $template_dir . '/public/settings/links.json';
        
        // パストラバーサル対策
        $real_path = realpath($json_path);
        $real_template_dir = realpath($template_dir);
        
        if ($real_path === false || strpos($real_path, $real_template_dir) !== 0) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ExternalLinksManager: Invalid file path detected.');
            }
            self::$links = [];
            return self::$links;
        }
        
        if (!file_exists($real_path) || !is_readable($real_path)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ExternalLinksManager: links.json not found or not readable.');
            }
            self::$links = [];
            return self::$links;
        }
        
        // ファイルサイズチェック（1MBまで）
        if (filesize($real_path) > 1048576) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ExternalLinksManager: links.json file size exceeds limit.');
            }
            self::$links = [];
            return self::$links;
        }
        
        $json_content = @file_get_contents($real_path);
        
        if ($json_content === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ExternalLinksManager: Failed to read links.json.');
            }
            self::$links = [];
            return self::$links;
        }
        
        $links = json_decode($json_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ExternalLinksManager: JSON decode error - ' . json_last_error_msg());
            }
            self::$links = [];
            return self::$links;
        }
        
        // データ構造の検証
        if (!is_array($links)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ExternalLinksManager: Invalid data structure in links.json.');
            }
            self::$links = [];
            return self::$links;
        }
        
        self::$links = $links;
        return self::$links;
    }
    
    /**
     * リンク情報を取得
     * @param string $key リンクのキー
     * @return array|null リンク情報
     */
    public static function get_link($key) {
        // 入力検証: 文字列型でなければnullを返す
        if (!is_string($key) || empty($key)) {
            return null;
        }
        
        // キー名のサニタイゼーション（英数字、アンダースコア、ハイフンのみ許可）
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $key)) {
            return null;
        }
        
        $links = self::load_links();
        
        if (!isset($links[$key])) {
            return null;
        }
        
        $link = $links[$key];
        
        // データ構造の検証
        if (!is_array($link) || !isset($link['url'])) {
            return null;
        }
        
        return $link;
    }
    
    /**
     * リンクURLのみを取得（エスケープ済み）
     * @param string $key リンクのキー
     * @return string エスケープされたリンクURL（存在しない場合は空文字列）
     */
    public static function get_url($key) {
        $link = self::get_link($key);
        
        if (!$link || !isset($link['url']) || !is_string($link['url'])) {
            return '';
        }
        
        $url = esc_url($link['url']);
        
        // esc_url()が無効なURLを空文字列にすることがあるため、それをチェック
        return $url !== '' ? $url : '';
    }
    
    /**
     * 全リンクのリストを取得
     * @return array 全リンクの配列
     */
    public static function get_all_links() {
        return self::load_links();
    }
}

// ----------------------------------------------------- //
// ショートコード関数
// ----------------------------------------------------- //
function external_url_shortcode($atts) {
    $atts = shortcode_atts(['key' => ''], $atts);
    
    // 入力検証
    if (empty($atts['key']) || !is_string($atts['key'])) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return '<!-- エラー: リンクキーが指定されていません -->';
        }
        return '';
    }
    
    // キー名のサニタイゼーション
    $key = sanitize_key($atts['key']);
    
    $url = ExternalLinksManager::get_url($key);
    
    // デバッグモードでのみエラーメッセージを表示
    if ($url === '' && defined('WP_DEBUG') && WP_DEBUG) {
        return '<!-- エラー: リンク "' . esc_html($key) . '" が見つかりません -->';
    }
    
    return $url;
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