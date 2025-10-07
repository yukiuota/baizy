<?php
if ( !defined( 'ABSPATH' ) ) exit;
/**
 * Title: Hero Section02
 * Slug: baizy/hero-section02
 * Categories: baizy-hero
 * Keywords: hero, banner, cover
 * Description: メインビジュアルとキャッチコピーを表示するヒーローセクション（バリエーション2）
 * Block Types: core/cover
 */
?>

<!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() . '/assets/images/hero.jpg' ); ?>","dimRatio":50,"overlayColor":"black","minHeight":400,"align":"full"} -->
<div class="wp-block-cover alignfull" style="min-height:400px">
    <span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-50"></span>
    <img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/hero.jpg' ); ?>" data-object-fit="cover" />
    <div class="wp-block-cover__inner-container">
        <!-- wp:heading {"textAlign":"center","level":1,"textColor":"white"} -->
        <h1 class="has-text-align-center has-white-color has-text-color">ここにキャッチコピーが入ります</h1>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","textColor":"white"} -->
        <p class="has-text-align-center has-white-color has-text-color">サブテキストや説明文が入ります。クライアントが自由に変更できます。</p>
        <!-- /wp:paragraph -->

        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-buttons">
            <!-- wp:button {"backgroundColor":"primary","textColor":"white"} -->
            <div class="wp-block-button"><a class="wp-block-button__link has-white-color has-primary-background-color has-text-color has-background">詳しく見る</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
</div>
<!-- /wp:cover -->