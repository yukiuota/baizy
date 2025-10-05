<?php
/**
 * Archive Template
 * 
 * @package baizy
 */

get_header();

// Controller に処理を渡す
$controller = new App\Controllers\PageController();
$data = $controller->archive();

// ビューの読み込み
get_template_part('resources/views/archives/content', null, $data);

get_footer();
