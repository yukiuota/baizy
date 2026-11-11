<?php
/**
 * ACF / SCF のフィールド定義を /data/field-groups/ へJSON同期する
 *
 * @package baizy
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// ACF JSON の保存先を指定（ACF標準の同期機能）
function baizy_acf_json_save_point( $_path ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	$custom_path = BAIZY_THEME_PATH . '/data/field-groups';

	// ディレクトリが存在しない場合は作成
	if ( ! file_exists( $custom_path ) ) {
		wp_mkdir_p( $custom_path );
	}

	return $custom_path;
}
add_filter( 'acf/settings/save_json', 'baizy_acf_json_save_point' );

// ACF JSON の読み込み先を指定（ACF標準の同期機能）
function baizy_acf_json_load_point( $paths ) {
	// デフォルトパスを削除
	unset( $paths[0] );

	// カスタムパスを追加
	$paths[] = BAIZY_THEME_PATH . '/data/field-groups';

	return $paths;
}
add_filter( 'acf/settings/load_json', 'baizy_acf_json_load_point' );

/**
 * WP_DEBUG 時のみログを出力する
 *
 * @param string $message ログメッセージ
 */
function baizy_scf_log( string $message ): void {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'SCF JSON export: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}

/**
 * SCF の設定（smart-custom-fields 投稿）をエクスポート用配列に変換
 *
 * 表示条件は SCF の設定クラスにゲッターがないため、SCF が保存している投稿メタから直接読む。
 *
 * @param \WP_Post $settings_post SCF の設定を持つ投稿
 * @return array
 */
function baizy_scf_setting_to_array( \WP_Post $settings_post ): array {
	// 表示条件のメタは未設定だと空文字で返るため、配列にそろえる
	$condition = static function ( string $key ) use ( $settings_post ): array {
		$value = get_post_meta( $settings_post->ID, 'smart-cf-' . $key, true );
		return is_array( $value ) ? array_values( $value ) : array();
	};

	$export_data = array(
		'id'            => $settings_post->ID,
		'title'         => $settings_post->post_title,
		'menu_order'    => $settings_post->menu_order,
		'post_types'    => $condition( 'condition' ),
		'roles'         => $condition( 'roles' ),
		'taxonomies'    => $condition( 'taxonomies' ),
		'options_pages' => $condition( 'options-pages' ),
		'groups'        => array(),
	);

	$setting = new \Smart_Custom_Fields_Setting( $settings_post->ID, $settings_post->post_title );

	foreach ( $setting->get_groups() as $group ) {
		$group_data = array(
			'name'   => $group->get_name(),
			'repeat' => $group->is_repeatable(),
			'fields' => array(),
		);

		foreach ( $group->get_fields() as $field ) {
			$group_data['fields'][] = array(
				'name'        => $field->get( 'name' ),
				'label'       => $field->get( 'label' ),
				'type'        => $field->get_attribute( 'type' ),
				'choices'     => $field->get( 'choices' ),
				'default'     => $field->get( 'default' ),
				'instruction' => $field->get( 'instruction' ),
				'notes'       => $field->get( 'notes' ),
			);
		}

		$export_data['groups'][] = $group_data;
	}

	return $export_data;
}

/**
 * ファイルへ書き込む（WP_Filesystem 優先・file_put_contents フォールバック）
 *
 * @param string $file_path 書き込み先の絶対パス
 * @param string $contents  書き込む内容
 * @return bool 成功したか
 */
function baizy_scf_write_file( string $file_path, string $contents ): bool {
	global $wp_filesystem;

	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	if ( WP_Filesystem() ) {
		return (bool) $wp_filesystem->put_contents( $file_path, $contents, FS_CHMOD_FILE );
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	return false !== file_put_contents( $file_path, $contents );
}

/**
 * SCF の全設定を JSON へエクスポートする（ファイル名は scf-{数値ID}.json のみ）
 *
 * @param int           $post_id 保存された投稿の ID
 * @param \WP_Post|null $post    保存された投稿
 */
function baizy_scf_export_field_group( $post_id, $post = null ): void {
	// SCF が有効化されているか確認
	if ( ! class_exists( 'Smart_Custom_Fields_Setting' ) ) {
		return;
	}

	// SCF の設定（投稿タイプ smart-custom-fields）以外はスキップ
	$post = $post instanceof \WP_Post ? $post : get_post( $post_id );
	if ( ! $post instanceof \WP_Post || 'smart-custom-fields' !== $post->post_type ) {
		return;
	}

	// 権限チェック: テーマ設定を編集できるユーザー（通常は管理者）のみ
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	// 自動保存・リビジョン・新規作成画面を開いただけの自動下書きはスキップ
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || 'auto-draft' === $post->post_status ) {
		return;
	}

	$data_dir = BAIZY_THEME_PATH . '/data/field-groups';

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
	if ( ! wp_mkdir_p( $data_dir ) || ! is_writable( $data_dir ) ) {
		baizy_scf_log( 'ディレクトリを作成できないか書き込めません: ' . $data_dir );
		return;
	}

	// SCF が実際に使うのは公開済みの設定だけなので、エクスポートも公開済みに限る
	// （ゴミ箱へ移動・下書きに戻した設定は、下の削除処理で JSON も消える）
	$settings_posts = get_posts(
		array(
			'post_type'      => 'smart-custom-fields',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);

	// 各設定を個別のファイルとして保存
	$current_files = array();
	foreach ( $settings_posts as $settings_post ) {
		$filename        = 'scf-' . (int) $settings_post->ID . '.json';
		$current_files[] = $filename;

		$json = wp_json_encode( baizy_scf_setting_to_array( $settings_post ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		if ( ! $json || ! baizy_scf_write_file( $data_dir . '/' . $filename, $json ) ) {
			baizy_scf_log( '書き込みに失敗: ' . $filename );
		}
	}

	// 存在しなくなったSCF設定のJSONを削除
	$existing_files = glob( $data_dir . '/scf-*.json' );
	foreach ( $existing_files ? $existing_files : array() as $file ) {
		if ( ! in_array( basename( $file ), $current_files, true ) ) {
			wp_delete_file( $file );
		}
	}
}

// SCF設定保存時にエクスポート
// SCF は save_post でフィールド定義のメタを保存する。それより先に動く save_post_{post_type} だと
// 保存前の古い内容を書き出してしまうため、メタの保存まで終わった後の wp_after_insert_post を使う
add_action( 'wp_after_insert_post', 'baizy_scf_export_field_group', 10, 2 );
