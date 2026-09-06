<?php
namespace Baizy\Setup;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// ユーザー情報の外部公開を制限する（ログイン ID の列挙対策）
class Security {

	public function __construct() {
		add_filter( 'rest_endpoints', array( $this, 'disable_user_endpoints' ) );
		add_filter( 'wp_sitemaps_add_provider', array( $this, 'disable_users_sitemap' ), 10, 2 );
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
}
