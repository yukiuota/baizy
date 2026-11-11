<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// ブロックパターン設定（patternsディレクトリ自動読み込み）

add_action(
	'after_setup_theme',
	function () {
		// WordPressデフォルトのブロックパターンを無効化
		remove_theme_support( 'core-block-patterns' );
	}
);

// パターンカテゴリーを登録（パターン本体は patterns/ から WP が自動登録するため不要）
function baizy_register_block_pattern_categories() {
	$categories = array(
		'sample'         => 'サンプル',
		'baizy-hero'     => 'ヒーローセクション',
		'baizy-content'  => 'コンテンツ',
		'baizy-footer'   => 'フッター',
		'baizy-header'   => 'ヘッダー',
		'baizy-original' => 'オリジナルパターン',
	);

	foreach ( $categories as $slug => $label ) {
		register_block_pattern_category( $slug, array( 'label' => $label ) );
	}
}
add_action( 'init', 'baizy_register_block_pattern_categories' );

// TypeScript製カスタムブロックは baizy-custom-blocks プラグインへ移行済み


// 各投稿のブロックの表示・非表示指定
function baizy_restrict_blocks_for_cases( $allowed_blocks, $block_editor_context ) {
	// 投稿タイプ
	if ( ! empty( $block_editor_context->post ) && 'news' === $block_editor_context->post->post_type ) {
		// 許可するブロックを指定（カスタムブロック + WordPress標準ブロック）
		return array(
			// カスタムブロック
			// 'my-blocks/◯◯',

			// WordPress標準ブロック - テキスト
			'core/paragraph',
			'core/heading',
			'core/list',
			'core/list-item', // リストの各項目。許可しないとリストに項目を追加できない
			'core/quote',
			'core/code',
			'core/preformatted',
			'core/pullquote',
			'core/table',
			'core/verse',

			// WordPress標準ブロック - メディア
			'core/image',
			'core/gallery',
			'core/audio',
			'core/video',
			'core/file',

			// WordPress標準ブロック - レイアウト（news の初期テンプレートで使用。post_types.php 参照）
			'core/columns',
			'core/column',
		);
	}

	// 他の投稿タイプではすべてのブロックを許可
	return $allowed_blocks;
}
add_filter( 'allowed_block_types_all', 'baizy_restrict_blocks_for_cases', 10, 2 );





// Gutenbergエディター内でのブロック属性を変更

/**
 * エディター内の見出しブロックに post-heading0{レベル} クラスを付けるスクリプトを返す
 *
 * エディターの編集領域は iframe の中にあり、管理画面側の document からは見出しの要素を取得できない。
 * そのため DOM を直接書き換えず、ブロックのラッパーに渡すクラスをフィルターで追加する
 * （保存される HTML には影響せず、エディター内の表示だけが変わる）。
 *
 * @return string JavaScript
 */
function baizy_modify_heading_block_attributes(): string {
	return <<<'JS'
( function () {
	var el = wp.element.createElement;
	var withHeadingClass = wp.compose.createHigherOrderComponent( function ( BlockListBlock ) {
		return function ( props ) {
			if ( 'core/heading' !== props.name ) {
				return el( BlockListBlock, props );
			}
			var level = parseInt( props.attributes.level, 10 ) || 2;
			var className = ( props.className ? props.className + ' ' : '' ) + 'post-heading0' + level;
			return el( BlockListBlock, Object.assign( {}, props, { className: className } ) );
		};
	}, 'withBaizyHeadingClass' );
	wp.hooks.addFilter( 'editor.BlockListBlock', 'baizy/heading-class', withHeadingClass );
} )();
JS;
}

// 投稿タイプがnewsの編集画面でのみスクリプトを追加
function baizy_enqueue_heading_script(): void {
	$screen = get_current_screen();
	if ( ! $screen || 'news' !== $screen->post_type ) {
		return;
	}

	// 依存する wp.hooks などの読み込み後に実行させるため、ファイルを持たないハンドルを登録してインラインで出力する
	wp_register_script( 'baizy-editor-heading', false, array( 'wp-hooks', 'wp-compose', 'wp-element' ), wp_get_theme()->get( 'Version' ), true );
	wp_enqueue_script( 'baizy-editor-heading' );
	wp_add_inline_script( 'baizy-editor-heading', baizy_modify_heading_block_attributes() );
}
add_action( 'enqueue_block_editor_assets', 'baizy_enqueue_heading_script' );
