<?php
if ( !defined( 'ABSPATH' ) ) exit;
/**
 * Title: sample02
 * Slug: baizy/sample02
 * Categories: sample
 * Keywords: sample
 * Description: カラムサンプルです
 * Block Types: core/image, core/heading, core/paragraph
 */
?>
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide">
    <!-- wp:column -->
    <div class="wp-block-column">
        <!-- wp:heading {"level":2} -->
        <h2>サービス01</h2>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        最初のサービスの説明文がここに入ります。
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->

    <!-- wp:column -->
    <div class="wp-block-column">
        <!-- wp:heading {"level":2} -->
        <h2>サービス02</h2>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        二番目のサービスの説明文がここに入ります。
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->

    <!-- wp:column -->
    <div class="wp-block-column">
        <!-- wp:heading {"level":2} -->
        <h2>サービス03</h2>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        三番目のサービスの説明文がここに入ります。
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
</div>
<!-- /wp:columns -->