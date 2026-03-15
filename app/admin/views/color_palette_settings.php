<?php
/**
 * カラーパレット管理ページのビュー
 *
 * @package baizy
 */

if ( !defined( 'ABSPATH' ) ) exit;
?>

<div class="wrap">
  <h1>カスタムカラーパレット管理</h1>
  <p>エディターで使用できるカラーパレットを管理します。</p>
  
  <form method="post" action="">
    <?php wp_nonce_field('save_custom_colors', 'custom_colors_nonce'); ?>
    
    <div id="color-palette-container">
      <?php
      if (!empty($saved_colors)) {
        foreach ($saved_colors as $index => $color) {
          render_color_row($index, $color);
        }
      } else {
        // 空の場合は1行表示
        render_color_row(0, array('name' => '', 'color' => '#000000'));
      }
      ?>
    </div>
    
    <p>
      <button type="button" id="add-color-btn" class="button">+ カラーを追加</button>
    </p>
    
    <p class="submit">
      <input type="submit" name="save_colors" class="button button-primary" value="保存">
    </p>
  </form>
</div>
