<?php
namespace Baizy\Helpers;

if ( ! defined( 'ABSPATH' ) ) exit;

class TemplateHelper {

    /**
     * get_template_part にフック機構を追加して読み込む
     *
     * フック例:
     *   baizy_part_before__{slug}  読み込み前アクション
     *   baizy_part__{slug}         コンテンツ書き換えフィルター
     *   baizy_part_after__{slug}   読み込み後アクション
     *
     * @param string $slug テンプレートパス（拡張子なし）
     */
    public static function part( string $slug ): void {
        ob_start();
        get_template_part( $slug );
        $content = ob_get_clean();

        if ( has_filter( "baizy_part_before__{$slug}" ) ) {
            do_action( "baizy_part_before__{$slug}" );
        }

        if ( has_filter( "baizy_part__{$slug}" ) ) {
            $content = apply_filters( "baizy_part__{$slug}", $content );
        }

        echo $content;

        if ( has_filter( "baizy_part_after__{$slug}" ) ) {
            do_action( "baizy_part_after__{$slug}" );
        }
    }
}
