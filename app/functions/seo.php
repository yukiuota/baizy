<?php
if ( !defined( 'ABSPATH' ) ) exit;

// ----------------------------------------------------- //
// SEO設定
// ----------------------------------------------------- //

// -----------------------------------------------------
// body上部タグ埋め込み
// -----------------------------------------------------
function include_body_top() {
  include get_template_directory() . '/public/include/tags/body_top.php';
}
add_action('wp_body_open', 'include_body_top');



// -----------------------------------------------------
// noindex設定
// -----------------------------------------------------
function single_noindex()
{
  if (is_404() || is_singular('xxx') || is_category() || is_tag()) {
    echo '<meta name="robots" content="noindex , nofollow" />';
  }
}
add_action('wp_head', 'single_noindex');



// -----------------------------------------------------
// パンくずリスト関数
// -----------------------------------------------------
function create_breadcrumb()
{

  // wpオブジェクト取得
  $wp_obj = get_queried_object();

  // パンくずのどのページでも変わらない部分を出力
  echo
  '<div class="p-breadcrumb">' .
    '<ul class="p-breadcrumb__lists" itemscope itemtype="http://schema.org/BreadcrumbList">' .
    '<li itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem" class="p-breadcrumb__item">' .
    '<a itemprop="item" href="' . home_url() . '">' .
    '<span itemprop="name">TOP</span>' .
    '</a>' .
    '<meta itemprop="position" content="1">' .
    '</li>';

  // 固定ページ（page-○○.php）
  if (is_page()) {
    echo
    '<li itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem" class="p-breadcrumb__item">' .
      '<a itemprop="item" href="' . home_url($_SERVER["REQUEST_URI"]) . '">' .
      '<span itemprop="name">' . single_post_title('', false) . '</span>' .
      '</a>' .
      '<meta itemprop="position" content="2">' .
      '</li>';
  }

  // カスタム投稿 TOPページ（archive-○○.php）
  if (is_post_type_archive()) {
    echo
    '<li itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem" class="p-breadcrumb__item">' .
      '<a itemprop="item" href="' . home_url($wp_obj->name) . '">' .
      '<span itemprop="name">' . $wp_obj->label . '</span>' .
      '</a>' .
      '<meta itemprop="position" content="2">' .
      '</li>';
  }

  // カスタム投稿 タクソノミー一覧ページ（taxonomy-○○.php）
  if (is_tax()) {
    $post_slug = get_post_type();
    $post_label = get_post_type_object($post_slug)->label;
    echo
    '<li itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem" class="p-breadcrumb__item">' .
      '<a itemprop="item" href="' . home_url($post_slug) . '">' .
      '<span itemprop="name">' . $post_label . '</span>' .
      '</a>' .
      '<meta itemprop="position" content="2">' .
      '</li>' .
      '<li itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem" class="p-breadcrumb__item">' .
      '<a itemprop="item" href="' . home_url($post_slug . '/' . $wp_obj->slug) . '">' .
      '<span itemprop="name">「' . $wp_obj->name . '」カテゴリー一覧</span>' .
      '</a>' .
      '<meta itemprop="position" content="3">' .
      '</li>';
  }

  // カスタム投稿 詳細ページ（single-○○.php）
  if (is_singular() && !is_page()) {
    $post_slug = get_post_type();
    $post_label = get_post_type_object($post_slug)->label;
    $post_id = $wp_obj->ID;
    $post_title = $wp_obj->post_title;
    echo
    '<li itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem" class="p-breadcrumb__item">' .
      '<a itemprop="item" href="' . home_url($post_slug) . '">' .
      '<span itemprop="name">' . $post_label . '</span>' .
      '</a>' .
      '<meta itemprop="position" content="2">' .
      '</li>' .
      '<li itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem" class="p-breadcrumb__item">' .
      '<a itemprop="item" href="' . home_url($post_slug . '/' . $post_id) . '">' .
      '<span itemprop="name">' . $post_title . '</span>' .
      '</a>' .
      '<meta itemprop="position" content="3">' .
      '</li>';
  }

  // 404（404.php）
  if (is_404()) {
    echo
    '<li itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem" class="p-breadcrumb__item">' .
      '<a itemprop="item" href="' . home_url($_SERVER["REQUEST_URI"]) . '">' .
      '<span itemprop="name">404 Not Found</span>' .
      '</a>' .
      '<meta itemprop="position" content="2">' .
      '</li>';
  }

  echo
  '</ul>' .
    '</div>';
}




// -----------------------------------------------------
// feed設定
// -----------------------------------------------------
function mysite_feed_request($vars)
{
  if (isset($vars['feed']) && !isset($vars['post_type'])) {
    $vars['post_type'] = array(
      'news'
    );
  }
  return $vars;
}
add_filter('request', 'mysite_feed_request');





// -----------------------------------------------------
// カスタム投稿SEO設定
// -----------------------------------------------------
function set_custom_post_type_meta_description($post_type_name, $description) {
  global $wp_post_types;

  if (isset($wp_post_types[$post_type_name])) {
      $wp_post_types[$post_type_name]->description = $description;
  }
}

// メタディスクリプションの出力を別関数に分離
function output_custom_post_meta_description() {
    // アーカイブページでのみ実行
    if (is_post_type_archive('news')) {
        $description = 'これはカスタム投稿タイプ「news」のアーカイブページです。';
        echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
    }
}

// initフックの優先度を低く（数値を大きく）して、カスタム投稿タイプ登録後に実行されるようにする
add_action('init', function() {
    set_custom_post_type_meta_description('news', 'これはカスタム投稿タイプ「news」のアーカイブページです。');
}, 20); // 優先度を20に設定

// wp_headフックで出力
add_action('wp_head', 'output_custom_post_meta_description', 1); // 早めに実行





// -----------------------------------------------------
// HTMLをミニファイ化
// -----------------------------------------------------
function minify_html_output($buffer) {
  $search = ['/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s'];
  $replace = ['>', '<', '\\1'];
  return preg_replace($search, $replace, $buffer);
}
function start_html_minify() {
  ob_start('minify_html_output');
}
// add_action('get_header', 'start_html_minify');