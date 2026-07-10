<?php
namespace Baizy\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class ImageHelper {

	/**
	 * テーマの /resources/img/ 以下の画像 URL を返す
	 *
	 * @param string $path  /resources/img/ からの相対パス
	 * @return string       エスケープ済みURL。ファイル不在の場合は空文字
	 */
	public static function url( string $path ): string {
		$img_dir = get_template_directory() . '/resources/img/';
		if ( ! is_dir( $img_dir ) || ! file_exists( $img_dir . $path ) ) {
			return '';
		}
		return esc_url( BAIZY_THEME_URI . '/resources/img/' . $path );
	}

	/**
	 * 画像の width / height / loading 属性文字列を出力する
	 *
	 * @param string $path  /resources/img/ からの相対パス
	 * @param bool   $lazy  loading="lazy" を付与するか（デフォルト: true）
	 */
	public static function attributes( string $path, bool $lazy = true ): void {
		$img_dir = get_template_directory() . '/resources/img/';
		$full    = $img_dir . $path;

		if ( ! is_dir( $img_dir ) || ! file_exists( $full ) || ! is_file( $full ) ) {
			return;
		}

		$ext  = strtolower( pathinfo( $full, PATHINFO_EXTENSION ) );
		$attr = '';

		if ( 'svg' === $ext ) {
			$dims = self::svg_dimensions( $full );
			if ( $dims ) {
				$attr = 'width="' . (int) $dims['width'] . '" height="' . (int) $dims['height'] . '"';
			}
		} else {
			$dims = getimagesize( $full );
			if ( $dims && isset( $dims[0], $dims[1] ) ) {
				$attr = 'width="' . (int) $dims[0] . '" height="' . (int) $dims[1] . '"';
			}
		}

		if ( $lazy && $attr ) {
			$attr .= ' loading="lazy"';
		}

		echo $attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * SVG ファイルから width / height を取得する
	 *
	 * @param string $path  SVG ファイルの絶対パス
	 * @return array{width: float, height: float}|false
	 */
	public static function svg_dimensions( string $path ): array|false {
		if ( ! file_exists( $path ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $path );
		if ( ! $content ) {
			return false;
		}

		$prev = libxml_use_internal_errors( true );
		$svg  = simplexml_load_string( $content );
		libxml_use_internal_errors( $prev );

		if ( ! $svg ) {
			return false;
		}

		$attrs  = $svg->attributes();
		$width  = isset( $attrs['width'] ) ? (string) $attrs['width'] : null;
		$height = isset( $attrs['height'] ) ? (string) $attrs['height'] : null;

		// width/height がなければ viewBox から取得
		if ( ( ! $width || ! $height ) && isset( $attrs['viewBox'] ) ) {
			$vb = preg_split( '/[\s,]+/', trim( (string) $attrs['viewBox'] ) );
			if ( count( $vb ) >= 4 ) {
				$width  = $width ?? $vb[2];
				$height = $height ?? $vb[3];
			}
		}

		if ( $width && $height ) {
			$w = (float) preg_replace( '/[^0-9.]/', '', $width );
			$h = (float) preg_replace( '/[^0-9.]/', '', $height );
			if ( $w > 0 && $h > 0 ) {
				return array(
					'width'  => $w,
					'height' => $h,
				);
			}
		}

		return false;
	}
}
