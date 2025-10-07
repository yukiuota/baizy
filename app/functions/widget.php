<?php
if ( !defined( 'ABSPATH' ) ) exit;


// -----------------------------------------------------
// ウィジェットエリアの登録
// -----------------------------------------------------
function register_theme_widget_areas() {
    // ヘッダーメニュー
    register_sidebar(array(
        'name'          => __('ヘッダーメニュー', 'textdomain'),
        'id'            => 'header-menu',
        'description'   => __('ヘッダーのメニューウィジェットエリア', 'textdomain'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    // フッター（左）
    register_sidebar(array(
        'name'          => __('フッター（左）', 'textdomain'),
        'id'            => 'footer-1',
        'description'   => __('フッター左側のウィジェットエリア', 'textdomain'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));

    // フッター（中央）
    register_sidebar(array(
        'name'          => __('フッター（中央）', 'textdomain'),
        'id'            => 'footer-2',
        'description'   => __('フッター中央のウィジェットエリア', 'textdomain'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));

    // フッター（右）
    register_sidebar(array(
        'name'          => __('フッター（右）', 'textdomain'),
        'id'            => 'footer-3',
        'description'   => __('フッター右側のウィジェットエリア', 'textdomain'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
}
add_action('widgets_init', 'register_theme_widget_areas');

// WordPressメニュー機能の有効化
function register_theme_menus() {
    register_nav_menus(array(
        'header-menu' => __('ヘッダーメニュー', 'textdomain'),
        'footer-menu' => __('フッターメニュー', 'textdomain'),
    ));
}
add_action('init', 'register_theme_menus');

// 出力
// if (is_active_sidebar('sidebar-1')) :
//         <?php dynamic_sidebar('sidebar-1');
// endif;

/**
 * カスタムHTMLウィジェット（プレビュー機能付き）
 */
class Custom_HTML_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'custom_html_widget',
            __('カスタムHTML（プレビュー付き）', 'textdomain'),
            array(
                'description' => __('自由にHTMLを記述でき、リアルタイムプレビュー機能付きのウィジェット', 'textdomain'),
                'customize_selective_refresh' => true,
            )
        );
    }

    /**
     * フロントエンドでのウィジェット表示
     */
    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
        $html_content = !empty( $instance['html_content'] ) ? $instance['html_content'] : '';

        echo $args['before_widget'];

        if ( !empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        // HTMLコンテンツを表示（wpautopを無効化してそのまま出力）
        echo do_shortcode( $html_content );

        echo $args['after_widget'];
    }

    /**
     * 管理画面でのウィジェット設定フォーム
     */
    public function form( $instance ) {
        $title = !empty( $instance['title'] ) ? $instance['title'] : '';
        $html_content = !empty( $instance['html_content'] ) ? $instance['html_content'] : '';
        ?>
<div class="custom-html-widget-form">
    <p>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'タイトル:', 'textdomain' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <p>
        <label for="<?php echo $this->get_field_id( 'html_content' ); ?>"><?php _e( 'HTMLコンテンツ:', 'textdomain' ); ?></label>
        <textarea class="widefat html-content-textarea" id="<?php echo $this->get_field_id( 'html_content' ); ?>" name="<?php echo $this->get_field_name( 'html_content' ); ?>" rows="10" style="font-family: monospace;"><?php echo esc_textarea( $html_content ); ?></textarea>
    </p>

    <!-- プレビューエリア -->
    <div class="html-preview-container">
        <p><strong><?php _e( 'プレビュー:', 'textdomain' ); ?></strong></p>
        <div class="html-preview" style="border: 1px solid #ddd; padding: 10px; background: #f9f9f9; min-height: 50px;">
            <?php echo do_shortcode( $html_content ); ?>
        </div>
        <p><small><?php _e( 'HTMLを編集すると、リアルタイムでプレビューが更新されます。', 'textdomain' ); ?></small></p>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // リアルタイムプレビュー機能
    $('#<?php echo $this->get_field_id( 'html_content' ); ?>').on('input', function() {
        var htmlContent = $(this).val();
        var previewArea = $(this).closest('.custom-html-widget-form').find('.html-preview');

        // HTMLをプレビューエリアに反映
        previewArea.html(htmlContent);
    });
});
</script>
<?php
    }

    /**
     * ウィジェット設定の保存処理
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( !empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        
        // HTMLコンテンツは基本的なサニタイズのみ（wp_kses_postを使用）
        $instance['html_content'] = ( !empty( $new_instance['html_content'] ) ) ? wp_kses_post( $new_instance['html_content'] ) : '';
        
        return $instance;
    }
}

/**
 * ウィジェットを登録
 */
function register_custom_html_widget() {
    register_widget( 'Custom_HTML_Widget' );
}
add_action( 'widgets_init', 'register_custom_html_widget' );

/**
 * 管理画面でのスタイル追加
 */
function custom_html_widget_admin_styles() {
    ?>
<style>
.custom-html-widget-form .html-content-textarea {
    font-family: 'Courier New', Courier, monospace;
    font-size: 12px;
}

.html-preview-container {
    margin-top: 15px;
}

.html-preview {
    border-radius: 3px;
    overflow: auto;
}

.html-preview * {
    max-width: 100%;
}
</style>
<?php
}
add_action( 'admin_head-widgets.php', 'custom_html_widget_admin_styles' );