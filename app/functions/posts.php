<?php
if ( !defined( 'ABSPATH' ) ) exit;

// ----------------------------------------------------- //
// カスタム投稿、タクソノミー設定
// ----------------------------------------------------- //

// -----------------------------------------------------
// カスタム投稿タイプを追加
// -----------------------------------------------------
add_action('init', 'create_post_type');

function create_post_type()
{
  register_post_type(
    'news', //投稿
    array(
      'label' => 'ニュース', //ラベル
      'public' => true,
      'has_archive' => true,
      'show_in_rest' => true,
      'menu_position' => 5,
      'supports' => array(
        'title',
        'editor',
        'thumbnail',
        'revisions',
      ),

      // -- 初期表示をカスタマイズ --
      'template' => array(
        // 見出し
        array('core/heading', array(
          'level' => 2,
          'placeholder' => 'ニュースタイトルを入力',
        )),
        // カラムブロック（2カラム）とその中身
        array('core/columns', array(
          'columns' => 2
        ), array(
          // 1つ目のカラム内のブロック
          array('core/column', array(), array(
            array('core/image', array())
          )),
          // 2つ目のカラム内のブロック
          array('core/column', array(), array(
            array('core/paragraph', array(
              'placeholder' => '説明文を入力'
            ))
          ))
        ))
      ),
      'template_lock' => 'false', // 'all'で固定、'insert'で追加のみ許可、'false'で制限なし
      // -- /初期表示をカスタマイズ --
    )
  );

  register_taxonomy(
    'news-category',
    'news',
    array(
      'label' => 'カテゴリー', //ラベル名
      'hierarchical' => true,
      'public' => true,
      'show_in_rest' => true,
    )
  );

  register_taxonomy(
    'news-tag',
    'news',
    array(
      'label' => 'タグ', //タクソノミー
      'hierarchical' => false,
      'public' => true,
      'show_in_rest' => true,
      'update_count_callback' => '_update_post_term_count',
    )
  );
}



/* ---------- 管理画面カスタム投稿非表示 ---------- */
function remove_post_function()
{
  // remove_post_type_support('post', 'comments'); // コメント
  remove_post_type_support('post', 'post-formats'); // 投稿フォーマット
  // remove_post_type_support( '〇〇', 'thumbnail' ); // アイキャッチ
}
add_action('init', 'remove_post_function');



// -----------------------------------------------------
// アーカイブ：ページネーション
// -----------------------------------------------------
function custom_pagination() {
  global $wp_query;
  $big = 999999999; // need an unlikely integer

  $pagination_links = paginate_links( array(
      'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
      'format'    => '?paged=%#%',
      'current'   => max( 1, get_query_var('paged') ),
      'total'     => $wp_query->max_num_pages,
      'mid_size'  => 2,
      'end_size'  => 1,
      'prev_text' => __('prev', 'textdomain'), // 前のページリンクのテキスト
      'next_text' => __('next', 'textdomain'), // 次のページリンクのテキスト
      'type'      => 'array',
  ) );

  if ( is_array( $pagination_links ) ) {
      echo '<div id="js-pagination" class="pagination">';
      foreach ( $pagination_links as $link ) {
          // アクティブページのリンクに専用classを追加
          if ( strpos( $link, 'current' ) !== false ) {
              echo '<span aria-current="page" class="current">' . $link . '</span>';
          } else {
              echo str_replace('<a', '<a class="cp_pagenum"', $link);
          }
      }
      echo '</div>';
  }
}


// -----------------------------------------------------
// single：ページャー
// -----------------------------------------------------
function display_prev_next_post_links() {
  $prev_post = get_previous_post();
  $next_post = get_next_post();
  if ($prev_post) {
      echo '<a href="' . get_permalink($prev_post->ID) . '">Prev</a>';
  }
  if ($next_post) {
      echo '<a href="' . get_permalink($next_post->ID) . '">Next</a>';
  }
}




// -----------------------------------------------------
// ターム別年月表示
// -----------------------------------------------------
function custom_taxonomy_monthly_list($post_type, $taxonomy_slug, $post_id)
{
  // 現在表示されている投稿のIDを取得
  $terms = get_the_terms($post_id, $taxonomy_slug);

  // タームがあれば、ターム名を出力する
  if ($terms && !is_wp_error($terms)) {
    foreach ($terms as $term) {
      $term_slug = $term->slug;

      $home_url = esc_url( home_url() );
      $args = array(
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'tax_query' => array(
          array(
            'taxonomy' => $taxonomy_slug,
            'field' => 'slug',
            'terms' => $term_slug,
          )
        )
      );
      $prev_month = null; // 初期値として null をセット
      $the_query = new WP_Query($args);
      if ($the_query->have_posts()) {
        echo '<ul>';
        while ($the_query->have_posts()) {
          $the_query->the_post();
          $this_month = get_the_date('m'); // 投稿の月を取得
          $this_year = get_the_date('Y'); // 投稿の年を取得
          $this_month_name = get_the_date('F'); // 投稿の月の名称を取得

          // 年と月がともに前回と異なる場合のみ表示
          if ($prev_month != $this_month || $prev_year != $this_year) {
            echo '<li>';
            echo '<a href="' . $home_url . '/date/' . $this_year . '/' . $this_month . '?' . $taxonomy_slug . '=' . $term_slug . '">';
            echo '<p>' . $this_year . '.' . $this_month . '</p>';
            echo '</a>';
            echo '</li>';
          }

          // 変数を更新して次のループへ
          $prev_month = $this_month;
          $prev_year = $this_year;
        }
        echo '</ul>';
        wp_reset_postdata();
      }
    }
  }
}


// <?php 関数を使用する際に必要なパラメーターを指定して呼び出します
// $post_id = get_the_ID();
// custom_taxonomy_monthly_list('tyoka', 'tyoka_category', $post_id);





// -----------------------------------------------------
// 記事が属するタームを表示
// -----------------------------------------------------
function display_terms_of_post($taxonomy)
{
  // タームを取得
  $terms = get_the_terms(get_the_ID(), $taxonomy);
  if ($terms && !is_wp_error($terms)) :
    foreach ($terms as $term) {
      echo $term->name;
    }
  endif;
}
// <?php display_terms_of_post('your_taxonomy_name'); 使用例

// 記事が属するタームを背景色付きで表示
function display_terms_of_post_with_color($taxonomy, $wrapper_tag = 'span', $separator = ' ')
{
  // タームを取得
  $terms = get_the_terms(get_the_ID(), $taxonomy);
  if ($terms && !is_wp_error($terms)) :
    $term_list = array();
    foreach ($terms as $term) {
      $bg_color = get_term_background_color($term->term_id);
      $style = 'style="background-color: ' . esc_attr($bg_color) . ';"';
      $term_list[] = '<' . $wrapper_tag . ' class="term-item term-' . esc_attr($term->slug) . '" ' . $style . '>' . esc_html($term->name) . '</' . $wrapper_tag . '>';
    }
    echo implode($separator, $term_list);
  endif;
}
// <?php display_terms_of_post_with_color('news-cat', 'span', ' '); 使用例





// -----------------------------------------------------
// 記事が属するタームスラッグを表示
// -----------------------------------------------------
function display_terms_of_slug($taxonomy)
{
  // タームを取得
  $terms = get_the_terms(get_the_ID(), $taxonomy);
  if ($terms && !is_wp_error($terms)) :
    foreach ($terms as $term) {
      echo $term->slug;
    }
  endif;
}
// <?php display_terms_of_slug('your_taxonomy_name'); 使用例






// -----------------------------------------------------
// タームリスト表示
// -----------------------------------------------------
function get_term_list($taxonomy, $use_background_color = false) {
    global $wp_query;
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => true,
    ));

    if (!is_wp_error($terms) && !empty($terms)) {
        $cat_list = '';
        
        // 現在のタームIDを取得
        $current_term_id = 0;
        if (is_tax($taxonomy)) {
            $queried_object = get_queried_object();
            if ($queried_object && isset($queried_object->term_id)) {
                $current_term_id = $queried_object->term_id;
            }
        } elseif (is_singular()) {
            $post_terms = get_the_terms(get_the_ID(), $taxonomy);
            if (!is_wp_error($post_terms) && !empty($post_terms)) {
                $current_term_id = $post_terms[0]->term_id;
            }
        }

        foreach ($terms as $term) {
            $term_link = get_term_link($term);
            $active_class = ($term->term_id == $current_term_id) ? ' active' : '';
            $slug_class = ' ' . esc_attr($term->slug); // タームスラッグをクラスとして追加
            
            // 背景色の適用は$use_background_colorがtrueの場合のみ
            $style = '';
            if ($use_background_color) {
                $bg_color = get_term_background_color($term->term_id);
                $style = 'style="background-color: ' . esc_attr($bg_color) . ';"';
            }
            
            $cat_list .= '<li class="' . $active_class . $slug_class . '" ' . $style . '><a href="' . esc_url($term_link) . '">' . esc_html($term->name) . '</a></li>';
        }
        echo $cat_list;
    }
}
// <?php get_term_list("タクソノミー名"); 通常の使用例
// <?php get_term_list("タクソノミー名", true); 背景色付きの使用例

// 背景色付きタームリスト表示（専用関数）
function get_term_list_with_color($taxonomy) {
    get_term_list($taxonomy, true);
}
// <?php get_term_list_with_color("タクソノミー名"); 背景色付き専用関数の使用例


// -----------------------------------------------------
// アイキャッチ設定設定
// -----------------------------------------------------
add_theme_support('post-thumbnails');

// アイキャッチサイズ
// add_image_size( 'post-thumb', 750, 500, true );


// -----------------------------------------------------
// カスタム投稿ごとにアーカイブページで表示する記事数を変更
// -----------------------------------------------------
function custom_posts_per_page($query) {
    if (!is_admin() && $query->is_main_query()) {
        if ($query->is_post_type_archive('news')) { // カスタム投稿タイプ
            $query->set('posts_per_page', 10); // 表示する記事数
        }
    }
}
add_action('pre_get_posts', 'custom_posts_per_page');






// -----------------------------------------------------
// 省略記号を変更（the_excerpt()のカスタマイズ）
// -----------------------------------------------------
add_filter( 'excerpt_more', function( $more ){
  return '';
}, 999 );

// 文字数制限を110から200に変更
add_filter( 'excerpt_length', function( $length ){
  return 120;
}, 999 );






// -----------------------------------------------------
// the_excerpt()を改行対応にする
// -----------------------------------------------------
function custom_excerpt_with_linebreaks( $excerpt ) {
    // 改行文字を<br>タグに変換
    $excerpt = nl2br( $excerpt );
    return $excerpt;
}
add_filter( 'get_the_excerpt', 'custom_excerpt_with_linebreaks', 999 );

// 抜粋でwpautopを無効化して改行を保持
function disable_wpautop_on_excerpt( $excerpt ) {
    // wpautopを一時的に無効化
    remove_filter( 'the_excerpt', 'wpautop' );
    return $excerpt;
}
add_filter( 'get_the_excerpt', 'disable_wpautop_on_excerpt', 1 );

// 自動生成される抜粋でも改行を保持
function custom_wp_trim_excerpt( $text, $raw_excerpt ) {
    if ( '' == $raw_excerpt ) {
        $text = get_the_content('');
        $text = strip_shortcodes( $text );
        
        // wpautopを適用する前に改行を保護
        $text = preg_replace('/<br\s*\/?>/i', '|||LINEBREAK|||', $text);
$text = apply_filters( 'the_content', $text );
$text = str_replace(']]>', ']]&gt;', $text);

// HTMLタグを削除するが、改行は保持
$text = wp_strip_all_tags( $text, true );

// 改行文字を一時的に特殊文字に変換
$text = str_replace( array( "\r\n", "\r", "\n" ), '|||LINEBREAK|||', $text );

$excerpt_length = apply_filters( 'excerpt_length', 100 );
$excerpt_more = apply_filters( 'excerpt_more', '' );
$text = wp_trim_words( $text, $excerpt_length, $excerpt_more );

// 特殊文字を<br>タグに戻す
$text = str_replace( '|||LINEBREAK|||', '<br>', $text );
} else {
// 手動で設定された抜粋の場合も改行を保持
$text = nl2br( $raw_excerpt );
}
return $text;
}
add_filter( 'wp_trim_excerpt', 'custom_wp_trim_excerpt', 10, 2 );

// 抜粋表示時にwpautopを無効化
remove_filter( 'the_excerpt', 'wpautop' );






// -----------------------------------------------------
// オリジナルタクソノミーのデフォルトタームを設定
// -----------------------------------------------------
function set_default_news_category($post_id, $post, $update) {
// news投稿タイプのみ対象
if ($post->post_type !== 'news') {
return;
}

// 投稿が公開状態または下書き状態の場合のみ処理
if (!in_array($post->post_status, array('publish', 'draft', 'pending', 'future'))) {
return;
}

// 自動保存や リビジョンは除外
if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
return;
}

// 現在のタームを取得
$current_terms = wp_get_object_terms($post_id, 'news_category');

// タームが設定されていない、またはエラーの場合のみデフォルトを設定
if (empty($current_terms) || is_wp_error($current_terms)) {
// デフォルトタームのスラッグを指定
$default_term = 'notice'; // ここにデフォルトにしたいタームのスラッグを入力

// タームが存在するかチェック
$term = get_term_by('slug', $default_term, 'news_category');
if ($term) {
wp_set_object_terms($post_id, $term->term_id, 'news_category');
}
}
}
add_action('wp_insert_post', 'set_default_news_category', 10, 3);

// -----------------------------------------------------
// オリジナルタクソノミーのデフォルトタームを作成（存在しない場合）
// -----------------------------------------------------
function create_default_news_category() {
// デフォルトタームが存在しない場合は作成
$default_term_slug = 'notice';
$default_term_name = 'お知らせ';

if (!term_exists($default_term_slug, 'news_category')) {
wp_insert_term(
$default_term_name,
'news_category',
array(
'slug' => $default_term_slug,
'description' => 'デフォルトのお知らせカテゴリー'
)
);
}
}
add_action('init', 'create_default_news_category', 20);

// -----------------------------------------------------
// タームに背景色設定機能を追加
// -----------------------------------------------------

// 管理画面でカラーピッカーのスタイルとスクリプトを読み込み
function enqueue_color_picker_assets($hook_suffix) {
// タクソノミー編集ページでのみ読み込み
if ($hook_suffix === 'edit-tags.php' || $hook_suffix === 'term.php') {
wp_enqueue_style('wp-color-picker');
wp_enqueue_script('wp-color-picker');
wp_enqueue_script('term-color-picker', get_template_directory_uri() . '/include/js/term-color-picker.js', array('wp-color-picker'), '1.0.0', true);
}
}
add_action('admin_enqueue_scripts', 'enqueue_color_picker_assets');

// タクソノミー新規追加フォームにカラーピッカーフィールドを追加
function add_term_color_field() {
?>
<div class="form-field">
    <label for="term_bg_color">背景色</label>
    <input type="text" name="term_bg_color" id="term_bg_color" value="#ffffff" class="color-picker" />
    <p class="description">このタームの背景色を選択してください。</p>
</div>
<?php
}
add_action('news-cat_add_form_fields', 'add_term_color_field');

// タクソノミー編集フォームにカラーピッカーフィールドを追加
function edit_term_color_field($term) {
    $bg_color = get_term_meta($term->term_id, 'term_bg_color', true);
    if (empty($bg_color)) {
        $bg_color = '#ffffff';
    }
    ?>
<tr class="form-field">
    <th scope="row">
        <label for="term_bg_color">背景色</label>
    </th>
    <td>
        <input type="text" name="term_bg_color" id="term_bg_color" value="<?php echo esc_attr($bg_color); ?>" class="color-picker" />
        <p class="description">このタームの背景色を選択してください。</p>
    </td>
</tr>
<?php
}
add_action('news-cat_edit_form_fields', 'edit_term_color_field');

// ターム保存時にカラー値を保存
function save_term_color_field($term_id) {
    if (isset($_POST['term_bg_color']) && !empty($_POST['term_bg_color'])) {
        update_term_meta($term_id, 'term_bg_color', sanitize_hex_color($_POST['term_bg_color']));
    }
}
add_action('created_news-cat', 'save_term_color_field');
add_action('edited_news-cat', 'save_term_color_field');

// タームの背景色を取得する関数
function get_term_background_color($term_id) {
    $bg_color = get_term_meta($term_id, 'term_bg_color', true);
    return !empty($bg_color) ? $bg_color : '#ffffff';
}

// タームの背景色をCSSスタイルとして出力する関数
function get_term_background_style($term_id) {
    $bg_color = get_term_background_color($term_id);
    return 'style="background-color: ' . esc_attr($bg_color) . ';"';
}