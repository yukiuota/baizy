<?php if ( !defined( 'ABSPATH' ) ) exit; ?>
<!-- header -->
<header class="header">
    <div class="header__inner">
        <?php $tag = (is_home() || is_front_page()) ? 'h1' : 'p'; ?>
        <<?php echo $tag; ?> class="header__logo"><a href="<?php echo esc_url( home_url() ); ?>"></a></<?php echo $tag; ?>>

        <!-- header-btn -->
        <div class="header-btn">
            <button id="menu-trigger" class="menu" aria-label="メニュー">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        <!-- /header-btn -->

        <nav id="js-menu">
            <?php //ヘッダーメニューウィジェットエリアの表示
            if (is_active_sidebar('header-menu')) :
                dynamic_sidebar('header-menu');
            endif;
            ?>
        </nav>
    </div>
</header>
<!-- /header -->