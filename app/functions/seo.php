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
    '<a itemprop="item" href="' . esc_url(home_url()) . '">' .
    '<span itemprop="name">TOP</span>' .
    '</a>' .
    '<meta itemprop="position" content="1">' .
    '</li>';

  // 固定ページ（page-○○.php）
  if (is_page()) {
    echo
    '<li itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem" class="p-breadcrumb__item">' .
      '<a itemprop="item" href="' . esc_url(home_url($_SERVER["REQUEST_URI"])) . '">' .
      '<span itemprop="name">' . esc_html(single_post_title('', false)) . '</span>' .
      '</a>' .
      '<meta itemprop="position" content="2">' .
      '</li>';
  }

  // カスタム投稿 TOPページ（archive-○○.php）
  if (is_post_type_archive()) {
    echo
    '<li itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem" class="p-breadcrumb__item">' .
      '<a itemprop="item" href="' . esc_url(home_url($wp_obj->name)) . '">' .
      '<span itemprop="name">' . esc_html($wp_obj->label) . '</span>' .
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
      '<a itemprop="item" href="' . esc_url(home_url($post_slug)) . '">' .
      '<span itemprop="name">' . esc_html($post_label) . '</span>' .
      '</a>' .
      '<meta itemprop="position" content="2">' .
      '</li>' .
      '<li itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem" class="p-breadcrumb__item">' .
      '<a itemprop="item" href="' . esc_url(home_url($post_slug . '/' . $wp_obj->slug)) . '">' .
      '<span itemprop="name">「' . esc_html($wp_obj->name) . '」カテゴリー一覧</span>' .
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
    
    // 通常の投稿（post）の場合はアーカイブページを表示しない
    if ($post_slug !== 'post') {
      echo
      '<li itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem" class="p-breadcrumb__item">' .
        '<a itemprop="item" href="' . esc_url(home_url($post_slug)) . '">' .
        '<span itemprop="name">' . esc_html($post_label) . '</span>' .
        '</a>' .
        '<meta itemprop="position" content="2">' .
        '</li>';
    }
    
    // 投稿詳細ページ
    $position = ($post_slug === 'post') ? '2' : '3';
    echo
    '<li itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem" class="p-breadcrumb__item">' .
      '<a itemprop="item" href="' . esc_url(get_permalink($post_id)) . '">' .
      '<span itemprop="name">' . esc_html($post_title) . '</span>' .
      '</a>' .
      '<meta itemprop="position" content="' . $position . '">' .
      '</li>';
  }

  // 404（404.php）
  if (is_404()) {
    echo
    '<li itemscope itemprop="itemListElement" itemtype="http://schema.org/ListItem" class="p-breadcrumb__item">' .
      '<a itemprop="item" href="' . esc_url(home_url($_SERVER["REQUEST_URI"])) . '">' .
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

// メタディスクリプション定数
define('NEWS_ARCHIVE_META_DESCRIPTION', 'これはカスタム投稿タイプ「news」のアーカイブページです。');

/**
 * カスタム投稿タイプにメタディスクリプションを設定
 */
function set_custom_post_type_meta_description($post_type_name, $description) {
    global $wp_post_types;

    if (isset($wp_post_types[$post_type_name])) {
        $wp_post_types[$post_type_name]->description = $description;
    }
}

/**
 * カスタム投稿アーカイブページのメタディスクリプション出力
 */
function output_custom_post_meta_description() {
    if (is_post_type_archive('news')) {
        echo '<meta name="description" content="' . esc_attr(NEWS_ARCHIVE_META_DESCRIPTION) . '" />' . "\n";
    }
}

// カスタム投稿タイプ登録後にメタディスクリプションを設定
add_action('init', function() {
    set_custom_post_type_meta_description('news', NEWS_ARCHIVE_META_DESCRIPTION);
}, 20);

// メタディスクリプションを出力
add_action('wp_head', 'output_custom_post_meta_description', 1);





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