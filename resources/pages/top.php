<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$news = \Baizy\Models\PostModel::get_latest_news();
?>

<!-- トップページのHTMLをここに記述 -->

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
