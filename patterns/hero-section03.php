<?php
if ( !defined( 'ABSPATH' ) ) exit;
/**
 * Title: Hero Section03
 * Slug: baizy/hero-section03
 * Categories: baizy-hero
 * Keywords: hero, banner, cover
 * Description: メインビジュアルとキャッチコピーを表示するヒーローセクション（バリエーション3）
 * Block Types: core/cover
 */
?>
<!-- wp:group {"className":"product-detail"} -->
<div class="wp-block-group product-detail">

    <!-- wp:image {"sizeSlug":"large"} /-->

    <!-- wp:heading -->
    <h2>製品名</h2>
    <!-- /wp:heading -->

    <!-- wp:table -->
    <figure class="wp-block-table">
        <table>
            <tbody>
                <tr>
                    <td>サイズ</td>
                    <td>W100 × H200</td>
                </tr>
                <tr>
                    <td>カラー</td>
                    <td>ブラック</td>
                </tr>
            </tbody>
        </table>
    </figure>
    <!-- /wp:table -->

    <!-- wp:paragraph -->
    <p>製品の説明文をここに入力します。</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons -->
    <div class="wp-block-buttons">
        <div class="wp-block-button"><a class="wp-block-button__link">購入する</a></div>
    </div>
    <!-- /wp:buttons -->

</div>
<!-- /wp:group -->