<?php
namespace Baizy\Services;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class ExternalLinksManager {

	private static ?array $links = null;

	private static function load_links(): array {
		if ( null !== self::$links ) {
			return self::$links;
		}

		$template_dir = get_template_directory();
		$json_path    = $template_dir . '/resources/settings/links.json';
		$real_path    = realpath( $json_path );
		$real_dir     = realpath( $template_dir );

		if ( false === $real_path || ! str_starts_with( $real_path, $real_dir ) ) {
			self::log( 'Invalid file path detected.' );
			self::$links = array();
			return self::$links;
		}

		if ( ! file_exists( $real_path ) || ! is_readable( $real_path ) ) {
			self::log( 'links.json not found or not readable.' );
			self::$links = array();
			return self::$links;
		}

		if ( filesize( $real_path ) > 1048576 ) {
			self::log( 'links.json file size exceeds limit.' );
			self::$links = array();
			return self::$links;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$json = file_get_contents( $real_path );
		if ( false === $json ) {
			self::log( 'Failed to read links.json.' );
			self::$links = array();
			return self::$links;
		}

		$data = json_decode( $json, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			self::log( 'JSON decode error - ' . json_last_error_msg() );
			self::$links = array();
			return self::$links;
		}

		if ( ! is_array( $data ) ) {
			self::log( 'Invalid data structure in links.json.' );
			self::$links = array();
			return self::$links;
		}

		self::$links = $data;
		return self::$links;
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
