<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$news = ( new Baizy\Controllers\TopController() )->getNews();
?>

<!-- トップページのHTMLをここに記述 -->

<ul class="news-list">
<?php foreach ( $news as $post ) : ?>
    <li class="news-list__item">
        <a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
            <time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $post->ID ) ); ?>">
                <?php echo esc_html( get_the_date( 'Y.m.d', $post->ID ) ); ?>
            </time>
            <span><?php echo esc_html( $post->post_title ); ?></span>
        </a>
    </li>
<?php endforeach; ?>
</ul>
