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
// noindex設定（wp_robots API・WP 5.7+）
// 特定のカスタム投稿詳細も対象にする場合は is_singular( 'post_type' ) を条件に追加する
// -----------------------------------------------------
function single_noindex( array $robots ): array {
	if ( is_404() || is_category() || is_tag() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'single_noindex' );



// -----------------------------------------------------
// パンくずリスト関数
// -----------------------------------------------------

/**
 * 現在のページのパンくず項目を [ 'name' => 表示名, 'url' => リンク先 ] の配列で返す
 * url が空の項目はリンクなしで描画される
 */
function baizy_breadcrumb_items(): array {
	$wp_obj = get_queried_object();
	$items  = array(
		array(
			'name' => 'TOP',
			'url'  => home_url(),
		),
	);

	if ( is_page() ) {
		// 固定ページ（page-○○.php）
		$items[] = array(
			'name' => single_post_title( '', false ),
			'url'  => get_permalink(),
		);
	} elseif ( is_post_type_archive() ) {
		// カスタム投稿 TOPページ（archive-○○.php）
		$items[] = array(
			'name' => $wp_obj->label,
			'url'  => home_url( $wp_obj->name ),
		);
	} elseif ( is_tax() ) {
		// カスタム投稿 タクソノミー一覧ページ（taxonomy-○○.php）
		// 投稿が0件のタームでは get_post_type() が false を返すため、
		// タクソノミーに紐づく投稿タイプから取得する
		$post_slug = get_post_type();
		if ( ! $post_slug ) {
			$taxonomy_obj = get_taxonomy( $wp_obj->taxonomy );
			$post_slug    = ( $taxonomy_obj && ! empty( $taxonomy_obj->object_type[0] ) ) ? $taxonomy_obj->object_type[0] : '';
		}
		$post_type_obj = $post_slug ? get_post_type_object( $post_slug ) : null;

		if ( $post_type_obj ) {
			$items[] = array(
				'name' => $post_type_obj->label,
				'url'  => home_url( $post_slug ),
			);
		}
		$items[] = array(
			'name' => '「' . $wp_obj->name . '」カテゴリー一覧',
			'url'  => home_url( $post_slug . '/' . $wp_obj->slug ),
		);
	} elseif ( is_singular() ) {
		// 投稿詳細ページ（single-○○.php）
		// 通常の投稿（post）の場合はアーカイブページを表示しない
		$post_slug     = get_post_type();
		$post_type_obj = $post_slug ? get_post_type_object( $post_slug ) : null;

		if ( $post_type_obj && 'post' !== $post_slug ) {
			$items[] = array(
				'name' => $post_type_obj->label,
				'url'  => home_url( $post_slug ),
			);
		}
		$items[] = array(
			'name' => $wp_obj->post_title,
			'url'  => get_permalink( $wp_obj->ID ),
		);
	} elseif ( is_404() ) {
		$items[] = array(
			'name' => '404 Not Found',
			'url'  => '',
		);
	}

	return $items;
}

function create_breadcrumb() {
	echo '<div class="p-breadcrumb">' .
	'<ul class="p-breadcrumb__lists" itemscope itemtype="https://schema.org/BreadcrumbList">';

	foreach ( baizy_breadcrumb_items() as $i => $item ) {
		$name = '<span itemprop="name">' . esc_html( $item['name'] ) . '</span>';
		echo '<li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem" class="p-breadcrumb__item">' .
		( $item['url'] ? '<a itemprop="item" href="' . esc_url( $item['url'] ) . '">' . $name . '</a>' : $name ) . // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'<meta itemprop="position" content="' . esc_attr( (string) ( $i + 1 ) ) . '">' .
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

/**
 * カスタム投稿アーカイブページのメタディスクリプション出力
 *
 * register_post_type の 'description' 引数に設定した文言をそのまま使う
 */
function output_custom_post_meta_description() {
	if ( ! is_post_type_archive() ) {
		return;
	}
	$post_type_obj = get_queried_object();
	if ( $post_type_obj instanceof WP_Post_Type && '' !== $post_type_obj->description ) {
		echo '<meta name="description" content="' . esc_attr( $post_type_obj->description ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'output_custom_post_meta_description', 1 );
