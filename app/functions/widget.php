<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// ウィジェットエリアの登録
function register_theme_widget_areas(): void {
	register_sidebar(
		array(
			'name'          => __( 'ヘッダーメニュー', 'baizy' ),
			'id'            => 'header-menu',
			'description'   => __( 'ヘッダーのメニューウィジェットエリア', 'baizy' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'フッター（左）', 'baizy' ),
			'id'            => 'footer-1',
			'description'   => __( 'フッター左側のウィジェットエリア', 'baizy' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="widget-title">',
			'after_title'   => '</h4>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'フッター（中央）', 'baizy' ),
			'id'            => 'footer-2',
			'description'   => __( 'フッター中央のウィジェットエリア', 'baizy' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="widget-title">',
			'after_title'   => '</h4>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'フッター（右）', 'baizy' ),
			'id'            => 'footer-3',
			'description'   => __( 'フッター右側のウィジェットエリア', 'baizy' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="widget-title">',
			'after_title'   => '</h4>',
		)
	);
}
add_action( 'widgets_init', 'register_theme_widget_areas' );

// ナビゲーションメニューの登録
function register_theme_menus(): void {
	register_nav_menus(
		array(
			'header-menu' => __( 'ヘッダーメニュー', 'baizy' ),
			'footer-menu' => __( 'フッターメニュー', 'baizy' ),
		)
	);
}
add_action( 'init', 'register_theme_menus' );
