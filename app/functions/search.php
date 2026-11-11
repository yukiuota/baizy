<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// 検索フォーム・検索クエリのカスタマイズ
// pre_get_posts はメインクエリの実行前に登録しておく必要があるため、テンプレートではなくここ（常時読み込み）で定義する

/**
 * カスタム検索フォームを表示する
 *
 * @param array $args 検索フォームの設定オプション（placeholder / button_text / form_class）
 */
function baizy_custom_search_form( array $args = array() ): void {
	$settings = wp_parse_args(
		$args,
		array(
			'placeholder' => 'キーワードを入力',
			'button_text' => '検索',
			'form_class'  => 'custom-search-form',
		)
	);
	?>
<form role="search" method="get" class="<?php echo esc_attr( $settings['form_class'] ); ?>" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<input type="search" class="search-field" placeholder="<?php echo esc_attr( $settings['placeholder'] ); ?>" value="<?php echo esc_attr( get_search_query( false ) ); ?>" name="s">
	<button type="submit" class="search-submit"><?php echo esc_html( $settings['button_text'] ); ?></button>
</form>
	<?php
}

// 検索フォームをショートコードで使えるようにする
add_shortcode( 'custom_search', 'baizy_custom_search_shortcode' );
function baizy_custom_search_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'placeholder' => 'サイト内を検索',
			'button_text' => '検索',
			'form_class'  => 'shortcode-search-form',
		),
		$atts,
		'custom_search'
	);

	ob_start();
	baizy_custom_search_form( $atts );
	return (string) ob_get_clean();
}

// フロントの検索結果（メインクエリ）かどうか
function baizy_is_front_search_query( WP_Query $query ): bool {
	return ! is_admin() && $query->is_main_query() && $query->is_search();
}

// 検索対象の投稿タイプを指定（必要に応じてカスタマイズ）
add_action( 'pre_get_posts', 'baizy_custom_search_query' );
function baizy_custom_search_query( WP_Query $query ): void {
	if ( baizy_is_front_search_query( $query ) ) {
		$query->set( 'post_type', array( 'post', 'page' ) );
	}
}

/**
 * 検索対象に含めるカスタムフィールドのキーを返す（必要に応じてカスタマイズ。空配列ならカスタムフィールドは検索しない）
 *
 * @return string[]
 */
function baizy_search_meta_keys(): array {
	return array(
		'_custom_field1',
	);
}

add_filter( 'posts_search', 'baizy_search_include_meta', 10, 2 );

/**
 * カスタムフィールドも検索対象に含める
 *
 * meta_query を足すとコアの検索条件（タイトル・本文）と AND で結合され、両方に一致する投稿しか出なくなる。
 * 「タイトル・本文に一致」または「カスタムフィールドに一致」にするため、検索条件の SQL を OR で組み立て直す。
 *
 * @param string   $search コアが組み立てた検索条件（" AND (...)" の形）
 * @param WP_Query $query  クエリ
 * @return string
 */
function baizy_search_include_meta( string $search, WP_Query $query ): string {
	global $wpdb;

	if ( '' === $search || ! baizy_is_front_search_query( $query ) ) {
		return $search;
	}

	$meta_keys = baizy_search_meta_keys();
	$terms     = $query->get( 'search_terms' );
	if ( ! $meta_keys || ! is_array( $terms ) ) {
		return $search;
	}

	$key_clauses = array();
	foreach ( $meta_keys as $meta_key ) {
		$key_clauses[] = $wpdb->prepare( "{$wpdb->postmeta}.meta_key = %s", $meta_key );
	}
	$key_sql = implode( ' OR ', $key_clauses );

	// コアと同じく、すべての語に一致する投稿を対象にする（「-語」の除外指定はカスタムフィールドには適用しない）
	$term_clauses = array();
	foreach ( $terms as $term ) {
		if ( ! is_string( $term ) || '' === $term || str_starts_with( $term, '-' ) ) {
			continue;
		}
		$like           = $wpdb->prepare( "{$wpdb->postmeta}.meta_value LIKE %s", '%' . $wpdb->esc_like( $term ) . '%' );
		$term_clauses[] = "EXISTS ( SELECT 1 FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND ( {$key_sql} ) AND {$like} )";
	}
	if ( ! $term_clauses ) {
		return $search;
	}
	$meta_sql = implode( ' AND ', $term_clauses );

	// パスワード保護の投稿は、コアの検索と同じく未ログインでは対象外にする
	if ( ! is_user_logged_in() ) {
		$meta_sql .= " AND {$wpdb->posts}.post_password = ''";
	}

	// $search は " AND (...)" で始まるため、1=1 を前に置いて丸ごと括ってから OR でつなぐ
	return " AND ( ( 1=1 {$search} ) OR ( {$meta_sql} ) ) ";
}
