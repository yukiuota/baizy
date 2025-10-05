<?php
/**
 * Single Post Template
 * 
 * @package baizy
 */

get_header();

// Controller に処理を渡す
$controller = new App\Controllers\PageController();
$data = $controller->single();

// ビューの読み込み
get_template_part('resources/views/single/content', null, $data);

get_footer();
