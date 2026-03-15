<?php
namespace Baizy\Widgets;

if ( ! defined( 'ABSPATH' ) ) exit;

class CustomHtmlWidget extends \WP_Widget {

    public function __construct() {
        parent::__construct(
            'custom_html_widget',
            __( 'カスタムHTML（プレビュー付き）', 'textdomain' ),
            [
                'description'                 => __( '自由にHTMLを記述でき、リアルタイムプレビュー機能付きのウィジェット', 'textdomain' ),
                'customize_selective_refresh' => true,
            ]
        );
    }

    public function widget( $args, $instance ): void {
        $title        = apply_filters( 'widget_title', $instance['title'] ?? '', $instance, $this->id_base );
        $html_content = $instance['html_content'] ?? '';

        echo $args['before_widget'];
        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        echo do_shortcode( $html_content );
        echo $args['after_widget'];
    }

    public function form( $instance ): void {
        $title        = $instance['title']        ?? '';
        $html_content = $instance['html_content'] ?? '';
        ?>
<div class="custom-html-widget-form">
    <p>
        <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'タイトル:', 'textdomain' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <p>
        <label for="<?php echo $this->get_field_id( 'html_content' ); ?>"><?php esc_html_e( 'HTMLコンテンツ:', 'textdomain' ); ?></label>
        <textarea class="widefat html-content-textarea" id="<?php echo $this->get_field_id( 'html_content' ); ?>" name="<?php echo $this->get_field_name( 'html_content' ); ?>" rows="10" style="font-family: monospace;"><?php echo esc_textarea( $html_content ); ?></textarea>
    </p>
    <div class="html-preview-container">
        <p><strong><?php esc_html_e( 'プレビュー:', 'textdomain' ); ?></strong></p>
        <div class="html-preview" style="border: 1px solid #ddd; padding: 10px; background: #f9f9f9; min-height: 50px;">
            <?php echo do_shortcode( $html_content ); ?>
        </div>
        <p><small><?php esc_html_e( 'HTMLを編集すると、リアルタイムでプレビューが更新されます。', 'textdomain' ); ?></small></p>
    </div>
</div>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#<?php echo $this->get_field_id( 'html_content' ); ?>').on('input', function() {
        $(this).closest('.custom-html-widget-form').find('.html-preview').html( $(this).val() );
    });
});
</script>
        <?php
    }

    public function update( $new_instance, $old_instance ): array {
        return [
            'title'        => sanitize_text_field( $new_instance['title']        ?? '' ),
            'html_content' => wp_kses_post(        $new_instance['html_content'] ?? '' ),
        ];
    }
}
