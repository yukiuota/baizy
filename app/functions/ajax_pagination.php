<?php

if ( !defined( 'ABSPATH' ) ) exit;

// ----------------------------------------------------- //
// 非同期ページネーション
// ----------------------------------------------------- //

// -----------------------------------------------------
// アーカイブ：ページネーション
// -----------------------------------------------------
function custom_ajax_pagination() {
    global $wp_query;
    $big = 999999999; // need an unlikely integer
  
    $pagination_links = paginate_links( array(
        'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
        'format'    => '?paged=%#%',
        'current'   => max( 1, get_query_var('paged') ),
        'total'     => $wp_query->max_num_pages,
        'mid_size'  => 2,
        'end_size'  => 1,
        'prev_text' => __('prev', 'textdomain'),
        'next_text' => __('next', 'textdomain'),
        'type'      => 'array',
    ) );
  
    if ( is_array( $pagination_links ) ) {
        echo '<div id="js-ajax-pagination" class="pagination">';
        foreach ( $pagination_links as $link ) {
            if ( strpos( $link, 'current' ) !== false ) {
                echo '<span aria-current="page" class="current">' . $link . '</span>';
            } else {
                echo str_replace('<a', '<a class="cp_pagenum"', $link);
            }
        }
        echo '</div>';
    }
  }
  
  // Ajaxエンドポイントの追加
  function enqueue_ajax_pagination_scripts() {
      // スクリプトの登録
      wp_register_script('ajax-pagination', get_template_directory_uri() . '/app/ajax/ajax-pagination.js', array('jquery'), time(), true);
      
      // スクリプトの読み込み
      wp_enqueue_script('ajax-pagination');
      
      // Ajaxオブジェクトの設定
      wp_localize_script('ajax-pagination', 'ajax_object', array(
          'ajax_url' => admin_url('admin-ajax.php'),
          'nonce' => wp_create_nonce('ajax_pagination_nonce'),
          'security' => wp_create_nonce('ajax_pagination_nonce')
      ));
  }
  add_action('wp_enqueue_scripts', 'enqueue_ajax_pagination_scripts', 20);
  
  // Ajaxハンドラー
  function load_more_posts() {
      // nonceの検証
      if (!isset($_POST['nonce'])) {
          wp_send_json_error(array('message' => 'Nonce is not set'));
          die();
      }
  
      if (!wp_verify_nonce($_POST['nonce'], 'ajax_pagination_nonce')) {
          wp_send_json_error(array('message' => 'Invalid nonce'));
          die();
      }
  
      // ページ番号の検証
      if (!isset($_POST['page']) || !is_numeric($_POST['page'])) {
          wp_send_json_error(array('message' => 'Invalid page number'));
          die();
      }
  
      // 投稿タイプの検証
      if (!isset($_POST['post_type'])) {
          wp_send_json_error(array('message' => 'Post type is not set'));
          die();
      }
  
      $page = intval($_POST['page']);
      $post_type = sanitize_text_field($_POST['post_type']);
      
      // クエリの引数を設定
      $args = array(
          'post_type' => $post_type,
          'posts_per_page' => get_option('posts_per_page'),
          'paged' => $page,
          'post_status' => 'publish'
      );
      
      // タクソノミークエリがある場合は追加
      if (!empty($_POST['tax_query'])) {
          $args['tax_query'] = $_POST['tax_query'];
      }
      
      // カテゴリークエリがある場合は追加
      if (!empty($_POST['cat'])) {
          $args['cat'] = intval($_POST['cat']);
      }
      
      // タグクエリがある場合は追加
      if (!empty($_POST['tag'])) {
          $args['tag'] = sanitize_text_field($_POST['tag']);
      }
      
      // 検索クエリがある場合は追加
      if (!empty($_POST['s'])) {
          $args['s'] = sanitize_text_field($_POST['s']);
      }
      
      // 著者クエリがある場合は追加
      if (!empty($_POST['author'])) {
          $args['author'] = intval($_POST['author']);
      }
      
      // 日付クエリがある場合は追加
      if (!empty($_POST['year']) || !empty($_POST['month']) || !empty($_POST['day'])) {
          $args['date_query'] = array();
          if (!empty($_POST['year'])) {
              $args['date_query']['year'] = intval($_POST['year']);
          }
          if (!empty($_POST['month'])) {
              $args['date_query']['month'] = intval($_POST['month']);
          }
          if (!empty($_POST['day'])) {
              $args['date_query']['day'] = intval($_POST['day']);
          }
      }
      
      $query = new WP_Query($args);
      
      if (!$query->have_posts()) {
          wp_send_json_error(array('message' => 'No posts found'));
          die();
      }
      
      // 投稿リストの内容を取得
      ob_start();
      if ($query->have_posts()) {
          while ($query->have_posts()) {
              $query->the_post();
              ?>
<ul>
    <li>
        <a href="<?php the_permalink(); ?>">
            <?php the_title(); ?>
        </a>
    </li>
</ul>
<?php
          }
      }
      $posts_content = ob_get_clean();
      
      // ページネーションの内容を取得
      ob_start();
      $big = 999999999;
      $pagination_links = paginate_links(array(
          'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
          'format' => '?paged=%#%',
          'current' => $page,
          'total' => $query->max_num_pages,
          'mid_size' => 2,
          'end_size' => 1,
          'prev_text' => __('prev', 'textdomain'),
          'next_text' => __('next', 'textdomain'),
          'type' => 'array'
      ));
      
      if (is_array($pagination_links)) {
          echo '<div class="pagination">';
          foreach ($pagination_links as $link) {
              if (strpos($link, 'current') !== false) {
                  echo '<span aria-current="page" class="current">' . $link . '</span>';
              } else {
                  echo str_replace('<a', '<a class="cp_pagenum"', $link);
              }
          }
          echo '</div>';
      }
      $pagination_content = ob_get_clean();
      
      wp_send_json_success(array(
          'posts' => $posts_content,
          'pagination' => $pagination_content,
          'max_pages' => $query->max_num_pages,
          'current_page' => $page,
          'post_type' => $post_type
      ));
      
      wp_reset_postdata();
      die();
  }
  add_action('wp_ajax_load_more_posts', 'load_more_posts');
  add_action('wp_ajax_nopriv_load_more_posts', 'load_more_posts');