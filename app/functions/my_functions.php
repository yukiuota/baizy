<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ----------------------------------------------------- //
// オリジナル関数
// ----------------------------------------------------- //

// -----------------------------------------------------
// テンプレートのimgパスを取得
// -----------------------------------------------------
function tmp_img($path) {
  // imgディレクトリが存在するかチェック
  $img_dir = get_template_directory() . '/public/img/';
  if (!is_dir($img_dir)) {
    // imgディレクトリが存在しない場合は何もしない
    return '';
  }
  
  $full_path = $img_dir . $path;
  if (!file_exists($full_path)) {
    return '';
  }
  
  return esc_url(get_template_directory_uri() . '/public/img/' . $path);
}


// -----------------------------------------------------
// テンプレートのimgの幅・高さを取得
// -----------------------------------------------------
function tmp_img_wh($path) {
  // imgディレクトリが存在するかチェック
  $img_dir = get_template_directory() . '/public/img/';
  if (!is_dir($img_dir)) {
    // imgディレクトリが存在しない場合は何もしない
    return;
  }
  
  $image_url = get_template_directory() . '/public/img/' . $path;

  // ファイルが存在し、かつ画像ファイルかチェック
  if (file_exists($image_url) && is_file($image_url)) {
      // ファイル拡張子を取得
      $file_extension = strtolower(pathinfo($image_url, PATHINFO_EXTENSION));
      
      if ($file_extension === 'svg') {
          // SVGファイルの場合
          $svg_dimensions = tmp_get_svg_dimensions($image_url);
          if ($svg_dimensions) {
              echo 'width="' . intval($svg_dimensions['width']) . '" height="' . intval($svg_dimensions['height']) . '"';
          }
      } else {
          // 通常の画像ファイルの場合
          $dimensions = getimagesize($image_url);
          if ($dimensions && is_array($dimensions) && isset($dimensions[0], $dimensions[1])) {
              echo 'width="' . intval($dimensions[0]) . '" height="' . intval($dimensions[1]) . '"';
          }
      }
  }
}

// -----------------------------------------------------
// SVGの幅・高さを取得
// -----------------------------------------------------
function tmp_get_svg_dimensions($svg_file_path) {
    if (!file_exists($svg_file_path)) {
        return false;
    }
    
    // SVGファイルの内容を読み込み
    $svg_content = file_get_contents($svg_file_path);
    if (!$svg_content) {
        return false;
    }
    
    // XMLとして解析
    $previous_value = libxml_use_internal_errors(true);
    $svg = simplexml_load_string($svg_content);
    libxml_use_internal_errors($previous_value);
    
    if (!$svg) {
        return false;
    }
    
    $width = null;
    $height = null;
    
    // width属性とheight属性を取得
    $attributes = $svg->attributes();
    if (isset($attributes['width'])) {
        $width = (string) $attributes['width'];
    }
    if (isset($attributes['height'])) {
        $height = (string) $attributes['height'];
    }
    
    // viewBox属性からサイズを取得（width/heightが設定されていない場合）
    if ((!$width || !$height) && isset($attributes['viewBox'])) {
        $viewBox = (string) $attributes['viewBox'];
        $viewBoxValues = preg_split('/[\s,]+/', trim($viewBox));
        if (count($viewBoxValues) >= 4) {
            if (!$width) {
                $width = $viewBoxValues[2]; // viewBoxの3番目の値が幅
            }
            if (!$height) {
                $height = $viewBoxValues[3]; // viewBoxの4番目の値が高さ
            }
        }
    }
    
    // 単位を除去して数値のみを取得
    if ($width && $height) {
        $width = preg_replace('/[^0-9.]/', '', $width);
        $height = preg_replace('/[^0-9.]/', '', $height);
        
        if (is_numeric($width) && is_numeric($height)) {
            return [
                'width' => (float) $width,
                'height' => (float) $height
            ];
        }
    }
    
    return false;
}