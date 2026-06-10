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
		return \Baizy\Helpers\ImageHelper::svgDimensions( $svg_file_path );
	}
}

// EscapeHelper
if ( ! function_exists( 'e' ) ) {
	function e( $str ) {
		\Baizy\Helpers\EscapeHelper::html( $str );
	}
}

if ( ! function_exists( 'e_attr' ) ) {
	function e_attr( $str ) {
		\Baizy\Helpers\EscapeHelper::attr( $str );
	}
}

if ( ! function_exists( 'e_url' ) ) {
	function e_url( $str ) {
		\Baizy\Helpers\EscapeHelper::url( $str );
	}
}

if ( ! function_exists( 'e_js' ) ) {
	function e_js( $str ) {
		\Baizy\Helpers\EscapeHelper::js( $str );
	}
}
