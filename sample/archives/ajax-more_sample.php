<?php if ( !defined( 'ABSPATH' ) ) exit; ?>

<div id="js-post">
    <?php
// カテゴリーページの場合：カテゴリIDを追加
if (is_category()) {
    $category = get_queried_object();
    $args['cat'] = $category->term_id;
}

if (have_posts()) : while (have_posts()) : the_post(); ?>
    <p><a href="<?php echo esc_url(get_permalink()); ?>">
            <?php echo esc_html(get_the_title()); ?>
        </a></p>
    <?php endwhile; endif; ?>
</div>

<button id="js-more" data-category-id="<?php echo get_queried_object_id(); ?>">もっと見る</button>