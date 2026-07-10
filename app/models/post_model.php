<?php
namespace Baizy\Models;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class PostModel {

	/**
	 * news カスタム投稿を新着順で取得
	 *
	 * @param int $limit 取得件数
	 * @return \WP_Post[]
	 */
	public static function get_latest_news( int $limit = 5 ): array {
		return get_posts(
			array(
				'post_type'      => 'news',
				'posts_per_page' => $limit,
				'post_status'    => 'publish',
			)
		);
	}
}
