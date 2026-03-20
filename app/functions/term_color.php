<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// -----------------------------------------------------
// タームに背景色設定機能を追加
// -----------------------------------------------------

// 管理画面でカラーピッカーのスタイルとスクリプトを読み込み
add_action( 'admin_enqueue_scripts', 'baizy_enqueue_term_color_picker_assets' );

function baizy_enqueue_term_color_picker_assets( $hook_suffix ) {
    if ( $hook_suffix === 'edit-tags.php' || $hook_suffix === 'term.php' ) {
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_script(
            'term-color-picker',
            BAIZY_THEME_URI . '/app/admin/js/term_color_picker.js',
            array( 'wp-color-picker' ),
            '1.0.0',
            true
        );
    }
}


/**
 * 指定したタクソノミーに背景色設定機能を追加する
 *
 * @param string|array $taxonomies タクソノミー名（文字列）または複数のタクソノミー名（配列）
 */
function add_term_background_color_field( $taxonomies ) {
    if ( ! is_array( $taxonomies ) ) {
        $taxonomies = array( $taxonomies );
    }

    foreach ( $taxonomies as $taxonomy ) {
        // 新規追加フォームにフィールドを追加
        add_action( "{$taxonomy}_add_form_fields", function() {
            ?>
            <div class="form-field">
                <label for="term_bg_color">背景色</label>
                <input type="text" name="term_bg_color" id="term_bg_color" value="#ffffff" class="color-picker" />
                <p class="description">このタームの背景色を選択してください。</p>
            </div>
            <?php
        } );

        // 編集フォームにフィールドを追加
        add_action( "{$taxonomy}_edit_form_fields", function( $term ) {
            $bg_color = get_term_meta( $term->term_id, 'term_bg_color', true );
            if ( empty( $bg_color ) ) {
                $bg_color = '#ffffff';
            }
            ?>
            <tr class="form-field">
                <th scope="row">
                    <label for="term_bg_color">背景色</label>
                </th>
                <td>
                    <input type="text" name="term_bg_color" id="term_bg_color" value="<?php echo esc_attr( $bg_color ); ?>" class="color-picker" />
                    <p class="description">このタームの背景色を選択してください。</p>
                </td>
            </tr>
            <?php
        } );

        // ターム作成時に保存
        add_action( "created_{$taxonomy}", function( $term_id ) {
            if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'add-tag' ) ) {
                return;
            }

            if ( isset( $_POST['term_bg_color'] ) && ! empty( $_POST['term_bg_color'] ) ) {
                update_term_meta( $term_id, 'term_bg_color', sanitize_hex_color( $_POST['term_bg_color'] ) );
            }
        } );

        // ターム編集時に保存
        add_action( "edited_{$taxonomy}", function( $term_id ) {
            if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-tag_' . $term_id ) ) {
                return;
            }

            if ( isset( $_POST['term_bg_color'] ) && ! empty( $_POST['term_bg_color'] ) ) {
                update_term_meta( $term_id, 'term_bg_color', sanitize_hex_color( $_POST['term_bg_color'] ) );
            }
        } );
    }
}


// -----------------------------------------------------
// 背景色機能を有効にするタクソノミー
// -----------------------------------------------------
add_term_background_color_field( 'sample-category' );
// 複数の場合: add_term_background_color_field( array( 'news-category', 'news-tag', 'category' ) );


// -----------------------------------------------------
// タームの背景色を取得する関数
// -----------------------------------------------------
function get_term_background_color( $term_id ) {
    return \Baizy\Models\TaxonomyModel::getTermBackgroundColor( (int) $term_id );
}

function get_term_background_style( $term_id ) {
    return \Baizy\Models\TaxonomyModel::getTermBackgroundStyle( (int) $term_id );
}
