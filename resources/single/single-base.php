<?php if ( !defined( 'ABSPATH' ) ) exit; ?>

<?php while ( have_posts() ) : the_post(); ?>
<main id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <?php the_title(); ?>
    <?php the_content(); ?>
    <?php display_prev_next_post_links(); ?>
    <?php endwhile; ?>
</main>