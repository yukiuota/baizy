<?php
namespace App\Controllers;

class PageController {
    public static function init() {
        add_action('wp_head', function() {
            echo "<!-- PageController init -->";
        });
    }

    /**
     * Single Post Controller
     * 
     * @return array
     */
    public function single() {
        return array(
            'post' => get_post(),
        );
    }

    /**
     * Page Controller
     * 
     * @return array
     */
    public function page() {
        return array(
            'page' => get_post(),
        );
    }

    /**
     * Archive Controller
     * 
     * @return array
     */
    public function archive() {
        global $wp_query;
        return array(
            'posts' => $wp_query->posts,
            'total' => $wp_query->found_posts,
        );
    }
}