<?php
namespace Baizy\Controllers;

use Baizy\Models\PageMetaModel;
use Baizy\Models\PostModel;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * トップページ用コントローラー
 *
 * Model からデータを集めて、ビュー（resources/pages/top.php）へ渡す配列を組み立てる。
 * 呼び出しは resources/layouts/index.php のルーターから:
 *   baizy_template_part( 'resources/pages/top', TopController::data() );
 */
class TopController {

	/**
	 * ビューへ渡すデータを組み立てる
	 *
	 * @return array{news:\WP_Post[], hero:array}
	 */
	public static function data(): array {
		$front_id = (int) get_option( 'page_on_front' );

		return array(
			'news' => PostModel::get_latest_news(),
			'hero' => PageMetaModel::get_hero( $front_id ),
		);
	}
}
