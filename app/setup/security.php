<?php
namespace Baizy\Setup;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// ユーザー情報の外部公開を制限する（ログイン ID / ユーザー名の列挙対策）
class Security {

	public function __construct() {
		add_filter( 'rest_endpoints', array( $this, 'disable_user_endpoints' ) );
		add_filter( 'wp_sitemaps_add_provider', array( $this, 'disable_users_sitemap' ), 10, 2 );
		// redirect_canonical（優先度 10）より先に 404 化するため優先度 0 で登録する
		add_action( 'template_redirect', array( $this, 'block_user_exposure_request' ), 0 );
		add_filter( 'redirect_canonical', array( $this, 'disable_author_canonical_redirect' ) );
		add_filter( 'oembed_response_data', array( $this, 'remove_author_from_oembed' ) );
	}

	// 未ログインからの users エンドポイントを登録解除する
	public function disable_user_endpoints( array $endpoints ): array {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}

		unset( $endpoints['/wp/v2/users'] );
		unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );

		return $endpoints;
	}

	// サイトマップから users プロバイダーを除外する
	public function disable_users_sitemap( $provider, $name ) {
		if ( 'users' === $name ) {
			return false;
		}

		return $provider;
	}

	// 著者アーカイブと users サイトマップ（/wp-sitemap-users-N.xml）を 404 にする
	public function block_user_exposure_request(): void {
		if ( ! is_author() && 'users' !== get_query_var( 'sitemap' ) ) {
			return;
		}

		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}

	// /?author=1 から /author/<slug>/ への正規化リダイレクトを止める（スラッグ露出対策）
	public function disable_author_canonical_redirect( $redirect_url ) {
		if ( '' !== (string) get_query_var( 'author' ) || '' !== (string) get_query_var( 'author_name' ) ) {
			return false;
		}

		return $redirect_url;
	}

	// oEmbed レスポンスから著者情報を取り除く
	public function remove_author_from_oembed( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		unset( $data['author_name'] );
		unset( $data['author_url'] );

		return $data;
	}
}
