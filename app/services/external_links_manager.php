<?php
namespace Baizy\Services;

if ( ! defined( 'ABSPATH' ) ) exit;

class ExternalLinksManager {

    private static ?array $links = null;

    private static function load_links(): array {
        if ( self::$links !== null ) {
            return self::$links;
        }

        $template_dir = get_template_directory();
        $json_path    = $template_dir . '/resources/settings/links.json';
        $real_path    = realpath( $json_path );
        $real_dir     = realpath( $template_dir );

        if ( $real_path === false || strpos( $real_path, $real_dir ) !== 0 ) {
            self::log( 'Invalid file path detected.' );
            return self::$links = [];
        }

        if ( ! file_exists( $real_path ) || ! is_readable( $real_path ) ) {
            self::log( 'links.json not found or not readable.' );
            return self::$links = [];
        }

        if ( filesize( $real_path ) > 1048576 ) {
            self::log( 'links.json file size exceeds limit.' );
            return self::$links = [];
        }

        $json = @file_get_contents( $real_path );
        if ( $json === false ) {
            self::log( 'Failed to read links.json.' );
            return self::$links = [];
        }

        $data = json_decode( $json, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            self::log( 'JSON decode error - ' . json_last_error_msg() );
            return self::$links = [];
        }

        if ( ! is_array( $data ) ) {
            self::log( 'Invalid data structure in links.json.' );
            return self::$links = [];
        }

        return self::$links = $data;
    }

    public static function get_link( string $key ): ?array {
        if ( empty( $key ) || ! preg_match( '/^[a-zA-Z0-9_-]+$/', $key ) ) {
            return null;
        }
        $links = self::load_links();
        $link  = $links[ $key ] ?? null;
        if ( ! is_array( $link ) || ! isset( $link['url'] ) ) {
            return null;
        }
        return $link;
    }

    public static function get_url( string $key ): string {
        $link = self::get_link( $key );
        if ( ! $link || ! is_string( $link['url'] ) ) {
            return '';
        }
        return esc_url( $link['url'] );
    }

    public static function get_all_links(): array {
        return self::load_links();
    }

    private static function log( string $message ): void {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'ExternalLinksManager: ' . $message );
        }
    }
}
