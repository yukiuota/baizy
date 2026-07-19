<?php
namespace Baizy\Controllers;

use Baizy\Models\PageMetaModel;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * 【サンプル】コントローラーの書き方見本
 *
 * ペアになるビュー: resources/pages/sample_mvc.php
 *
 * ■ 役割分担のルール
 *   Model      (app/models/)      … get_posts / get_field などデータ取得と整形
 *   Controller (app/controllers/) … Model を呼び、ビューへ渡す配列を組み立てる
 *   View       (resources/)       … $args を表示するだけ。get_field() 禁止
 *
 * ■ 新しいページを作る手順
 *   1. app/controllers/xxx_controller.php を作成（このファイルをコピー）
 *   2. resources/pages/xxx.php を作成（sample_mvc.php をコピー）
 *   3. resources/layouts/index.php のルーターで
 *      baizy_template_part( 'resources/pages/xxx', XxxController::data() );
 *   4. composer dump-autoload（新規クラスファイル追加時のみ）
 */
class SampleController {

	/**
	 * ビューへ渡すデータを組み立てる
	 *
	 * @param int $post_id 対象の投稿ID（省略時は現在の投稿）
	 * @return array
	 */
	public static function data( int $post_id = 0 ): array {
		$post_id = $post_id ? $post_id : get_the_ID();

		return array(
			// パターン1: シンプルなACFフィールド
			// このページでしか使わない単純な値は PageMetaModel::field() で直接取得してよい
			'catch_copy' => PageMetaModel::field( 'catch_copy', $post_id, '' ),

			// パターン2: 複数箇所で使う・整形が必要なフィールド群
			// Model にメソッドを作って委譲する（フィールド名変更時の修正が1箇所で済む）
			'hero'       => PageMetaModel::get_hero( $post_id ),

			// パターン3: 繰り返しフィールド（Repeater）
			// 生の配列をそのまま渡さず、ビューが使いやすいキーに整形してから渡す
			'faq_items'  => self::shape_faq( PageMetaModel::field( 'faq', $post_id, array() ) ),
		);
	}

	/**
	 * Repeaterフィールド「faq」をビュー用に整形する
	 *
	 * @param array $rows ACFのRepeater生データ
	 * @return array<array{question:string, answer:string}>
	 */
	private static function shape_faq( array $rows ): array {
		$items = array();
		foreach ( $rows as $row ) {
			// 必須項目が空の行はビューへ渡す前に除外しておく
			if ( empty( $row['question'] ) ) {
				continue;
			}
			$items[] = array(
				'question' => (string) $row['question'],
				'answer'   => (string) ( $row['answer'] ?? '' ),
			);
		}
		return $items;
	}
}
