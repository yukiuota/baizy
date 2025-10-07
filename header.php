<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<?php if ( is_home() || is_front_page() ) : ?>

<head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# website: http://ogp.me/ns/website#">
    <?php else : ?>

    <head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# article: http://ogp.me/ns/article#">
        <?php endif; ?>
        <?php get_template_part( 'public/include/tags/head_top' ); // head_tag ?>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0" />
        <?php wp_head(); ?>
    </head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <div id="container">
        <?php get_template_part( 'public/include/header/header_base' ); ?>