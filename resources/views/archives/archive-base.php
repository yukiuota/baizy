<?php if ( !defined( 'ABSPATH' ) ) exit; ?>

<?php 
if (is_tax() || is_category() || is_tag()) {
    $term = get_queried_object();
    if ($term) {
        echo esc_html($term->name);
    }
}
?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<ul>
    <li>
        <a href="<?php the_permalink(); ?>">
            <?php the_title(); ?>
        </a>
    </li>
</ul>
<?php endwhile; else : ?>
<p>記事がありません。</p>
<?php endif; ?>

<?php 
// 検索フォーム機能をインクルード
get_template_part('public/include/search/search');

// 引数でカスタマイズすることも可能
custom_search_form([
    'placeholder' => 'ブログ内検索',
    'button_text' => '検索する',
    'form_class' => 'header-search-form',
]);
?>

<?php custom_pagination(); ?>