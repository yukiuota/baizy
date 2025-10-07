<?php if ( !defined( 'ABSPATH' ) ) exit;

/**
 * カスタム検索フォームを表示する関数
 *
 * @param array $args 検索フォームの設定オプション
 * @return void
 */
function custom_search_form($args = []) {
    // デフォルト設定
    $defaults = [
        'placeholder' => 'キーワードを入力',
        'button_text' => '検索',
        'form_class' => 'custom-search-form',
    ];
    
    // ユーザー設定とデフォルト設定をマージ
    $settings = wp_parse_args($args, $defaults);
    
    // 検索フォームのHTML
    ?>
    <form role="search" method="get" class="<?php echo esc_attr($settings['form_class']); ?>" action="<?php echo esc_url(home_url('/')); ?>">
        <input type="search" class="search-field" placeholder="<?php echo esc_attr($settings['placeholder']); ?>" value="<?php echo get_search_query(); ?>" name="s" />
        <button type="submit" class="search-submit"><?php echo esc_html($settings['button_text']); ?></button>
    </form>
    <?php
}

/**
 * 検索クエリをカスタマイズして全文検索の精度を向上させる
 */
function custom_search_query($query) {
    // メインクエリで検索ページの場合のみ処理
    if ($query->is_search() && $query->is_main_query()) {
        // 投稿タイプの指定（必要に応じてカスタマイズ）
        $query->set('post_type', ['post', 'page']);
        
        // 検索対象のフィールドを増やす
        global $wpdb;
        $search_term = $query->get('s');
        
        if (!empty($search_term)) {
            // カスタムフィールドも検索対象に含める
            $meta_query = [
                'relation' => 'OR',
                [
                    'key' => '_custom_field1', // 検索したいカスタムフィールド
                    'value' => $search_term,
                    'compare' => 'LIKE',
                ],
                // 必要に応じて他のカスタムフィールドも追加
            ];
            
            $query->set('meta_query', $meta_query);
        }
    }
    
    return $query;
}
add_filter('pre_get_posts', 'custom_search_query');

/**
 * 検索フォームをショートコードで使えるようにする
 */
function custom_search_shortcode($atts) {
    $atts = shortcode_atts([
        'placeholder' => 'サイト内を検索',
        'button_text' => '検索',
        'form_class' => 'shortcode-search-form',
    ], $atts, 'custom_search');
    
    ob_start();
    custom_search_form($atts);
    return ob_get_clean();
}
add_shortcode('custom_search', 'custom_search_shortcode');