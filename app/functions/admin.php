<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// ----------------------------------------------------- //
// 管理画面のカスタマイズ
// ----------------------------------------------------- //

// -----------------------------------------------------
// 管理画面アイコン読み込み
// -----------------------------------------------------
function enqueue_dashicons() {
	wp_enqueue_style( 'dashicons' );
}
add_action( 'admin_enqueue_scripts', 'enqueue_dashicons' );

// -----------------------------------------------------
// 管理画面の必要ない項目を非表示
// -----------------------------------------------------
function remove_menus() {
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' ); // WordPressニュース
	remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
	// remove_menu_page( 'index.php' ); // ダッシュボード.
	// remove_menu_page( 'edit.php' ); // 投稿.
	// remove_menu_page( 'upload.php' ); // メディア.
	// remove_menu_page( 'edit.php?post_type=page' ); // 固定.
	remove_menu_page( 'edit-comments.php' ); // コメント.
	// remove_menu_page( 'themes.php' ); // 外観.
	// remove_menu_page( 'plugins.php' ); // プラグイン.
	// remove_menu_page( 'users.php' ); // ユーザー.
	// remove_menu_page( 'tools.php' ); // ツール.
	// remove_menu_page( 'options-general.php' ); // 設定.
}
add_action( 'admin_menu', 'remove_menus', 999 );



// -----------------------------------------------------
// 管理画面のカスタム投稿にターム絞り込み機能追加
// -----------------------------------------------------

/**
 * 投稿一覧で絞り込み対象にするタクソノミーを返す
 *
 * register_taxonomy 済みの情報から導出するため、投稿タイプを追加しても設定不要。
 * 標準の category / post_tag は WP が標準で絞り込み UI を持つため除外する。
 *
 * @param string $post_type 投稿タイプ
 * @return \WP_Taxonomy[]  taxonomy_slug => WP_Taxonomy
 */
function baizy_get_admin_filter_taxonomies( string $post_type ): array {
	$taxonomies = get_object_taxonomies( $post_type, 'objects' );
	unset( $taxonomies['category'], $taxonomies['post_tag'] );
	return array_filter( $taxonomies, fn( $taxonomy ) => $taxonomy->show_ui );
}

function add_custom_taxonomies_term_filter() {
	global $post_type;

	foreach ( baizy_get_admin_filter_taxonomies( (string) $post_type ) as $taxonomy => $taxonomy_obj ) {
		$label = $taxonomy_obj->label . '一覧';

		// タクソノミーの全タームを取得
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		// タームが存在しない場合はスキップ
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			continue;
		}

		// 現在選択されているタームを取得
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected = isset( $_GET[ $taxonomy ] ) ? sanitize_text_field( wp_unslash( $_GET[ $taxonomy ] ) ) : '';

		// セレクトボックスを出力
		echo '<select name="' . esc_attr( $taxonomy ) . '" id="' . esc_attr( $taxonomy ) . '" style="margin-right: 10px;">';
		echo '<option value="">' . esc_html( $label ) . '</option>';

		foreach ( $terms as $term ) {
			printf(
				'<option value="%s"%s>%s (%d)</option>',
				esc_attr( $term->slug ),
				selected( $selected, $term->slug, false ),
				esc_html( $term->name ),
				(int) $term->count
			);
		}

		echo '</select>';
	}
}
add_action( 'restrict_manage_posts', 'add_custom_taxonomies_term_filter' );

// -----------------------------------------------------
// カスタムタクソノミーでの絞り込みクエリを処理
// -----------------------------------------------------
function filter_posts_by_custom_taxonomy( $query ) {
	global $pagenow;

	// 管理画面の投稿一覧ページのメインクエリでのみ実行
	if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$current_post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : 'post';

	$tax_queries = array();

	// 各タクソノミーについて絞り込み条件をチェック
	foreach ( baizy_get_admin_filter_taxonomies( $current_post_type ) as $taxonomy => $taxonomy_obj ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET[ $taxonomy ] ) && ! empty( $_GET[ $taxonomy ] ) ) {
			$tax_queries[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'terms'    => sanitize_text_field( wp_unslash( $_GET[ $taxonomy ] ) ),
			);
		}
	}

	// 絞り込み条件がある場合はクエリに追加
	if ( ! empty( $tax_queries ) ) {
		if ( count( $tax_queries ) > 1 ) {
			$tax_queries['relation'] = 'AND'; // 複数条件の場合はANDで結合
		}
		$query->query_vars['tax_query'] = $tax_queries;
	}
}
add_action( 'pre_get_posts', 'filter_posts_by_custom_taxonomy' );




// -----------------------------------------------------
// 管理画面にCSSを反映
// -----------------------------------------------------
add_action(
	'admin_init',
	function () {
		add_editor_style( 'resources/common/css/editor-style.css' );
	}
);



// -----------------------------------------------------
// アイキャッチ注意テキスト ※クラシックエディタのみ
// -----------------------------------------------------
// function add_featured_image_instruction( $content ) {
// return $content .= '<p>推奨サイズは幅：300px、高さ：200px</p>';
// }
// add_filter( 'admin_post_thumbnail_html', 'add_featured_image_instruction' );



// -----------------------------------------------------
// カスタムカラーパレットの設定
// baizy-color-palette プラグインへ移行済み
// （管理ページ・保存処理・theme.json へのマージはプラグイン側で行う）
// -----------------------------------------------------