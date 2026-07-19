<?php
namespace Baizy\Models;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class TaxonomyModel {

	/**
	 * 投稿に紐づくタームデータを配列で返す
	 *
	 * @param string $taxonomy タクソノミースラッグ
	 * @param int    $post_id  投稿ID（0 = 現在の投稿）
	 * @return array  [ 'name', 'name_escaped', 'slug', 'slug_escaped', 'term_id', 'taxonomy', 'link', 'link_escaped' ][]
	 */
	public static function get_terms_of_post( string $taxonomy, int $post_id = 0 ): array {
		if ( 0 === $post_id ) {
			$post_id = get_the_ID();
		}
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! $terms || is_wp_error( $terms ) ) {
			return array();
		}
		$result = array();
		foreach ( $terms as $term ) {
			$link     = get_term_link( $term );
			$result[] = array(
				'name'         => $term->name,
				'name_escaped' => esc_html( $term->name ),
				'slug'         => $term->slug,
				'slug_escaped' => esc_attr( $term->slug ),
				'term_id'      => $term->term_id,
				'taxonomy'     => $term->taxonomy,
				'link'         => is_wp_error( $link ) ? '' : $link,
				'link_escaped' => is_wp_error( $link ) ? '' : esc_url( $link ),
			);
		}
		return $result;
	}
}
