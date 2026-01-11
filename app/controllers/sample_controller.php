<?php
if ( !defined( 'ABSPATH' ) ) exit;

// ターム一覧設定
$post_id = get_the_ID();
$terms = display_terms_of_post('sample-category', $post_id);

// 記事に属するターム一覧表示設定
$now_terms = display_terms_of_post('sample-category');

// カスタムフィールド取得sample
// $fields = get_fields();
// $field1 = $fields['text01'];
// $text02 = $fields['text02'];
?>