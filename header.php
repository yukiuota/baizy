<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<?php $head_tag = (is_home() || is_front_page()) ? 'website' : 'article'; ?>

<head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# <?php echo $head_tag; ?>: http://ogp.me/ns/<?php echo $head_tag; ?>#">
    <?php baizy_template_part( 'public/include/tags/head_top' ); // head_tag ?>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0" />
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>