<?php
if ( !defined( 'ABSPATH' ) ) exit;

// ----------------------------------------------------- //
// コメントフォームのカスタマイズ
// ----------------------------------------------------- //
// コメント投稿後のリダイレクト先にフラグを追加
function my_comment_redirect( $location ) {
    if ( ! str_contains( $location, 'comment_posted=1' ) ) {
        $location = add_query_arg( 'comment_posted', '1', $location );
    }
    return $location;
}
add_filter( 'comment_post_redirect', 'my_comment_redirect' );