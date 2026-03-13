<?php
namespace Baizy\Models;

if ( ! defined( 'ABSPATH' ) ) exit;

class PostModel {

    /**
     * タクソノミー・タームに紐づく投稿を全件取得
     *
     * @param string $post_type   投稿タイプ
     * @param string $taxonomy    タクソノミースラッグ
     * @param string $term_slug   タームスラッグ
     * @return \WP_Query
     */
    public static function getByTaxTerm( string $post_type, string $taxonomy, string $term_slug ): \WP_Query {
        $args = [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'tax_query'      => [[
                'taxonomy' => $taxonomy,
                'field'    => 'slug',
                'terms'    => $term_slug,
            ]],
        ];
        return new \WP_Query( $args );
    }

    /**
     * オフセット付きで投稿を取得（もっと読む機能用）
     *
     * @param string $post_type   投稿タイプ
     * @param int    $offset      取得開始位置
     * @param int    $limit       取得件数
     * @param int    $category_id タームID（0 = 絞り込みなし）
     * @param string $taxonomy    タクソノミースラッグ
     * @return \WP_Query
     */
    public static function getMorePosts(
        string $post_type,
        int $offset,
        int $limit = 5,
        int $category_id = 0,
        string $taxonomy = 'cat01'
    ): \WP_Query {
        $args = [
            'post_type'      => $post_type,
            'posts_per_page' => $limit,
            'offset'         => $offset,
            'post_status'    => 'publish',
        ];
        if ( $category_id > 0 ) {
            $args['tax_query'] = [[
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => $category_id,
            ]];
        }
        return new \WP_Query( $args );
    }

    /**
     * ページネーション付き投稿を取得
     *
     * @param array $args WP_Queryに渡す引数（sanitize済みであること）
     * @return \WP_Query
     */
    public static function getPaginatedPosts( array $args ): \WP_Query {
        return new \WP_Query( $args );
    }

    /**
     * タクソノミーで絞り込んだ投稿を取得
     *
     * @param string $post_type  投稿タイプ
     * @param array  $tax_terms  [ taxonomy_slug => term_slug, ... ]
     * @return \WP_Post[]
     */
    public static function getFilteredPosts( string $post_type, array $tax_terms ): array {
        $args = [
            'posts_per_page' => -1,
            'post_type'      => $post_type,
            'tax_query'      => [ 'relation' => 'AND' ],
        ];
        foreach ( $tax_terms as $taxonomy => $slug ) {
            $args['tax_query'][] = [
                'taxonomy' => sanitize_key( $taxonomy ),
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $slug ),
            ];
        }
        return get_posts( $args );
    }
}
