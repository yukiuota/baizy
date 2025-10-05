<?php
/**
 * Common Helper Functions
 * 
 * @package baizy
 */

if (!function_exists('baizy_asset_url')) {
    /**
     * Get asset URL
     * 
     * @param string $path
     * @return string
     */
    function baizy_asset_url($path) {
        return get_template_directory_uri() . '/public/' . ltrim($path, '/');
    }
}

if (!function_exists('baizy_get_image_url')) {
    /**
     * Get image URL
     * 
     * @param string $filename
     * @return string
     */
    function baizy_get_image_url($filename) {
        return get_template_directory_uri() . '/public/imgs/' . ltrim($filename, '/');
    }
}