<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// クラス実装は app/widgets/custom_html_widget.php を参照

// ウィジェットエリアの登録
function register_theme_widget_areas(): void {
    register_sidebar( [ 'name' => __( 'ヘッダーメニュー', 'textdomain' ), 'id' => 'header-menu',
        'description' => __( 'ヘッダーのメニューウィジェットエリア', 'textdomain' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">', 'after_widget' => '</div>',
        'before_title' => '<h3 class="widget-title">', 'after_title' => '</h3>' ] );

    register_sidebar( [ 'name' => __( 'フッター（左）', 'textdomain' ), 'id' => 'footer-1',
        'description' => __( 'フッター左側のウィジェットエリア', 'textdomain' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">', 'after_widget' => '</div>',
        'before_title' => '<h4 class="widget-title">', 'after_title' => '</h4>' ] );

    register_sidebar( [ 'name' => __( 'フッター（中央）', 'textdomain' ), 'id' => 'footer-2',
        'description' => __( 'フッター中央のウィジェットエリア', 'textdomain' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">', 'after_widget' => '</div>',
        'before_title' => '<h4 class="widget-title">', 'after_title' => '</h4>' ] );

    register_sidebar( [ 'name' => __( 'フッター（右）', 'textdomain' ), 'id' => 'footer-3',
        'description' => __( 'フッター右側のウィジェットエリア', 'textdomain' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">', 'after_widget' => '</div>',
        'before_title' => '<h4 class="widget-title">', 'after_title' => '</h4>' ] );
}
add_action( 'widgets_init', 'register_theme_widget_areas' );

// ナビゲーションメニューの登録
function register_theme_menus(): void {
    register_nav_menus( [
        'header-menu' => __( 'ヘッダーメニュー', 'textdomain' ),
        'footer-menu' => __( 'フッターメニュー', 'textdomain' ),
    ] );
}
add_action( 'init', 'register_theme_menus' );

// カスタムHTMLウィジェットの登録
add_action( 'widgets_init', fn() => register_widget( \Baizy\Widgets\CustomHtmlWidget::class ) );

// 管理画面スタイル
add_action( 'admin_head-widgets.php', function(): void {
    echo '<style>
.custom-html-widget-form .html-content-textarea { font-family: "Courier New", Courier, monospace; font-size: 12px; }
.html-preview-container { margin-top: 15px; }
.html-preview { border-radius: 3px; overflow: auto; }
.html-preview * { max-width: 100%; }
</style>';
} );
