<?php
if ( ! defined( 'ABSPATH' ) ) exit;

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
function baizy_img( $path ) {
    return \Baizy\Helpers\ImageHelper::url( $path );
}

function baizy_img_wh( $path, $lazy = true ) {
    \Baizy\Helpers\ImageHelper::attributes( $path, $lazy );
}

function baizy_get_svg_dimensions( $svg_file_path ) {
    return \Baizy\Helpers\ImageHelper::svgDimensions( $svg_file_path );
}

// EscapeHelper
function e( $str ) {
    \Baizy\Helpers\EscapeHelper::html( $str );
}

function e_attr( $str ) {
    \Baizy\Helpers\EscapeHelper::attr( $str );
}

function e_url( $str ) {
    \Baizy\Helpers\EscapeHelper::url( $str );
}

function e_js( $str ) {
    \Baizy\Helpers\EscapeHelper::js( $str );
}
