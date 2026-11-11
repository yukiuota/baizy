<?php
if ( !defined( 'ABSPATH' ) ) exit;

// 【サンプル】コメント欄つきの投稿詳細
// 表示するデータは Controller で組み立て、ルーター（resources/layouts/index.php）から $args で受け取る
// 書き方の見本: app/controllers/sample_controller.php / resources/pages/sample_mvc.php
$custom_text = $args['custom_text'] ?? '';
?>

<?php while ( have_posts() ) : the_post(); ?>
<main id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

    <?php if ( $custom_text ) : ?>
    <div class="custom-text">
        <p><?php echo esc_html( $custom_text ); ?></p>
    </div>
    <?php endif; ?>

    <?php // コメント表示
    if ( comments_open() || get_comments_number() ) {
        comments_template();
    }
    ?>

</main>
<?php endwhile; ?>
