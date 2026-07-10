<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// -----------------------------------------------------
// 記事が属するタームを取得（配列）
// -----------------------------------------------------
function display_terms_of_post( string $taxonomy, ?int $post_id = null ): array {
	return \Baizy\Models\TaxonomyModel::get_terms_of_post( $taxonomy, $post_id ?? 0 );
}
