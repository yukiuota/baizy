<?php
namespace Baizy\Setup;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class ThemeSetup {

	private string $text_domain    = 'baizy';
	private string $languages_path = BAIZY_THEME_PATH . '/app/languages';

	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );
		add_filter( 'body_class', array( $this, 'add_slug_to_body_class' ) );
		add_action( 'template_redirect', array( $this, 'disable_author_archive' ) );
	}

	public function setup_theme(): void {
		load_theme_textdomain( $this->text_domain, $this->languages_path );
		$this->cleanup_wp_head();
		$this->add_theme_supports();
		$this->disable_auto_paragraph();
	}

	private function cleanup_wp_head(): void {
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wp_resource_hints', 2 );
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
	}

	private function add_theme_supports(): void {
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'site-icon' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'block-patterns' );
	}

	private function disable_auto_paragraph(): void {
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_excerpt', 'wpautop' );
	}

	public function add_slug_to_body_class( array $classes ): array {
		global $post;
		if ( isset( $post ) ) {
			$classes[] = $post->post_name;
		}
		return $classes;
	}

	public function disable_author_archive(): void {
		if ( is_author() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}
}
