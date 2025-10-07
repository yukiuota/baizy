<?php

if ( !defined( 'ABSPATH' ) ) exit;

// ----------------------------------------------------- //
// 非同期絞り込み機能
// ----------------------------------------------------- //

function enqueue_ajax_scripts() {
    wp_enqueue_script('custom-ajax-search-script', get_template_directory_uri() . '/app/ajax/ajax-search.js', array('jquery'), null, true);
    wp_localize_script('custom-ajax-search-script', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_search_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_ajax_scripts');

// AJAXハンドラを追加
add_action('wp_ajax_filter_posts', 'filter_posts');
add_action('wp_ajax_nopriv_filter_posts', 'filter_posts');

function filter_posts() {
    // nonceの検証
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_search_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        wp_die();
    }

    $terms = array();
    if (isset($_POST['terms']) && !empty($_POST['terms'])) {
        $raw_terms = json_decode(stripslashes($_POST['terms']), true);
        
        // 入力値の検証と無害化
        if (is_array($raw_terms)) {
            if (!empty($raw_terms['cat01']) && is_string($raw_terms['cat01'])) {
                $terms['cat01'] = sanitize_text_field($raw_terms['cat01']);
            }
            
            if (!empty($raw_terms['cat02']) && is_string($raw_terms['cat02'])) {
                $terms['cat02'] = sanitize_text_field($raw_terms['cat02']);
            }
        }
    }

    $args = array(
        'posts_per_page' => -1,
        'post_type' => 'aa',
        'tax_query' => array(
            'relation' => 'AND',
        ),
    );

    if (!empty($terms)) {
        if (!empty($terms['cat01'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'cat01',
                'field' => 'slug',
                'terms' => $terms['cat01'],
            );
        }
        if (!empty($terms['cat02'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'cat02',
                'field' => 'slug',
                'terms' => $terms['cat02'],
            );
        }
    }

    $posts = get_posts($args);
    $output = '';

    if (!empty($posts)) {
        foreach ($posts as $post) {
            setup_postdata($post);
            $output .= '<p><a href="' . esc_url(get_permalink($post->ID)) . '">';
            $output .= esc_html(get_the_title($post->ID));
            $output .= '</a></p>';
        }
        wp_reset_postdata();
    } else {
        $output = '<p>該当する記事がありません。</p>';
    }

    echo $output;
    wp_die();
}