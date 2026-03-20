<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// -----------------------------------------------------
// アーカイブ：ページネーション
// -----------------------------------------------------
function custom_pagination() {
    global $wp_query;
    $big = 999999999;

    $pagination_links = paginate_links( array(
        'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
        'format'    => '?paged=%#%',
        'current'   => max( 1, get_query_var( 'paged' ) ),
        'total'     => $wp_query->max_num_pages,
        'mid_size'  => 2,
        'end_size'  => 1,
        'prev_text' => __( 'prev', 'textdomain' ),
        'next_text' => __( 'next', 'textdomain' ),
        'type'      => 'array',
    ) );

    if ( is_array( $pagination_links ) ) {
        echo '<div id="js-pagination" class="pagination">';
        foreach ( $pagination_links as $link ) {
            if ( strpos( $link, 'current' ) !== false ) {
                echo '<span aria-current="page" class="current">' . wp_kses_post( $link ) . '</span>';
            } else {
                echo wp_kses_post( str_replace( '<a', '<a class="cp_pagenum"', $link ) );
            }
        }
        echo '</div>';
    }
}


// -----------------------------------------------------
// single：ページャー
// -----------------------------------------------------
function display_prev_next_post_links() {
    $prev_post = get_previous_post();
    $next_post = get_next_post();

    if ( $prev_post ) {
        echo '<a href="' . esc_url( get_permalink( $prev_post->ID ) ) . '">Prev</a>';
    }
    if ( $next_post ) {
        echo '<a href="' . esc_url( get_permalink( $next_post->ID ) ) . '">Next</a>';
    }
}
