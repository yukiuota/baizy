<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * トップページ ビュー
 *
 * データは app/controllers/top_controller.php の TopController::data() から
 * $args で受け取る。このファイルでは取得処理（get_posts / get_field 等）を行わない。
 */

$news = $args['news'] ?? array();
$hero = $args['hero'] ?? array();
?>

<!-- トップページのHTMLをここに記述 -->

<?php if ( ! empty( $hero['title'] ) ) : ?>
<h1 class="hero__title"><?php echo esc_html( $hero['title'] ); ?></h1>
<?php endif; ?>

<?php if ( $news ) : ?>
<ul class="news-list">
<?php foreach ( $news as $post ) :
    setup_postdata( $post );
    baizy_template_part( 'resources/include/components/news/item' );
endforeach;
wp_reset_postdata(); ?>
</ul>
<?php else : ?>
<p>ニュースはありません。</p>
<?php endif; ?>
