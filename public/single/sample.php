<?php if ( !defined( 'ABSPATH' ) ) exit;

require_once get_template_directory() . '/app/controllers/sample_controller.php';
?>

<?php while ( have_posts() ) : the_post(); ?>
<main id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

    <p>記事に属するターム一覧表示</p>
    <?php
    foreach ($now_terms as $term):
    $bg_color = get_term_background_color($term['term_id']);
    ?>
    <p style="background-color: <?php echo esc_attr($bg_color); ?>;"><?php echo esc_html($term['name']); ?></p>
    <?php endforeach; ?>


    <?php the_title(); ?>
    <?php the_content(); ?>
    <?php display_prev_next_post_links(); ?>
    <?php endwhile; ?>
</main>