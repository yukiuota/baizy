<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }


// =============================================================================
// テーマ基本設定
// =============================================================================

/**
 * テーマ基本設定管理クラス
 */
class Baizy_Theme_Setup {
    /** @var string テキストドメイン */
    private $text_domain = 'baizy';
    /** @var string 言語ファイルパス */
    private $languages_path = BAIZY_THEME_PATH . '/app/languages';

    /**
     * コンストラクタ
     */
    public function __construct() {
        add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );
        add_filter( 'body_class', array( $this, 'add_slug_to_body_class' ) );
        add_action( 'template_redirect', array( $this, 'disable_author_archive' ) );
    }
    
    /**
     * テーマのセットアップ
     */
    public function setup_theme() {
        // 国際化対応
        load_theme_textdomain( $this->text_domain, $this->languages_path );

        // WordPressコア機能のクリーンアップ
        $this->cleanup_wp_head();
        
        // テーマサポート設定
        $this->add_theme_supports();
        
        // ナビゲーションメニューの登録
        $this->register_nav_menus();
        
        // 自動段落整形を無効化
        $this->disable_auto_paragraph();
    }
    
    /**
     * WordPressコア機能のクリーンアップ
     */
    private function cleanup_wp_head() {
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
    }
    
    /**
     * テーマサポート設定
     */
    private function add_theme_supports() {
        // 自動フィードリンク
        add_theme_support( 'automatic-feed-links' );
        
        // 動的ドキュメントタイトルサポート
        add_theme_support( 'title-tag' );
        
        // アイキャッチ画像サポート
        add_theme_support( 'post-thumbnails' );
        
        // HTML5サポート
        add_theme_support( 'html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
        ) );
        
        // ブロックスタイルサポート
        add_theme_support( 'wp-block-styles' );
        
        // サイトアイコン（favicon）サポート
        add_theme_support( 'site-icon' );

        // ブロックエディタ関連
        add_theme_support( 'responsive-embeds' );
        add_theme_support( 'editor-styles' );
        add_theme_support( 'block-patterns' );
    }
    
    /**
     * 自動段落整形を無効化
     */
    private function disable_auto_paragraph() {
        remove_filter( 'the_content', 'wpautop' );
        remove_filter( 'the_excerpt', 'wpautop' );
    }
    
    /**
     * 投稿スラッグをbodyクラスに追加
     *
     * @param array $classes 既存のbodyクラス
     * @return array 変更されたbodyクラス
     */
    public function add_slug_to_body_class( $classes ) {
        global $post;

        if ( isset( $post ) ) {
            $classes[] = $post->post_name;
        }

        return $classes;
    }

    /**
     * 著者アーカイブページを無効化
     * セキュリティ対策：ユーザー名の露出を防ぐ
     */
    public function disable_author_archive() {
        if ( is_author() ) {
            wp_safe_redirect( home_url( '/404' ), 301 );
            exit;
        }
    }
}

// =============================================================================
// スクリプト・スタイル管理
// =============================================================================

/**
 * テーマスクリプト・スタイル管理クラス
 */
class Baizy_Scripts_Styles {
    /** @var array defer適用スクリプト */
    private $defer_scripts = array(
        'baizy-main-script',        // メインテーマスクリプト（jQuery非依存）
        'custom-page-script',       // CF7フォーム（jQuery非依存、DOMContentLoaded使用）
    );

    /** @var array jQuery依存のdefer適用スクリプト */
    private $jquery_dependent_defer_scripts = array(
        'custom-ajax-search-script', // Ajax検索（jQuery依存）
        'custom-ajax-script',        // Ajax more（jQuery依存）
        'ajax-pagination',           // Ajaxページネーション（jQuery依存）
    );

    /** @var array async適用スクリプト */
    private $async_scripts = array(
        // 'analytics-script', // 例:Google Analyticsなどの非同期スクリプト
    );

    /** @var array jQuery関連スクリプト（属性を追加しない） */
    private $jquery_scripts = array( 'jquery', 'jquery-core', 'jquery-migrate' );

    /**
     * コンストラクタ
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_filter( 'script_loader_tag', array( $this, 'add_script_attributes' ), 10, 3 );
    }
    
    /**
     * ファイルのバージョン番号を取得
     *
     * @param string $file_path ファイルパス
     * @return string|null バージョン番号（ファイルが存在しない場合はnull）
     */
    private function get_file_version( $file_path ) {
        return file_exists( $file_path ) ? filemtime( $file_path ) : null;
    }

    /**
     * テーマスタイルの読み込み
     */
    public function enqueue_styles() {
        // メインテーマスタイルシート
        $main_css_path = get_template_directory() . '/public/common/css/common.css';
        $version = $this->get_file_version( $main_css_path );

        if ( $version ) {
            wp_enqueue_style(
                'baizy-main',
                BAIZY_THEME_URI . '/public/common/css/common.css',
                array(),
                $version
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
            $sanitized_filename = sanitize_file_name( $class_name );
            $css_file_path = get_template_directory() . '/public/common/css/' . $sanitized_filename . '.css';
            $version = $this->get_file_version( $css_file_path );

            if ( $version ) {
                wp_enqueue_style(
                    'baizy-body-class-' . sanitize_html_class( $class_name ),
                    BAIZY_THEME_URI . '/public/common/css/' . $sanitized_filename . '.css',
                    array( 'baizy-main' ),
                    $version
                );
            }
        }
    }
    
    /**
     * テーマスクリプトの読み込み
     */
    public function enqueue_scripts() {
        // jQuery（WordPress標準）
        // メインテーマスクリプト
        $main_js_path = get_template_directory() . '/public/common/js/script.js';
        $version = $this->get_file_version( $main_js_path );

        if ( $version ) {
            wp_enqueue_script(
                'baizy-main-script',
                BAIZY_THEME_URI . '/public/common/js/script.js',
                array( 'jquery' ),
                $version,
                true // フッターで読み込み
            );
        }
    }
    
    /**
     * スクリプトタグにdefer/async属性を追加
     *
     * @param string $tag スクリプトタグ
     * @param string $handle スクリプトハンドル
     * @param string $src スクリプトソース
     * @return string 変更されたスクリプトタグ
     */
    public function add_script_attributes( $tag, $handle, $src ) {
        // jQueryは通常通り読み込み
        if ( in_array( $handle, $this->jquery_scripts, true ) ) {
            return $tag;
        }

        // defer属性を追加
        if ( in_array( $handle, $this->defer_scripts, true ) ||
             in_array( $handle, $this->jquery_dependent_defer_scripts, true ) ) {
            return str_replace( ' src', ' defer src', $tag );
        }

        // async属性を追加
        if ( in_array( $handle, $this->async_scripts, true ) ) {
            return str_replace( ' src', ' async src', $tag );
        }

        return $tag;
    }
}

// =============================================================================
// カスタマイザー設定
// =============================================================================

/**
 * テーマカスタマイザー設定クラス
 */
class Baizy_Customizer {
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        add_action( 'customize_register', array( $this, 'register_customizer' ) );
    }
    
    /**
     * カスタマイザーに設定を登録
     *
     * @param WP_Customize_Manager $wp_customize カスタマイザーオブジェクト
     */
    public function register_customizer( $wp_customize ) {
        // タグ追加セクション
        $wp_customize->add_section( 'baizy_custom_tags', array(
            'title'       => __( 'タグ追加', 'baizy' ),
            'priority'    => 30,
            'description' => __( 'headタグやbodyタグにカスタムコードを追加します。', 'baizy' ),
        ) );
        
        // head上部タグ設定
        $wp_customize->add_setting( 'baizy_head_top_code', array(
            'default'           => '',
            'sanitize_callback' => array( $this, 'sanitize_code' ),
            'transport'         => 'refresh',
        ) );
        
        $wp_customize->add_control( 'baizy_head_top_code', array(
            'label'       => __( 'head上部', 'baizy' ),
            'description' => __( '<head>タグの直後に追加されるコードです。Google Analytics、メタタグなどを追加できます。', 'baizy' ),
            'section'     => 'baizy_custom_tags',
            'type'        => 'textarea',
            'input_attrs' => array(
                'placeholder' => __( '例: <meta name="description" content="サイトの説明">', 'baizy' ),
                'rows'        => 10,
            ),
        ) );
        
        // body上部タグ設定
        $wp_customize->add_setting( 'baizy_body_top_code', array(
            'default'           => '',
            'sanitize_callback' => array( $this, 'sanitize_code' ),
            'transport'         => 'refresh',
        ) );
        
        $wp_customize->add_control( 'baizy_body_top_code', array(
            'label'       => __( 'body上部', 'baizy' ),
            'description' => __( '<body>タグの直後に追加されるコードです。Google Tag Manager、トラッキングコードなどを追加できます。', 'baizy' ),
            'section'     => 'baizy_custom_tags',
            'type'        => 'textarea',
            'input_attrs' => array(
                'placeholder' => __( '例: <!-- Google Tag Manager -->', 'baizy' ),
                'rows'        => 10,
            ),
        ) );
    }
    
    /**
     * コードのサニタイズ
     * スクリプトやスタイルを許可
     *
     * @param string $input 入力値
     * @return string サニタイズされた値
     */
    public function sanitize_code( $input ) {
        // 空の場合はそのまま返す
        if ( empty( $input ) ) {
            return '';
        }
        
        // 許可するHTMLタグとその属性を定義
        $allowed_html = wp_kses_allowed_html( 'post' );
        
        // スクリプトタグを許可
        $allowed_html['script'] = array(
            'type'        => true,
            'src'         => true,
            'async'       => true,
            'defer'       => true,
            'crossorigin' => true,
            'integrity'   => true,
        );
        
        // スタイルタグを許可
        $allowed_html['style'] = array(
            'type'  => true,
            'media' => true,
        );
        
        // メタタグを許可
        $allowed_html['meta'] = array(
            'name'     => true,
            'content'  => true,
            'property' => true,
            'charset'  => true,
        );
        
        // リンクタグを許可
        $allowed_html['link'] = array(
            'rel'         => true,
            'href'        => true,
            'type'        => true,
            'media'       => true,
            'sizes'       => true,
            'crossorigin' => true,
        );
        
        // noscriptタグを許可
        $allowed_html['noscript'] = array();
        $allowed_html['iframe'] = array(
            'src'             => true,
            'height'          => true,
            'width'           => true,
            'frameborder'     => true,
            'style'           => true,
            'allowfullscreen' => true,
        );
        
        return wp_kses( $input, $allowed_html );
    }
}

// =============================================================================
// 初期化
// =============================================================================

// テーマ基本設定を初期化
new Baizy_Theme_Setup();

// スクリプト・スタイル管理を初期化
new Baizy_Scripts_Styles();

// カスタマイザー設定を初期化
new Baizy_Customizer();