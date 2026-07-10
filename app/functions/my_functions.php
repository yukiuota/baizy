<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// ----------------------------------------------------- //
// グローバル関数ラッパー
// 実装は app/Helpers/ の各クラスに委譲しています
// ----------------------------------------------------- //

// TemplateHelper
if ( ! function_exists( 'baizy_template_part' ) ) {
	function baizy_template_part( $slug ) {
		\Baizy\Helpers\TemplateHelper::part( $slug );
	}
}

// ImageHelper
if ( ! function_exists( 'baizy_img' ) ) {
	function baizy_img( $path ) {
		return \Baizy\Helpers\ImageHelper::url( $path );
	}
}

if ( ! function_exists( 'baizy_img_wh' ) ) {
	function baizy_img_wh( $path, $lazy = true ) {
		\Baizy\Helpers\ImageHelper::attributes( $path, $lazy );
	}
}

if ( ! function_exists( 'baizy_get_svg_dimensions' ) ) {
	function baizy_get_svg_dimensions( $svg_file_path ) {
		return \Baizy\Helpers\ImageHelper::svg_dimensions( $svg_file_path );
	}
}
