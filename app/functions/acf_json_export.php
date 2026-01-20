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

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ACF JSON保存先を指定
 * ACFの標準JSON同期機能を使用
 */
function baizy_acf_json_save_point( $path ) {
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
 * SCFフィールドグループ（カスタムフィールド定義）をJSONにエクスポート
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
    // データディレクトリのパス
    $data_dir = BAIZY_THEME_PATH . '/data/field-groups';

    // ディレクトリが存在しない場合は作成
    if ( ! file_exists( $data_dir ) ) {
        if ( ! wp_mkdir_p( $data_dir ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Failed to create directory: ' . $data_dir );
            }
            return;
        }
    }

    // ディレクトリが書き込み可能か確認
    if ( ! is_writable( $data_dir ) ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'Directory is not writable: ' . $data_dir );
        }
        return;
    }

    // 全てのSCF設定を取得
    $settings = SCF::get_settings();
    
    if ( empty( $settings ) ) {
        return;
    }

    // 現在のフィールドグループのキーを保持
    $current_keys = array();

    // 各設定を個別のファイルとして保存
    foreach ( $settings as $setting ) {
        // 設定IDを取得
        $setting_id = $setting->get_id();
        
        if ( ! $setting_id ) {
            continue;
        }

        // エクスポート用データを構築
        $export_data = array(
            'id'             => $setting_id,
            'title'          => $setting->get_title(),
            'menu_order'     => $setting->get_menu_order(),
            'post_types'     => $setting->get_post_types(),
            'roles'          => $setting->get_roles(),
            'options_pages'  => $setting->get_options_pages(),
            'groups'         => array(),
        );

        // グループとフィールドを取得
        $groups = $setting->get_groups();
        foreach ( $groups as $group ) {
            $group_data = array(
                'name'          => $group->get_name(),
                'repeat'        => $group->is_repeatable(),
                'fields'        => array(),
            );

            $fields = $group->get_fields();
            foreach ( $fields as $field ) {
                $group_data['fields'][] = array(
                    'name'         => $field->get( 'name' ),
                    'label'        => $field->get( 'label' ),
                    'type'         => $field->get( 'type' ),
                    'choices'      => $field->get( 'choices' ),
                    'default'      => $field->get( 'default' ),
                    'instruction'  => $field->get( 'instruction' ),
                    'notes'        => $field->get( 'notes' ),
                );
            }

            $export_data['groups'][] = $group_data;
        }

        // ファイル名のサニタイズ(パストラバーサル対策)
        $safe_setting_id = sanitize_file_name( $setting_id );
        
        // 数字以外が含まれている場合はスキップ(追加のセキュリティチェック)
        if ( ! ctype_digit( (string) $safe_setting_id ) ) {
            continue;
        }
        
        // ファイル名を生成(scf-設定ID.json)
        $filename = 'scf-' . $safe_setting_id . '.json';
        $current_keys[] = $filename;
        
        // JSONファイルのパス
        $file_path = $data_dir . '/' . $filename;
        
        // パストラバーサル対策: パスが指定ディレクトリ内にあることを確認
        $real_data_dir = realpath( $data_dir );
        $real_file_path = realpath( dirname( $file_path ) ) . '/' . basename( $file_path );
        
        if ( strpos( $real_file_path, $real_data_dir ) !== 0 ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Security: Path traversal attempt detected - ' . $filename );
            }
            continue;
        }

        // JSONとして保存
        $json_data = wp_json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

        if ( $json_data ) {
            // WordPress Filesystem APIを使用
            global $wp_filesystem;
            
            if ( ! function_exists( 'WP_Filesystem' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            
            if ( WP_Filesystem() ) {
                if ( ! $wp_filesystem->put_contents( $file_path, $json_data, FS_CHMOD_FILE ) ) {
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'Failed to write SCF JSON file: ' . $filename );
                    }
                }
            } else {
                // Fallback: 直接書き込み(互換性のため)
                if ( ! file_put_contents( $file_path, $json_data ) ) {
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'Failed to write SCF JSON file (fallback): ' . $filename );
                    }
                }
            }
        }
    }

    // 存在しなくなったSCF設定のJSONを削除
    $existing_files = glob( $data_dir . '/scf-*.json' );
    if ( $existing_files ) {
        global $wp_filesystem;
        
        foreach ( $existing_files as $file ) {
            $basename = basename( $file );
            
            // パストラバーサル対策: ファイルが指定ディレクトリ内にあることを確認
            $real_file = realpath( $file );
            if ( ! $real_file || strpos( $real_file, $real_data_dir ) !== 0 ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'Security: Invalid file path detected - ' . $basename );
                }
                continue;
            }
            
            if ( ! in_array( $basename, $current_keys, true ) ) {
                // WordPress Filesystem APIを使用
                if ( isset( $wp_filesystem ) && $wp_filesystem ) {
                    if ( $wp_filesystem->delete( $file ) ) {
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                            error_log( 'Deleted old SCF JSON: ' . $basename );
                        }
                    } else {
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                            error_log( 'Failed to delete SCF JSON: ' . $basename );
                        }
                    }
                } else {
                    // Fallback: 直接削除(互換性のため)
                    if ( @unlink( $file ) ) {
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                            error_log( 'Deleted old SCF JSON (fallback): ' . $basename );
                        }
                    } else {
                        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                            error_log( 'Failed to delete SCF JSON (fallback): ' . $basename );
                        }
                    }
                }
            }
        }
    }

    // デバッグログに記録
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( sprintf(
            'SCF Settings JSON exported: %d settings',
            count( $settings )
        ) );
    }
}

// SCF設定保存時にエクスポート
add_action( 'save_post_smart-cf', 'baizy_scf_export_field_group', 10, 1 );