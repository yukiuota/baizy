<?php if ( !defined( 'ABSPATH' ) ) exit;

require_once get_template_directory() . '/app/controllers/news_controller.php';
?>

<?php while ( have_posts() ) : the_post(); ?>
<main id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <?php the_title(); ?>
    <?php the_content(); ?>
    <?php display_prev_next_post_links(); ?>
    <?php endwhile; ?>

    <?php // コメント表示
    if ( comments_open() || get_comments_number() ) {
        comments_template();
    }
    ?>
</main>