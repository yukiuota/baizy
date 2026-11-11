<?php if ( !defined( 'ABSPATH' ) ) exit; ?>

<?php if (have_posts()) : ?>
<ul>
    <?php while (have_posts()) : the_post(); ?>
    <li>
        <a href="<?php the_permalink(); ?>">
            <?php the_title(); ?>
        </a>
    </li>
    <?php endwhile; ?>
</ul>
<?php else : ?>
<p>記事がありません。</p>
<?php endif; ?>

<?php
// 検索フォーム（定義は app/functions/search.php。引数でカスタマイズ可能）
baizy_custom_search_form([
    'placeholder' => 'ブログ内検索',
    'button_text' => '検索する',
    'form_class' => 'header-search-form',
]);
?>

<?php baizy_custom_pagination(); ?>
