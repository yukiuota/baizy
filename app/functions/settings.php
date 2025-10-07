<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =============================================================================
// テーマ基本設定
// =============================================================================

/**
 * WordPressコア機能のクリーンアップ
 */
// WordPressバージョン情報を削除
remove_action( 'wp_head', 'wp_generator' );

// 絵文字検出スクリプトとスタイルを削除
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );

// Windows Live Writerマニフェストを削除
remove_action( 'wp_head', 'wlwmanifest_link' );

// Really Simple Discoveryリンクを削除
remove_action( 'wp_head', 'rsd_link' );

// DNS プリフェッチを削除
remove_action( 'wp_head', 'wp_resource_hints', 2 );

// RSSフィードリンクを削除
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );

// テーマサポート設定
add_theme_support( 'automatic-feed-links' );

// 自動段落整形を無効化
remove_filter( 'the_content', 'wpautop' );
remove_filter( 'the_excerpt', 'wpautop' );

// 動的ドキュメントタイトルサポートを有効化
add_theme_support( 'title-tag' );

// アクセシビリティとHTML5テーマサポート
add_theme_support( 'post-thumbnails' );
add_theme_support( 'html5', array(
    'search-form',
    'comment-form',
    'comment-list',
    'gallery',
    'caption',
) );

// ブロックスタイルサポート
add_theme_support( 'wp-block-styles' );

// ナビゲーションメニューの登録
register_nav_menus( array(
    'primary' => __( 'Primary Menu', 'baizy' ),
    'footer'  => __( 'Footer Menu', 'baizy' ),
) );

// =============================================================================
// bodyクラスのカスタマイズ
// =============================================================================

/**
 * 投稿スラッグをbodyクラスに追加
 *
 * @param array $classes 既存のbodyクラス
 * @return array 変更されたbodyクラス
 */
function baizy_add_slug_to_body_class( $classes ) {
    global $post;
    
    if ( isset( $post ) ) {
        $classes[] = $post->post_name;
    }
    
    return $classes;
}
add_filter( 'body_class', 'baizy_add_slug_to_body_class' );


// サイトアイコン（favicon）サポートを有効化
add_theme_support( 'site-icon' );



// =============================================================================
// スクリプト・スタイル管理
// =============================================================================

/**
 * テーマスクリプト・スタイル管理クラス
 */
class baizy_Scripts_Styles {
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }
    
    /**
     * テーマスタイルの読み込み
     */
    public function enqueue_styles() {
        // メインテーマスタイルシート
        $main_css_path = get_template_directory() . '/public/common/css/common.css';
        if ( file_exists( $main_css_path ) ) {
            $main_css_version = filemtime( $main_css_path );
            wp_enqueue_style( 
                'baizy-main', 
                get_template_directory_uri() . '/public/common/css/common.css', 
                array(), 
                $main_css_version 
            );
        }
        
        // bodyクラス固有のスタイルシート
        $this->enqueue_body_class_styles();
    }
    
    /**
     * bodyクラス固有のスタイルの読み込み
     */
    private function enqueue_body_class_styles() {
        $body_classes = get_body_class();
        
        if ( empty( $body_classes ) ) {
            return;
        }
        
        foreach ( $body_classes as $class_name ) {
            $css_file_path = get_template_directory() . '/public/common/css/' . sanitize_file_name( $class_name ) . '.css';
            
            if ( file_exists( $css_file_path ) ) {
                $css_file_version = filemtime( $css_file_path );
                wp_enqueue_style( 
                    'baizy-body-class-' . sanitize_html_class( $class_name ), 
                    get_template_directory_uri() . '/public/common/css/' . sanitize_file_name( $class_name ) . '.css', 
                    array( 'baizy-main' ), 
                    $css_file_version 
                );
            }
        }
    }
    
    /**
     * テーマスクリプトの読み込み
     */
    public function enqueue_scripts() {
        // jQuery（WordPress標準）
        wp_enqueue_script( 'jquery' );
        
        // メインテーマスクリプト
        $main_js_path = get_template_directory() . '/public/common/js/script.js';
        if ( file_exists( $main_js_path ) ) {
            $main_js_version = filemtime( $main_js_path );
            wp_enqueue_script( 
                'baizy-main-script', 
                get_template_directory_uri() . '/public/common/js/script.js', 
                array( 'jquery' ), 
                $main_js_version, 
                true // フッターで読み込み
            );
        }
    }
}


// スクリプト・スタイル管理を初期化
new baizy_Scripts_Styles();

// =============================================================================
// スクリプト最適化（defer/async属性追加）
// =============================================================================

/**
 * スクリプトタグにdefer/async属性を追加
 * 
 * @param string $tag スクリプトタグ
 * @param string $handle スクリプトハンドル
 * @param string $src スクリプトソース
 * @return string 変更されたスクリプトタグ
 */
function baizy_add_script_attributes( $tag, $handle, $src ) {
    // defer属性を追加するスクリプトハンドル
    $defer_scripts = array(
        'baizy-main-script',        // メインテーマスクリプト（jQuery非依存）
        'custom-page-script',        // CF7フォーム（jQuery非依存、DOMContentLoaded使用）
    );
    
    // jQuery依存スクリプトはjQueryがロード後にdefer適用
    $jquery_dependent_defer_scripts = array(
        'custom-ajax-search-script', // Ajax検索（jQuery依存）
        'custom-ajax-script',        // Ajax more（jQuery依存）
        'ajax-pagination',           // Ajaxページネーション（jQuery依存）
    );
    
    // async属性を追加するスクリプトハンドル（アナリティクスなど独立したスクリプト）
    $async_scripts = array(
        // 'analytics-script', // 例：Google Analyticsなどの非同期スクリプト
    );
    
    // jQueryは通常通り読み込み、他のスクリプトはdefer/async適用
    if ( $handle !== 'jquery' && $handle !== 'jquery-core' && $handle !== 'jquery-migrate' ) {
        
        // defer属性を追加（jQuery非依存）
        if ( in_array( $handle, $defer_scripts ) ) {
            $tag = str_replace( ' src', ' defer src', $tag );
        }
        
        // jQuery依存スクリプトにもdefer適用（jQueryは先に読み込まれているため）
        if ( in_array( $handle, $jquery_dependent_defer_scripts ) ) {
            $tag = str_replace( ' src', ' defer src', $tag );
        }
        
        // async属性を追加
        if ( in_array( $handle, $async_scripts ) ) {
            $tag = str_replace( ' src', ' async src', $tag );
        }
    }
    
    return $tag;
}
add_filter( 'script_loader_tag', 'baizy_add_script_attributes', 10, 3 );