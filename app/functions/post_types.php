<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// -----------------------------------------------------
// カスタム投稿タイプ・タクソノミー登録
// -----------------------------------------------------

add_action( 'init', 'baizy_register_post_types' );

function baizy_register_post_types() {
    // News
    register_post_type(
        'news',
        array(
            'label'        => 'ニュース',
            'public'       => true,
            'has_archive'  => true,
            'show_in_rest' => true,
            'menu_position' => 5,
            'supports'     => array( 'title', 'editor', 'thumbnail', 'revisions' ),
            'template'     => array(
                array( 'core/heading', array(
                    'level'       => 2,
                    'placeholder' => 'ニュースタイトルを入力',
                ) ),
                array( 'core/columns', array( 'columns' => 2 ), array(
                    array( 'core/column', array(), array(
                        array( 'core/image', array() )
                    ) ),
                    array( 'core/column', array(), array(
                        array( 'core/paragraph', array(
                            'placeholder' => '説明文を入力'
                        ) )
                    ) )
                ) )
            ),
            'template_lock' => 'false',
        )
    );

    register_taxonomy(
        'news-category',
        'news',
        array(
            'label'        => 'カテゴリー',
            'hierarchical' => true,
            'public'       => true,
            'show_in_rest' => true,
        )
    );

    register_taxonomy(
        'news-tag',
        'news',
        array(
            'label'                 => 'タグ',
            'hierarchical'          => false,
            'public'                => true,
            'show_in_rest'          => true,
            'update_count_callback' => '_update_post_term_count',
        )
    );

    // Sample
    register_post_type(
        'sample',
        array(
            'label'        => 'サンプル',
            'public'       => true,
            'has_archive'  => true,
            'show_in_rest' => true,
            'menu_position' => 5,
            'supports'     => array( 'title', 'editor', 'thumbnail', 'revisions' ),
        )
    );

    register_taxonomy(
        'sample-category',
        'sample',
        array(
            'label'        => 'カテゴリー',
            'hierarchical' => true,
            'public'       => true,
            'show_in_rest' => true,
        )
    );
}


// -----------------------------------------------------
// 投稿タイプのサポート機能を変更
// -----------------------------------------------------
add_action( 'init', 'baizy_remove_post_support' );

function baizy_remove_post_support() {
    remove_post_type_support( 'post', 'post-formats' );
}


// -----------------------------------------------------
// アーカイブページの表示件数を変更
// -----------------------------------------------------
add_action( 'pre_get_posts', 'baizy_custom_posts_per_page' );

function baizy_custom_posts_per_page( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {
        if ( $query->is_post_type_archive( 'news' ) ) {
            $query->set( 'posts_per_page', 10 );
        }
    }
}


// -----------------------------------------------------
// デフォルトタームを設定
// -----------------------------------------------------
add_action( 'wp_insert_post', 'baizy_set_default_news_category', 10, 3 );

function baizy_set_default_news_category( $post_id, $post, $update ) {
    if ( $post->post_type !== 'news' ) {
        return;
    }

    if ( ! in_array( $post->post_status, array( 'publish', 'draft', 'pending', 'future' ) ) ) {
        return;
    }

    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    $current_terms = wp_get_object_terms( $post_id, 'news-category' );

    if ( empty( $current_terms ) || is_wp_error( $current_terms ) ) {
        $default_term = 'notice';
        $term = get_term_by( 'slug', $default_term, 'news-category' );
        if ( $term ) {
            wp_set_object_terms( $post_id, $term->term_id, 'news-category' );
        }
    }
}
