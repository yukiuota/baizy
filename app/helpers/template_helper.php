<?php
namespace Baizy\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

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
	 * @param array  $args テンプレートへ渡すデータ（テンプレート側では $args で参照）
	 */
	public static function part( string $slug, array $args = array() ): void {
		ob_start();
		get_template_part( $slug, null, $args );
		$content = ob_get_clean();

		if ( has_filter( "baizy_part_before__{$slug}" ) ) {
			do_action( "baizy_part_before__{$slug}" );
		}

		if ( has_filter( "baizy_part__{$slug}" ) ) {
			$content = apply_filters( "baizy_part__{$slug}", $content );
		}

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( has_filter( "baizy_part_after__{$slug}" ) ) {
			do_action( "baizy_part_after__{$slug}" );
		}
	}

	/**
	 * 候補テンプレートのうち最初に存在するものを読み込む
	 *
	 * @param string[] $candidates 優先順のテンプレートパス（拡張子なし）
	 * @param string   $fallback   どの候補も存在しない場合に読み込むパス
	 * @param array    $args       テンプレートへ渡すデータ（テンプレート側では $args で参照）
	 */
	public static function first_part( array $candidates, string $fallback, array $args = array() ): void {
		foreach ( $candidates as $candidate ) {
			if ( locate_template( $candidate . '.php' ) ) {
				self::part( $candidate, $args );
				return;
			}
		}
		self::part( $fallback, $args );
	}
}
