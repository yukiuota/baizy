<?php if ( !defined( 'ABSPATH' ) ) exit;

// 記事に属するターム一覧
$now_terms = display_terms_of_post( 'sample-category' );
?>

<?php while ( have_posts() ) : the_post(); ?>
<main id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

    <p>記事に属するターム一覧表示</p>
    <?php
    foreach ($now_terms as $term):
    // 背景色機能は baizy-term-color プラグイン提供のため、未有効時も動くようにガード
    $bg_color = function_exists('get_term_background_color') ? get_term_background_color($term['term_id']) : '';
    ?>
    <p style="background-color: <?php echo esc_attr($bg_color); ?>;"><?php echo esc_html($term['name']); ?></p>
    <?php endforeach; ?>


    <?php the_title(); ?>
    <?php the_content(); ?>
    <?php display_prev_next_post_links(); ?>
    <?php endwhile; ?>
</main>