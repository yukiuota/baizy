<?php
namespace Baizy\Controllers;

use Baizy\Models\PageMetaModel;
use Baizy\Models\PostModel;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// トップページ用コントローラー（Model から集めたデータを resources/pages/top.php へ渡す）
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
