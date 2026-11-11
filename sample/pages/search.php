<?php
if ( !defined( 'ABSPATH' ) ) exit;
?>


<?php
// 検索フォーム（定義は app/functions/search.php。引数でカスタマイズ可能）
baizy_custom_search_form([
    'placeholder' => 'ブログ内検索',
    'button_text' => '検索する',
    'form_class' => 'header-search-form',
]);
?>
