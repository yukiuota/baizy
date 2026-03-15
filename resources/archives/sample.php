<?php if ( !defined( 'ABSPATH' ) ) exit; ?>

<?php

require get_template_directory() . '/app/controllers/sample_controller.php';
?>


<p>ターム一覧表示サンプル</p>
<?php if (!empty($terms)): ?>
<ul>
    <?php foreach ($terms as $term): ?>
    <li>
        <a href="<?php echo esc_url($term['link']); ?>" class="term--<?php echo esc_attr($term['slug']); ?>">
            <?php echo esc_html($term['name']); ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>


<p>ターム一覧表示サンプル（背景色付き）</p>
<?php
if (!empty($terms)):
foreach ($terms as $term):
$bg_color = get_term_background_color($term['term_id']);
?>
<span style="background-color: <?php echo esc_attr($bg_color); ?>;">
    <?php echo esc_html($term['name']); ?>
</span>
<?php
endforeach;
endif;
?>





<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<a href="<?php the_permalink(); ?>">
    <p>記事に属するターム一覧表示</p>
    <?php foreach ($now_terms as $term): ?>
    <p><?php echo esc_html($term['name']); ?></p>
    <?php endforeach; ?>
    <?php the_title(); ?>
</a>

<?php endwhile; else : ?>
<p>記事がありません。</p>
<?php endif; ?>

<?php custom_pagination(); ?>