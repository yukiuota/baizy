<?php
namespace Baizy\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class CustomHtmlWidget extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'custom_html_widget',
			__( 'カスタムHTML（プレビュー付き）', 'baizy' ),
			array(
				'description'                 => __( '自由にHTMLを記述でき、リアルタイムプレビュー機能付きのウィジェット', 'baizy' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	public function widget( $args, $instance ): void {
		$title        = apply_filters( 'widget_title', $instance['title'] ?? '', $instance, $this->id_base );
		$html_content = $instance['html_content'] ?? '';

		echo wp_kses_post( $args['before_widget'] );
		if ( ! empty( $title ) ) {
			echo wp_kses_post( $args['before_title'] ) . esc_html( $title ) . wp_kses_post( $args['after_title'] );
		}
		echo do_shortcode( $html_content );
		echo wp_kses_post( $args['after_widget'] );
	}

	public function form( $instance ): void {
		$title        = $instance['title'] ?? '';
		$html_content = $instance['html_content'] ?? '';
		?>
<div class="custom-html-widget-form">
	<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'タイトル:', 'baizy' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
	</p>
	<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'html_content' ) ); ?>"><?php esc_html_e( 'HTMLコンテンツ:', 'baizy' ); ?></label>
		<textarea class="widefat html-content-textarea" id="<?php echo esc_attr( $this->get_field_id( 'html_content' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'html_content' ) ); ?>" rows="10" style="font-family: monospace;"><?php echo esc_textarea( $html_content ); ?></textarea>
	</p>
	<div class="html-preview-container">
		<p><strong><?php esc_html_e( 'プレビュー:', 'baizy' ); ?></strong></p>
		<div class="html-preview" style="border: 1px solid #ddd; padding: 10px; background: #f9f9f9; min-height: 50px;">
			<?php echo do_shortcode( $html_content ); ?>
		</div>
		<p><small><?php esc_html_e( 'HTMLを編集すると、リアルタイムでプレビューが更新されます。', 'baizy' ); ?></small></p>
	</div>
</div>
<script>
document.getElementById('<?php echo esc_js( $this->get_field_id( 'html_content' ) ); ?>')?.addEventListener('input', function () {
	const preview = this.closest('.custom-html-widget-form')?.querySelector('.html-preview');
	if (preview) {
		preview.innerHTML = this.value;
	}
});
</script>
		<?php
	}

	public function update( $new_instance, $old_instance ): array {
		return array(
			'title'        => sanitize_text_field( $new_instance['title'] ?? '' ),
			'html_content' => wp_kses_post( $new_instance['html_content'] ?? '' ),
		);
	}
}
