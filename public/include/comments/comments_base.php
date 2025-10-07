<?php if ( !defined( 'ABSPATH' ) ) exit; ?>

<?php if ( isset($_GET['comment_posted']) && $_GET['comment_posted'] == '1' ) : ?>
<p class="comment-thanks">コメントありがとうございました。</p>
<?php else: ?>
<?php
    comment_form( array(
        'fields' => array(
            'author'  => '',
            'email'   => '',
            'url'     => '',
            'cookies' => ''
        ),
        'title_reply'          => 'コメントを書く',
        'comment_field'        => '<p class="comment-form-comment"><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>',
        'comment_notes_before' => '',
        'comment_notes_after'  => '',
        'label_submit'         => '送信する'
    ) );
    ?>
<?php endif; ?>