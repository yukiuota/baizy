<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<?php $head_tag = (is_home() || is_front_page()) ? 'website' : 'article'; ?>

<head prefix="og: https://ogp.me/ns# fb: https://ogp.me/ns/fb# <?php echo esc_attr( $head_tag ); ?>: https://ogp.me/ns/<?php echo esc_attr( $head_tag ); ?>#">
    <?php // charset は先頭 1024 バイト以内に置く必要があるため、タグ追加欄（長いコードが入り得る）より前に出力する ?>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <?php baizy_template_part( 'resources/include/tags/head_top' ); // head_tag ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>