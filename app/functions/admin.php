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
// -----------------------------------------------------

/**
 * カラーパレット管理ページを追加
 */
add_action(
	'admin_menu',
	function () {
		add_theme_page(
			'カラーパレット管理',
			'カラーパレット',
			'manage_options',
			'custom-color-palette',
			'render_custom_color_palette_page'
		);
	}
);

/**
 * カラーパレット管理画面のCSS・JSを読み込み
 */
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'appearance_page_custom-color-palette' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'color-palette-admin-css',
			get_template_directory_uri() . '/app/admin/css/color_palette_admin.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'color-palette-admin-js',
			get_template_directory_uri() . '/app/admin/js/color_palette_admin.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);
	}
);

/**
 * カラーパレット管理ページの表示
 */
function render_custom_color_palette_page() {
	// 権限チェック
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// カラーの保存処理
	if ( isset( $_POST['save_colors'] ) && check_admin_referer( 'save_custom_colors', 'custom_colors_nonce' ) ) {
		$colors = array();

		if ( isset( $_POST['color_name'] ) && is_array( $_POST['color_name'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$color_names = array_map( 'sanitize_text_field', array_map( 'wp_unslash', (array) $_POST['color_name'] ) );
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$color_codes = isset( $_POST['color_code'] ) ? array_map( 'wp_unslash', (array) $_POST['color_code'] ) : array();
			foreach ( $color_names as $index => $name ) {
				if ( ! empty( $name ) && ! empty( $color_codes[ $index ] ) ) {
					$colors[] = array(
						'name'  => $name,
						'color' => sanitize_hex_color( $color_codes[ $index ] ),
						'slug'  => sanitize_title( $name ),
					);
				}
			}
		}

		update_option( 'custom_color_palette', $colors );
		echo '<div class="notice notice-success is-dismissible"><p>カラーパレットを保存しました。</p></div>';
	}

	// 保存されているカラーを取得
	$saved_colors = get_option( 'custom_color_palette', array() );

	// ビューファイルを読み込み
	include get_template_directory() . '/app/admin/views/color_palette_settings.php';
}

/**
 * カラー行を表示
 */
function render_color_row( int $index, array $color ) {
	$name       = $color['name'] ?? '';
	$color_code = $color['color'] ?? '#000000';
	?>
<div class="color-row">
	<label>カラー名:</label>
	<input type="text" name="color_name[<?php echo absint( $index ); ?>]" value="<?php echo esc_attr( $name ); ?>" placeholder="例: プライマリー" required>
	<label>カラーコード:</label>
	<?php // 送信値はテキスト側の color_code のみ。カラーピッカーは JS でテキストと同期する（color_palette_admin.js） ?>
	<input type="color" value="<?php echo esc_attr( $color_code ); ?>" required>
	<input type="text" name="color_code[<?php echo absint( $index ); ?>]" value="<?php echo esc_attr( $color_code ); ?>" class="color-code-text" pattern="^#[0-9A-Fa-f]{6}$" required>
	<span class="dashicons dashicons-trash remove-color-btn"></span>
</div>
	<?php
}

/**
 * テーマのカラーパレットにカスタムカラーを追加する
 * Core のデフォルトカラーパレットを非表示にする
 *
 * @param object $theme_json テーマJSONオブジェクト.
 * @return object 更新されたテーマJSONオブジェクト.
 */
add_filter(
	'wp_theme_json_data_theme',
	function ( $theme_json ) {
		$saved_colors = get_option( 'custom_color_palette', array() );
		if ( empty( $saved_colors ) || ! is_array( $saved_colors ) ) {
			return $theme_json;
		}

		$get_data          = $theme_json->get_data();
		$theme_palette     = $get_data['settings']['color']['palette']['theme'] ?? array();
		$add_color_palette = array();

		foreach ( $saved_colors as $color ) {
			if ( empty( $color['slug'] ) || empty( $color['color'] ) || empty( $color['name'] ) ) {
				continue;
			}
			$add_color_palette[] = array(
				'slug'  => $color['slug'],
				'color' => $color['color'],
				'name'  => $color['name'],
			);
		}

		$new_color_palette = array_merge(
			$theme_palette,
			$add_color_palette
		);

		$new_data = array(
			'version'  => 2,
			'settings' => array(
				'color' => array(
					'palette'        => $new_color_palette,
					'defaultPalette' => false,
				),
			),
		);

		return $theme_json->update_with( $new_data );
	}
);