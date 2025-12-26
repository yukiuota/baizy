<?php if ( !defined( 'ABSPATH' ) ) exit; ?>

<form method="get" action="<?php echo esc_url( home_url() ); ?>" class="ajax-search">
    <div class="cat01">
        <?php
        $taxonomy = 'cat01'; // タクソノミー1のスラッグを指定
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));

        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                echo '<label>';
                echo '<input type="checkbox" name="cat01" value="' . esc_attr($term->slug) . '">';
                echo esc_html($term->name);
                echo '</label>';
            }
        }
        ?>
    </div>
    <div class="cat02">
        <?php
        $taxonomy = 'cat02'; // タクソノミー2のスラッグを指定
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));

        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                echo '<label>';
                echo '<input type="checkbox" name="cat02" value="' . esc_attr($term->slug) . '">';
                echo esc_html($term->name);
                echo '</label>';
            }
        }
        ?>
    </div>
</form>

<div id="ajax-posts">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <p><a href="<?php echo esc_url(get_permalink()); ?>">
            <?php echo esc_html(get_the_title()); ?>
        </a></p>
    <?php endwhile; endif; ?>
</div>