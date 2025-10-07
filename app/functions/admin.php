<?php
if ( !defined( 'ABSPATH' ) ) exit;

// ----------------------------------------------------- //
// 管理画面のカスタマイズ
// ----------------------------------------------------- //

// -----------------------------------------------------
// 管理画面アイコン読み込み
// -----------------------------------------------------
function enqueue_dashicons() {
  wp_enqueue_style('dashicons');
}
add_action('admin_enqueue_scripts', 'enqueue_dashicons');

// -----------------------------------------------------
// 管理画面の必要ない項目を非表示
// -----------------------------------------------------
function remove_menus() {
  remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
  remove_meta_box('dashboard_primary', 'dashboard', 'side'); // WordPressニュース
  remove_meta_box('dashboard_activity', 'dashboard', 'normal');
  // remove_menu_page( 'index.php' ); // ダッシュボード.
  // remove_menu_page( 'edit.php' ); // 投稿.
  // remove_menu_page( 'upload.php' ); // メディア.
  // remove_menu_page( 'edit.php?post_type=page' ); // 固定.
  // remove_menu_page( 'edit-comments.php' ); // コメント.
  // remove_menu_page( 'themes.php' ); // 外観.
  // remove_menu_page( 'plugins.php' ); // プラグイン.
  // remove_menu_page( 'users.php' ); // ユーザー.
  // remove_menu_page( 'tools.php' ); // ツール.
  // remove_menu_page( 'options-general.php' ); // 設定.
}
add_action( 'admin_menu', 'remove_menus', 999 );



// -----------------------------------------------------
// 管理画面のカスタム投稿にターム絞り込み機能追加（改良版）
// -----------------------------------------------------

/**
 * 投稿タイプとタクソノミーの対応表を取得
 */
function get_post_type_taxonomies_config() {
  return array(
    'news' => array(
      'news-category' => 'カテゴリー一覧'
    ),
    // 他のカスタム投稿タイプがある場合はここに追加
    // 'products' => array(
    //   'product_category' => '商品カテゴリー一覧',
    //   'product_tag' => '商品タグ一覧'
    // )
  );
}

function add_custom_taxonomies_term_filter()
{
  global $post_type;
  
  // デバッグ: 現在の投稿タイプを確認（開発時のみ使用）
  // error_log('Current post type: ' . $post_type);
  
  // 投稿タイプとタクソノミーの対応表
  $post_type_taxonomies = get_post_type_taxonomies_config();
  
  // 現在の投稿タイプに対応するタクソノミーがあるかチェック
  if (!isset($post_type_taxonomies[$post_type])) {
    return;
  }
  
  foreach ($post_type_taxonomies[$post_type] as $taxonomy => $label) {
    // タクソノミーが存在するかチェック
    if (!taxonomy_exists($taxonomy)) {
      // error_log('Taxonomy does not exist: ' . $taxonomy);
      continue;
    }
    
    // タクソノミーの全タームを取得
    $terms = get_terms(array(
      'taxonomy' => $taxonomy,
      'hide_empty' => false,
      'orderby' => 'name',
      'order' => 'ASC'
    ));
    
    // タームが存在しない場合はスキップ
    if (empty($terms) || is_wp_error($terms)) {
      // error_log('No terms found for taxonomy: ' . $taxonomy);
      continue;
    }
    
    // 現在選択されているタームを取得
    $selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
    
    // セレクトボックスを出力
    echo '<select name="' . esc_attr($taxonomy) . '" id="' . esc_attr($taxonomy) . '" style="margin-right: 10px;">';
    echo '<option value="">' . esc_html($label) . '</option>';
    
    foreach ($terms as $term) {
      printf(
        '<option value="%s"%s>%s (%d)</option>',
        esc_attr($term->slug),
        selected($selected, $term->slug, false),
        esc_html($term->name),
        $term->count
      );
    }
    
    echo '</select>';
  }
}
add_action('restrict_manage_posts', 'add_custom_taxonomies_term_filter');

// -----------------------------------------------------
// カスタムタクソノミーでの絞り込みクエリを処理
// -----------------------------------------------------
function filter_posts_by_custom_taxonomy($query) {
  global $pagenow;
  
  // 管理画面の投稿一覧ページでのみ実行
  if (!is_admin() || $pagenow !== 'edit.php') {
    return;
  }
  
  // 投稿タイプとタクソノミーの対応表（共通設定を使用）
  $post_type_taxonomies = get_post_type_taxonomies_config();
  
  $current_post_type = isset($_GET['post_type']) ? $_GET['post_type'] : 'post';
  
  // 現在の投稿タイプに対応するタクソノミーがあるかチェック
  if (!isset($post_type_taxonomies[$current_post_type])) {
    return;
  }
  
  $tax_queries = array();
  
  // 各タクソノミーについて絞り込み条件をチェック
  foreach ($post_type_taxonomies[$current_post_type] as $taxonomy => $label) {
    if (isset($_GET[$taxonomy]) && !empty($_GET[$taxonomy])) {
      $tax_queries[] = array(
        'taxonomy' => $taxonomy,
        'field'    => 'slug',
        'terms'    => sanitize_text_field($_GET[$taxonomy])
      );
    }
  }
  
  // 絞り込み条件がある場合はクエリに追加
  if (!empty($tax_queries)) {
    if (count($tax_queries) > 1) {
      $tax_queries['relation'] = 'AND'; // 複数条件の場合はANDで結合
    }
    $query->query_vars['tax_query'] = $tax_queries;
  }
}
add_action('pre_get_posts', 'filter_posts_by_custom_taxonomy');




// -----------------------------------------------------
// 管理画面にCSSを反映
// -----------------------------------------------------
function add_my_editor_styles() {
  // ブロックエディタ（Gutenberg）用のCSSを読み込む
  add_theme_support('editor-styles');
  add_editor_style('public/common/css/editor-style.css');

  // クラシックエディタ用のCSSを読み込む
  wp_enqueue_style(
    'editor-style', // ハンドル名
    get_template_directory_uri() . '/public/common/css/editor-style.css'
  );
}
add_action('enqueue_block_editor_assets', 'add_my_editor_styles');
add_action('admin_enqueue_scripts', 'add_my_editor_styles');



// -----------------------------------------------------
// アイキャッチ注意テキスト ※クラシックエディタのみ
// -----------------------------------------------------
// function add_featured_image_instruction( $content ) {
//   return $content .= '<p>推奨サイズは幅：300px、高さ：200px</p>';
// }
// add_filter( 'admin_post_thumbnail_html', 'add_featured_image_instruction' );



// -----------------------------------------------------
// ダッシュボードにオリジナルウィジェットを追加
// -----------------------------------------------------
// add_action('wp_dashboard_setup', 'my_dashboard_widgets');
// function my_dashboard_widgets() {
//   wp_add_dashboard_widget('my_theme_options_widget', 'オリジナルウィジェット', 'my_dashboard_widget_function');
// }
// // ダッシュボードのオリジナルウィジェット内に情報を掲載
// function my_dashboard_widget_function() {
// // 管理画面用HTML
// echo '<ul class="custom_widget">
//   <li><a href="post-new.php"><span class="dashicons dashicons-edit"></span><span>新しく記事を書く</span></a></li>
//   <li><a href="edit.php"><span class="dashicons dashicons-list-view"></span><span>過去記事一覧</span></a></li>
//   <li><a href="edit.php?post_type=page"><span class="dashicons dashicons-clipboard"></span><span>固定ページ編集</span></a></li>
//   </ul>';
// }
// // ダッシュボードにスタイルシートを読み込む
// function custom_admin_enqueue(){
//      wp_enqueue_style( 'custom_admin_enqueue', get_stylesheet_directory_uri(). '/my-widgets.css' );
// }
// add_action( 'admin_enqueue_scripts', 'custom_admin_enqueue' );