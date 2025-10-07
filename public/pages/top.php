<?php
if ( !defined( 'ABSPATH' ) ) exit;
?>


<?php 
// 検索フォーム機能をインクルード
get_template_part('public/include/search/search');

// 引数でカスタマイズすることも可能
custom_search_form([
    'placeholder' => 'ブログ内検索',
    'button_text' => '検索する',
    'form_class' => 'header-search-form',
]);
?>