<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// SEO設定

// body上部タグ埋め込み
function baizy_include_body_top() {
	include get_template_directory() . '/resources/include/tags/body_top.php';
}
add_action( 'wp_body_open', 'baizy_include_body_top' );



// 投機的読み込み（Speculation Rules）はテーマ側（resources/include/tags/head_top.php）で出力する
// WP 6.8 以降はコアも同じ script を出力して二重になるため、コア側を止める
add_filter( 'wp_speculation_rules_configuration', '__return_null' );



// noindex設定（対象を増やす場合は is_singular( 'post_type' ) を条件に追加）
function baizy_single_noindex( array $robots ): array {
	if ( is_404() || is_category() || is_tag() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'baizy_single_noindex' );



// パンくずリスト関数

// パンくず項目を [ 'name', 'url' ] の配列で返す（url が空ならリンクなしで描画）
function baizy_breadcrumb_items(): array {
	$wp_obj = get_queried_object();
	$items  = array(
		array(
			'name' => 'TOP',
			'url'  => home_url(),
		),
	);

	// URL はスラッグから組み立てず WP の関数で取得する（rewrite 設定やパーマリンク構造が変わっても正しいリンクになる）
	// アーカイブを持たない投稿タイプは空文字（リンクなし）にする
	$archive_url = static function ( string $post_type ): string {
		$url = get_post_type_archive_link( $post_type );
		return $url ? $url : '';
	};

	if ( is_page() ) {
		// 固定ページ（page-○○.php）
		$items[] = array(
			'name' => single_post_title( '', false ),
			'url'  => get_permalink(),
		);
	} elseif ( is_tax() && $wp_obj instanceof WP_Term ) {
		// カスタム投稿 タクソノミー一覧ページ（0件時は get_post_type() が false のためタクソノミーから導出）
		// 投稿タイプアーカイブをタームで絞り込んだ URL（/news/?news-category=xx）もここで処理するため、is_post_type_archive() より先に判定する
		$post_slug = get_post_type();
		if ( ! $post_slug ) {
			$taxonomy_obj = get_taxonomy( $wp_obj->taxonomy );
			$post_slug    = ( $taxonomy_obj && ! empty( $taxonomy_obj->object_type[0] ) ) ? $taxonomy_obj->object_type[0] : '';
		}
		$post_type_obj = $post_slug ? get_post_type_object( $post_slug ) : null;

		if ( $post_type_obj ) {
			$items[] = array(
				'name' => $post_type_obj->label,
				'url'  => $archive_url( $post_slug ),
			);
		}
		$term_url = get_term_link( $wp_obj );
		$items[]  = array(
			'name' => '「' . $wp_obj->name . '」カテゴリー一覧',
			'url'  => is_wp_error( $term_url ) ? '' : $term_url,
		);
	} elseif ( is_post_type_archive() && $wp_obj instanceof WP_Post_Type ) {
		// カスタム投稿 TOPページ（archive-○○.php）
		$items[] = array(
			'name' => $wp_obj->label,
			'url'  => $archive_url( $wp_obj->name ),
		);
	} elseif ( is_singular() && $wp_obj instanceof WP_Post ) {
		// 投稿詳細ページ（通常の投稿ではアーカイブを挟まない）
		$post_slug     = get_post_type();
		$post_type_obj = $post_slug ? get_post_type_object( $post_slug ) : null;

		if ( $post_type_obj && 'post' !== $post_slug ) {
			$items[] = array(
				'name' => $post_type_obj->label,
				'url'  => $archive_url( $post_slug ),
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

function baizy_create_breadcrumb() {
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




// feed設定
function baizy_feed_request( $vars ) {
	if ( isset( $vars['feed'] ) && ! isset( $vars['post_type'] ) ) {
		$vars['post_type'] = array(
			'news',
		);
	}
	return $vars;
}
add_filter( 'request', 'baizy_feed_request' );




// カスタム投稿SEO設定

// カスタム投稿アーカイブのメタディスクリプション出力（register_post_type の description を使用）
function baizy_output_custom_post_meta_description() {
	if ( ! is_post_type_archive() ) {
		return;
	}
	$post_type_obj = get_queried_object();
	if ( $post_type_obj instanceof WP_Post_Type && '' !== $post_type_obj->description ) {
		echo '<meta name="description" content="' . esc_attr( $post_type_obj->description ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'baizy_output_custom_post_meta_description', 1 );
