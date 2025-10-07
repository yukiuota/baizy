<?php
if ( !defined( 'ABSPATH' ) ) exit;

// ----------------------------------------------------- //
// 非同期でmore機能実装
// ----------------------------------------------------- //

function enqueue_more_scripts() {
    wp_enqueue_script('custom-ajax-script', get_template_directory_uri() . '/app/ajax/ajax-more.js', array('jquery'), null, true);
    wp_localize_script('custom-ajax-script', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_more_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_more_scripts');


// Ajaxアクションのフック
add_action('wp_ajax_myplugin_more_posts', 'myplugin_more_posts');
add_action('wp_ajax_nopriv_myplugin_more_posts', 'myplugin_more_posts');

function myplugin_more_posts() {
    // nonceの検証
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_more_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        wp_die();
    }

    // 入力値の検証と無害化
    $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
    $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
    $output = '';

    $args = array(
        'posts_per_page' => 5,
        'offset' => $offset,
        'post_type' => 'aa',
        'post_status' => 'publish'
    );

    // カテゴリIDが渡されている場合、タクソノミーの引数を追加
    if ($category_id > 0) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'cat01',
                'field'    => 'term_id',
                'terms'    => $category_id,
            ),
        );
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $output .= '<p>';
            $output .= '<a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a>';
            $output .= '</p>';
        }
        $has_more_posts = true;
    } else {
        $output .= '<p>No posts found.</p>';
        $has_more_posts = false;
    }

    wp_reset_postdata();
    wp_send_json(array(
        'output' => $output,
        'has_more_posts' => $has_more_posts
    ));
    wp_die();
}