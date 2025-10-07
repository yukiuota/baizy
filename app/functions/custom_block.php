<?php
if ( !defined( 'ABSPATH' ) ) exit;

// =============================================================================
// ブロックパターン設定（patternsディレクトリ自動読み込み）
// =============================================================================

add_action( 'after_setup_theme', function() {
    // WordPressデフォルトのブロックパターンを無効化
    remove_theme_support( 'core-block-patterns' );
} );

/**
 * patternsディレクトリから全てのパターンファイルを読み込んで登録
 */
function baizy_register_block_patterns() {
    $patterns_dir = get_template_directory() . '/patterns/';
    
    // patternsディレクトリが存在するかチェック
    if ( ! is_dir( $patterns_dir ) ) {
        return;
    }
    
    // patternsディレクトリ内のPHPファイルを取得
    $pattern_files = glob( $patterns_dir . '*.php' );
    
    if ( empty( $pattern_files ) ) {
        return;
    }
    
    foreach ( $pattern_files as $pattern_file ) {
        // ファイル内容を取得
        $pattern_content = file_get_contents( $pattern_file );
        
        // ヘッダーコメントからメタデータを抽出
        $headers = array(
            'title'       => 'Title',
            'slug'        => 'Slug',
            'description' => 'Description',
            'categories'  => 'Categories',
            'keywords'    => 'Keywords',
            'block_types' => 'Block Types',
        );
        
        $metadata = array();
        foreach ( $headers as $key => $header ) {
            if ( preg_match( '/^\s*\*\s*' . $header . ':\s*(.+)$/m', $pattern_content, $matches ) ) {
                $value = trim( $matches[1] );
                if ( in_array( $key, array( 'categories', 'keywords', 'block_types' ) ) ) {
                    // カンマ区切りの値を配列に変換
                    $metadata[ $key ] = array_map( 'trim', explode( ',', $value ) );
                } else {
                    $metadata[ $key ] = $value;
                }
            }
        }
        
        // 必須フィールドをチェック
        if ( empty( $metadata['title'] ) || empty( $metadata['slug'] ) ) {
            continue;
        }
        
        // パターンのHTMLコンテンツを抽出（PHPコードを実行してHTMLを取得）
        ob_start();
        include $pattern_file;
        $pattern_html = ob_get_clean();
        
        // パターンを登録
        $pattern_args = array(
            'title'       => $metadata['title'],
            'content'     => $pattern_html,
            'description' => isset( $metadata['description'] ) ? $metadata['description'] : '',
            'categories'  => isset( $metadata['categories'] ) ? $metadata['categories'] : array(),
            'keywords'    => isset( $metadata['keywords'] ) ? $metadata['keywords'] : array(),
            'blockTypes'  => isset( $metadata['block_types'] ) ? $metadata['block_types'] : array(),
        );
        
        register_block_pattern( $metadata['slug'], $pattern_args );
        
        // カテゴリーも登録（存在しない場合）
        if ( ! empty( $metadata['categories'] ) ) {
            foreach ( $metadata['categories'] as $category ) {
                if ( ! WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( $category ) ) {
                    // カテゴリーラベルをカスタマイズ
                    $category_labels = array(
                        'baizy-hero' => 'ヒーローセクション',
                        'baizy-content' => 'コンテンツ',
                        'baizy-footer' => 'フッター',
                        'baizy-header' => 'ヘッダー',
                    );
                    
                    $label = isset( $category_labels[ $category ] ) 
                        ? $category_labels[ $category ] 
                        : ucwords( str_replace( '-', ' ', $category ) );
                    
                    register_block_pattern_category( $category, array( 'label' => $label ) );
                    
                    // デバッグ用ログ（開発時のみ）
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'Registered block pattern category: ' . $category );
                    }
                }
            }
        }
    }
}

// WordPressの初期化後にパターンを登録
add_action( 'init', 'baizy_register_block_patterns' );

// ----------------------------------------------------- //
// テーマ専用のカスタムブロック
// ----------------------------------------------------- //

function add_custom_block() {
    // ビルドされたブロックファイルを読み込む
    wp_enqueue_script(
        'custom_block_script',
        get_stylesheet_directory_uri() . '/app/blocks/build/custom-blocks.js',
        array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-block-editor' ),
        filemtime( get_stylesheet_directory() . '/app/blocks/build/custom-blocks.js' )
    );
}

add_action( 'enqueue_block_editor_assets', 'add_custom_block' );

// カスタムブロックカテゴリーを登録
function register_custom_block_category( $categories, $post ) {
    return array_merge(
        $categories,
        array(
            array(
                'slug' => 'theme-custom',
                'title' => 'テーマカスタム',
                'icon'  => 'admin-appearance', // WordPressのダッシュアイコンを使用
            ),
        )
    );
}

// WordPress 5.8以降用のフック
add_filter( 'block_categories_all', 'register_custom_block_category', 10, 2 );
// 古いバージョン互換性のためのフック
add_filter( 'block_categories', 'register_custom_block_category', 10, 2 );




// -----------------------------------------------------
// 各投稿のブロックの表示・非表示指定
// -----------------------------------------------------
function restrict_blocks_for_cases($allowed_blocks, $block_editor_context) {
    // 投稿タイプ
    if (!empty($block_editor_context->post) && $block_editor_context->post->post_type === 'news') {
        // 許可するブロックを指定（カスタムブロック + WordPress標準ブロック）
        return array(
            // カスタムブロック
            // 'my-blocks/◯◯',
            
            // WordPress標準ブロック - テキスト
            'core/paragraph',
            'core/heading',
            'core/list',
            'core/quote',
            'core/code',
            'core/preformatted',
            'core/pullquote',
            'core/table',
            'core/verse',
            
            // WordPress標準ブロック - メディア
            'core/image',
            'core/gallery',
            'core/audio',
            'core/video',
            'core/file',
        );
    }

    // 他の投稿タイプではすべてのブロックを許可
    return $allowed_blocks;
}
add_filter('allowed_block_types_all', 'restrict_blocks_for_cases', 10, 2);






// -----------------------------------------------------
// ブロックエディタの見出しHTMLをカスタマイズ
// -----------------------------------------------------
function custom_heading_html( $block_content, $block ) {
    // 見出しブロックの場合のみ処理
    if ( 'core/heading' !== $block['blockName'] ) {
        return $block_content;
    }
    
    // 例: 見出しに特定のクラスやデザイン要素を追加
    $modified_content = str_replace(
        array('<h1', '<h2', '<h3', '<h4', '<h5', '<h6'),
        array('<h1 class="post-heading01"', '<h2 class="post-heading02"', 
              '<h3 class="post-heading03"', '<h4 class="post-heading04"', 
              '<h5 class="post-heading05"', '<h6 class="post-heading06"'),
        $block_content
    );
    
    return $modified_content;
}
add_filter( 'render_block', 'custom_heading_html', 10, 2 );





// -----------------------------------------------------
// 管理画面のGutenberg上のh1~h6にだけクラスを追加
// -----------------------------------------------------
add_filter( 'render_block', function( $block_content, $block ) {
    // 管理画面でのみ実行（REST APIリクエストも含む）
    if ( (is_admin() || wp_doing_ajax() || defined('REST_REQUEST')) && $block['blockName'] === 'core/heading' ) {
        $block_content = preg_replace(
            '/<h([1-6])([^>]*)>/', 
            '<h$1 class="post-heading0$1"$2>', 
            $block_content
        );
    }
    return $block_content;
}, 10, 2 );


// Gutenbergエディター内でのブロック属性を変更
function modify_heading_block_attributes() {
    ?>
<script>
wp.domReady(function() {
    // 見出しブロックのレンダリング後にクラスを追加
    wp.data.subscribe(function() {
        var headings = document.querySelectorAll('.wp-block-heading:not(.class-added)');
        headings.forEach(function(heading) {
            var tagName = heading.tagName.toLowerCase();
            var level = tagName.charAt(1); // h1のh後の数字を取得
            if (level >= 1 && level <= 6) {
                heading.classList.add('post-heading0' + level);
                heading.classList.add('class-added'); // 重複処理を防ぐフラグ
            }
        });
    });
});
</script>
<?php
}

// 投稿タイプがnewsの編集画面でのみスクリプトを追加
function enqueue_heading_script() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'news' && $screen->base === 'post') {
        add_action('admin_footer', 'modify_heading_block_attributes');
    }
}
add_action('current_screen', 'enqueue_heading_script');