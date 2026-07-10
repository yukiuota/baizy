<?php
/**
 * ACF & SCF JSON Sync
 *
 * ACF: 標準JSON同期機能を使用して自動保存・読み込み
 * SCF: カスタムフィールド設定をJSON形式でエクスポート
 * 保存先: /data/field-groups/
 *
 * @package baizy
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * ACF JSON保存先を指定
 * ACFの標準JSON同期機能を使用
 */
function baizy_acf_json_save_point( $_path ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	$custom_path = BAIZY_THEME_PATH . '/data/field-groups';

	// ディレクトリが存在しない場合は作成
	if ( ! file_exists( $custom_path ) ) {
		wp_mkdir_p( $custom_path );
	}

	return $custom_path;
}
add_filter( 'acf/settings/save_json', 'baizy_acf_json_save_point' );

/**
 * ACF JSON読み込み先を指定
 * ACFの標準JSON同期機能を使用
 */
function baizy_acf_json_load_point( $paths ) {
	// デフォルトパスを削除
	unset( $paths[0] );

	// カスタムパスを追加
	$paths[] = BAIZY_THEME_PATH . '/data/field-groups';

	return $paths;
}
add_filter( 'acf/settings/load_json', 'baizy_acf_json_load_point' );

/**
 * WP_DEBUG 時のみログを出力
 *
 * @param string $message ログメッセージ
 */
function baizy_scf_log( string $message ): void {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'SCF JSON export: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}

/**
 * SCF設定オブジェクトをエクスポート用配列に変換
 *
 * @param \Smart_Custom_Fields_Setting $setting SCF設定
 * @return array
 */
function baizy_scf_setting_to_array( $setting ): array {
	$export_data = array(
		'id'            => $setting->get_id(),
		'title'         => $setting->get_title(),
		'menu_order'    => $setting->get_menu_order(),
		'post_types'    => $setting->get_post_types(),
		'roles'         => $setting->get_roles(),
		'options_pages' => $setting->get_options_pages(),
		'groups'        => array(),
	);

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
				'type'        => $field->get( 'type' ),
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
 * ファイルに内容を書き込む（WP_Filesystem 優先、file_put_contents フォールバック）
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
 * SCFフィールドグループ（カスタムフィールド定義）をJSONにエクスポート
 *
 * ファイル名は「scf-{数値ID}.json」のみ生成する（数値以外のIDはスキップ）ため、
 * パス操作の余地はない。
 *
 * @param int $post_id 投稿ID
 */
function baizy_scf_export_field_group( $post_id ) {
	// SCFが有効化されているか確認
	if ( ! class_exists( 'SCF' ) ) {
		return;
	}

	// smart-cf投稿タイプ以外はスキップ
	if ( get_post_type( $post_id ) !== 'smart-cf' ) {
		return;
	}

	// 権限チェック: 管理者またはエディター以上の権限が必要
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	// 自動保存、リビジョン、ゴミ箱をスキップ
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || get_post_status( $post_id ) === 'trash' ) {
		return;
	}

	$data_dir = BAIZY_THEME_PATH . '/data/field-groups';

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
	if ( ! wp_mkdir_p( $data_dir ) || ! is_writable( $data_dir ) ) {
		baizy_scf_log( 'ディレクトリを作成できないか書き込めません: ' . $data_dir );
		return;
	}

	$settings = SCF::get_settings();
	if ( empty( $settings ) ) {
		return;
	}

	// 各設定を個別のファイルとして保存
	$current_files = array();
	foreach ( $settings as $setting ) {
		$setting_id = $setting->get_id();

		// ファイル名は数値IDのみ許可
		if ( ! ctype_digit( (string) $setting_id ) ) {
			continue;
		}

		$filename        = 'scf-' . $setting_id . '.json';
		$current_files[] = $filename;

		$json = wp_json_encode( baizy_scf_setting_to_array( $setting ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
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
add_action( 'save_post_smart-cf', 'baizy_scf_export_field_group', 10, 1 );
