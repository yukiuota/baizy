<?php
namespace Baizy\Setup;

if ( ! defined( 'ABSPATH' ) ) exit;

class Scripts {

    /** @var string[] defer 適用スクリプト */
    private array $defer_scripts = [
        'baizy-main-script',  // メインテーマスクリプト（jQuery非依存）
        'custom-page-script', // CF7フォーム（jQuery非依存、DOMContentLoaded使用）
    ];

    /** @var string[] jQuery依存の defer 適用スクリプト */
    private array $jquery_dependent_defer_scripts = [];

    /** @var string[] async 適用スクリプト */
    private array $async_scripts = [];

    /** @var string[] jQuery 本体（属性を付与しない） */
    private array $jquery_scripts = [ 'jquery', 'jquery-core', 'jquery-migrate' ];

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_filter( 'script_loader_tag',  [ $this, 'add_script_attributes' ], 10, 3 );
    }

    private function get_file_version( string $file_path ): ?int {
        return file_exists( $file_path ) ? filemtime( $file_path ) : null;
    }

    public function enqueue_styles(): void {
        $path    = get_template_directory() . '/resources/common/css/common.css';
        $version = $this->get_file_version( $path );
        if ( $version ) {
            wp_enqueue_style( 'baizy-main', BAIZY_THEME_URI . '/resources/common/css/common.css', [], $version );
        }
        $this->enqueue_body_class_styles();
    }

    private function enqueue_body_class_styles(): void {
        $body_classes = get_body_class();
        if ( empty( $body_classes ) ) {
            return;
        }
        $css_dir   = get_template_directory() . '/resources/common/css/';
        $css_files = glob( $css_dir . '*.css' );
        if ( empty( $css_files ) ) {
            return;
        }
        $css_map = [];
        foreach ( $css_files as $file ) {
            $css_map[ basename( $file, '.css' ) ] = $file;
        }
        foreach ( $body_classes as $class_name ) {
            $filename = sanitize_file_name( $class_name );
            if ( isset( $css_map[ $filename ] ) ) {
                wp_enqueue_style(
                    'baizy-body-class-' . sanitize_html_class( $class_name ),
                    BAIZY_THEME_URI . '/resources/common/css/' . $filename . '.css',
                    [ 'baizy-main' ],
                    filemtime( $css_map[ $filename ] )
                );
            }
        }
    }

    public function enqueue_scripts(): void {
        $path    = get_template_directory() . '/resources/common/js/script.js';
        $version = $this->get_file_version( $path );
        if ( $version ) {
            wp_enqueue_script( 'baizy-main-script', BAIZY_THEME_URI . '/resources/common/js/script.js', [ 'jquery' ], $version, true );
        }
    }

    public function add_script_attributes( string $tag, string $handle, string $src ): string {
        if ( in_array( $handle, $this->jquery_scripts, true ) ) {
            return $tag;
        }
        if ( in_array( $handle, $this->defer_scripts, true ) ||
             in_array( $handle, $this->jquery_dependent_defer_scripts, true ) ) {
            return str_replace( ' src', ' defer src', $tag );
        }
        if ( in_array( $handle, $this->async_scripts, true ) ) {
            return str_replace( ' src', ' async src', $tag );
        }
        return $tag;
    }
}
