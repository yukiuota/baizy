<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// -----------------------------------------------------
// ターム別年月表示
// -----------------------------------------------------
function custom_taxonomy_monthly_list( $post_type, $taxonomy_slug, $post_id ) {
    $terms = get_the_terms( $post_id, $taxonomy_slug );

    if ( $terms && ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            $term_slug  = $term->slug;
            $home_url   = esc_url( home_url() );
            $prev_month = null;
            $prev_year  = null;
            $the_query  = \Baizy\Models\PostModel::getByTaxTerm( $post_type, $taxonomy_slug, $term_slug );

            if ( $the_query->have_posts() ) {
                echo '<ul>';
                while ( $the_query->have_posts() ) {
                    $the_query->the_post();
                    $this_month      = get_the_date( 'm' );
                    $this_year       = get_the_date( 'Y' );
                    $this_month_name = get_the_date( 'F' );

                    if ( $prev_month != $this_month || $prev_year != $this_year ) {
                        echo '<li>';
                        echo '<a href="' . esc_url( $home_url . '/date/' . $this_year . '/' . $this_month . '?' . esc_attr( $taxonomy_slug ) . '=' . esc_attr( $term_slug ) ) . '">';
                        echo '<p>' . esc_html( $this_year . '.' . $this_month ) . '</p>';
                        echo '</a>';
                        echo '</li>';
                    }

                    $prev_month = $this_month;
                    $prev_year  = $this_year;
                }
                echo '</ul>';
                wp_reset_postdata();
            }
        }
    }
}


// -----------------------------------------------------
// 記事が属するタームを取得（配列）
// -----------------------------------------------------
function display_terms_of_post( $taxonomy, $post_id = null ) {
    return \Baizy\Models\TaxonomyModel::getTermsOfPost( $taxonomy, $post_id ?? 0 );
}


// -----------------------------------------------------
// 記事が属するタームスラッグを表示
// -----------------------------------------------------
function display_terms_of_slug( $taxonomy ) {
    $terms = get_the_terms( get_the_ID(), $taxonomy );
    if ( $terms && ! is_wp_error( $terms ) ) :
        foreach ( $terms as $term ) {
            echo esc_html( $term->slug );
        }
    endif;
}
