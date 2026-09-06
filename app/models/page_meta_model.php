<?php
namespace Baizy\Models;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// ACFフィールドの取得・整形を集約するモデル（ビューでの get_field() 直呼びは禁止）
class PageMetaModel {

	/**
	 * ACFフィールドを安全に取得する（ACF無効時も致命的エラーにしない）
	 *
	 * @param string $name    フィールド名
	 * @param int    $post_id 投稿ID
	 * @param mixed  $default フィールドが空・未定義のときに返す値
	 * @return mixed
	 */
	public static function field( string $name, int $post_id, $default = null ) {
		if ( ! function_exists( 'get_field' ) || ! $post_id ) {
			return $default;
		}
		$value = get_field( $name, $post_id );
		return ( null === $value || '' === $value || false === $value ) ? $default : $value;
	}

	/**
	 * ヒーローセクションのフィールド一式を整形して返す
	 *
	 * @param int $post_id 投稿ID
	 * @return array{title:string, image:array|null}
	 */
	public static function get_hero( int $post_id ): array {
		return array(
			'title' => (string) self::field( 'hero_title', $post_id, get_the_title( $post_id ) ),
			'image' => self::field( 'hero_image', $post_id ), // ACF返り値形式「画像配列」を想定
		);
	}
}
