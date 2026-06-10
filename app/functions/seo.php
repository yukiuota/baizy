<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// ----------------------------------------------------- //
// SEO設定
// ----------------------------------------------------- //

// -----------------------------------------------------
// body上部タグ埋め込み
// -----------------------------------------------------
function include_body_top() {
	include get_template_directory() . '/resources/include/tags/body_top.php';
}
add_action( 'wp_body_open', 'include_body_top' );



// -----------------------------------------------------
// noindex設定
// -----------------------------------------------------
function single_noindex() {
	if ( is_404() || is_singular( 'xxx' ) || is_category() || is_tag() ) {
		echo '<meta name="robots" content="noindex , nofollow" />';
	}
}
add_action( 'wp_head', 'single_noindex' );



// -----------------------------------------------------
// パンくずリスト関数
// -----------------------------------------------------
function create_breadcrumb() {

	// wpオブジェクト取得
	$wp_obj = get_queried_object();

	// パンくずのどのページでも変わらない部分を出力
	echo '<div class="p-breadcrumb">' .
	'<ul class="p-breadcrumb__lists" itemscope itemtype="https://schema.org/BreadcrumbList">' .
	'<li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem" class="p-breadcrumb__item">' .
	'<a itemprop="item" href="' . esc_url( home_url() ) . '">' .
	'<span itemprop="name">TOP</span>' .
	'</a>' .
	'<meta itemprop="position" content="1">' .
	'</li>';

	// 固定ページ（page-○○.php）
	if ( is_page() ) {
		echo '<li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem" class="p-breadcrumb__item">' .
		'<a itemprop="item" href="' . esc_url( get_permalink() ) . '">' .
		'<span itemprop="name">' . esc_html( single_post_title( '', false ) ) . '</span>' .
		'</a>' .
		'<meta itemprop="position" content="2">' .
		'</li>';
	}

	// カスタム投稿 TOPページ（archive-○○.php）
	if ( is_post_type_archive() ) {
		echo '<li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem" class="p-breadcrumb__item">' .
		'<a itemprop="item" href="' . esc_url( home_url( $wp_obj->name ) ) . '">' .
		'<span itemprop="name">' . esc_html( $wp_obj->label ) . '</span>' .
		'</a>' .
		'<meta itemprop="position" content="2">' .
		'</li>';
	}

	// カスタム投稿 タクソノミー一覧ページ（taxonomy-○○.php）
	if ( is_tax() ) {
		// 投稿が0件のタームでは get_post_type() が false を返すため、
		// タクソノミーに紐づく投稿タイプから取得する
		$post_slug = get_post_type();
		if ( ! $post_slug ) {
			$taxonomy_obj = get_taxonomy( $wp_obj->taxonomy );
			$post_slug    = ( $taxonomy_obj && ! empty( $taxonomy_obj->object_type[0] ) ) ? $taxonomy_obj->object_type[0] : '';
		}
		$post_type_obj = $post_slug ? get_post_type_object( $post_slug ) : null;
		$term_position = 2;

		if ( $post_type_obj ) {
			echo '<li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem" class="p-breadcrumb__item">' .
			'<a itemprop="item" href="' . esc_url( home_url( $post_slug ) ) . '">' .
			'<span itemprop="name">' . esc_html( $post_type_obj->label ) . '</span>' .
			'</a>' .
			'<meta itemprop="position" content="2">' .
			'</li>';
			$term_position = 3;
		}

		echo '<li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem" class="p-breadcrumb__item">' .
		'<a itemprop="item" href="' . esc_url( home_url( $post_slug . '/' . $wp_obj->slug ) ) . '">' .
		'<span itemprop="name">「' . esc_html( $wp_obj->name ) . '」カテゴリー一覧</span>' .
		'</a>' .
		'<meta itemprop="position" content="' . esc_attr( (string) $term_position ) . '">' .
		'</li>';
	}

	// カスタム投稿 詳細ページ（single-○○.php）
	if ( is_singular() && ! is_page() ) {
		$post_slug     = get_post_type();
		$post_type_obj = $post_slug ? get_post_type_object( $post_slug ) : null;
		$post_label    = $post_type_obj ? $post_type_obj->label : '';
		$post_id       = $wp_obj->ID;
		$post_title    = $wp_obj->post_title;

		// 通常の投稿（post）の場合はアーカイブページを表示しない
		if ( 'post' !== $post_slug && $post_type_obj ) {
			echo '<li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem" class="p-breadcrumb__item">' .
			'<a itemprop="item" href="' . esc_url( home_url( $post_slug ) ) . '">' .
			'<span itemprop="name">' . esc_html( $post_label ) . '</span>' .
			'</a>' .
			'<meta itemprop="position" content="2">' .
			'</li>';
		}

		// 投稿詳細ページ（アーカイブ項目を出力していない場合は position を詰める）
		$position = ( 'post' === $post_slug || ! $post_type_obj ) ? '2' : '3';
		echo '<li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem" class="p-breadcrumb__item">' .
		'<a itemprop="item" href="' . esc_url( get_permalink( $post_id ) ) . '">' .
		'<span itemprop="name">' . esc_html( $post_title ) . '</span>' .
		'</a>' .
		'<meta itemprop="position" content="' . esc_attr( $position ) . '">' .
		'</li>';
	}

	// 404（404.php）
	if ( is_404() ) {
		echo '<li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem" class="p-breadcrumb__item">' .
		'<span itemprop="name">404 Not Found</span>' .
		'<meta itemprop="position" content="2">' .
		'</li>';
	}

	echo '</ul>' .
	'</div>';
}




// -----------------------------------------------------
// feed設定
// -----------------------------------------------------
function mysite_feed_request( $vars ) {
	if ( isset( $vars['feed'] ) && ! isset( $vars['post_type'] ) ) {
		$vars['post_type'] = array(
			'news',
		);
	}
	return $vars;
}
add_filter( 'request', 'mysite_feed_request' );




// -----------------------------------------------------
// カスタム投稿SEO設定
// -----------------------------------------------------

// メタディスクリプション定数
define( 'NEWS_ARCHIVE_META_DESCRIPTION', 'これはカスタム投稿タイプ「news」のアーカイブページです。' );

/**
 * カスタム投稿タイプにメタディスクリプションを設定
 */
function set_custom_post_type_meta_description( $post_type_name, $description ) {
	global $wp_post_types;

	if ( isset( $wp_post_types[ $post_type_name ] ) ) {
		$wp_post_types[ $post_type_name ]->description = $description;
	}
}

/**
 * カスタム投稿アーカイブページのメタディスクリプション出力
 */
function output_custom_post_meta_description() {
	if ( is_post_type_archive( 'news' ) ) {
		echo '<meta name="description" content="' . esc_attr( NEWS_ARCHIVE_META_DESCRIPTION ) . '" />' . "\n";
	}
}

// カスタム投稿タイプ登録後にメタディスクリプションを設定
add_action(
	'init',
	function () {
		set_custom_post_type_meta_description( 'news', NEWS_ARCHIVE_META_DESCRIPTION );
	},
	20
);

// メタディスクリプションを出力
add_action( 'wp_head', 'output_custom_post_meta_description', 1 );
