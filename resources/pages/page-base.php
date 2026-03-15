<?php if ( !defined( 'ABSPATH' ) ) exit; ?>

<?php while ( have_posts() ) : the_post(); ?>

<?php the_title(); ?>
<?php the_content(); ?>

<?php endwhile; ?>