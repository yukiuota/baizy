<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $post;
?>
<li class="news-list__item">
    <a href="<?php the_permalink(); ?>">
        <time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
            <?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?>
        </time>
        <span><?php the_title(); ?></span>
    </a>
</li>
