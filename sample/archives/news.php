<?php if ( !defined( 'ABSPATH' ) ) exit; ?>

<?php // -- ajax archive sample -- ?>
<div class="archive-container">
    <?php 
    if (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        if ($term) {
            echo esc_html($term->name);
        }
    }
    ?>
    <?php get_term_list("news-cat"); ?>
    <div class="archive-posts">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <ul>
            <li><?php echo do_shortcode('[uk_category_list taxonomy="news-category"]'); ?>
                <a href="<?php the_permalink(); ?>">
                    <?php the_title(); ?>
                </a>
            </li>
        </ul>
        <?php endwhile; else : ?>
            <p>記事がありません。</p>
        <?php endif; ?>
    </div>

    <div id="js-pagination" class="pagination">
        <?php custom_ajax_pagination(); ?>
    </div>
</div>

<script>
document.body.setAttribute('data-post-type', '<?php echo get_post_type(); ?>');
</script>

<?php // -- /ajax archive sample -- ?>