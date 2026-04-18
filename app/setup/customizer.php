<?php
namespace Baizy\Setup;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class Customizer {

	public function __construct() {
		add_action( 'customize_register', array( $this, 'register_customizer' ) );
	}

	public function register_customizer( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section(
			'baizy_custom_tags',
			array(
				'title'       => __( 'タグ追加', 'baizy' ),
				'priority'    => 30,
				'description' => __( 'headタグやbodyタグにカスタムコードを追加します。', 'baizy' ),
			)
		);

		// head 上部
		$wp_customize->add_setting(
			'baizy_head_top_code',
			array(
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_code' ),
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'baizy_head_top_code',
			array(
				'label'       => __( 'head上部', 'baizy' ),
				'description' => __( '<head>タグの直後に追加されるコードです。Google Analytics、メタタグなどを追加できます。', 'baizy' ),
				'section'     => 'baizy_custom_tags',
				'type'        => 'textarea',
				'input_attrs' => array(
					'placeholder' => __( '例: <meta name="description" content="サイトの説明">', 'baizy' ),
					'rows'        => 10,
				),
			)
		);

		// body 上部
		$wp_customize->add_setting(
			'baizy_body_top_code',
			array(
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_code' ),
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'baizy_body_top_code',
			array(
				'label'       => __( 'body上部', 'baizy' ),
				'description' => __( '<body>タグの直後に追加されるコードです。Google Tag Manager、トラッキングコードなどを追加できます。', 'baizy' ),
				'section'     => 'baizy_custom_tags',
				'type'        => 'textarea',
				'input_attrs' => array(
					'placeholder' => __( '例: <!-- Google Tag Manager -->', 'baizy' ),
					'rows'        => 10,
				),
			)
		);
	}

	public function sanitize_code( string $input ): string {
		if ( empty( $input ) ) {
			return '';
		}
		// head/body へのコード埋め込み用に必要最小限のタグのみ許可（管理者専用機能）
		$allowed = array(
			'script'   => array(
				'type'        => true,
				'src'         => true,
				'async'       => true,
				'defer'       => true,
				'crossorigin' => true,
				'integrity'   => true,
				'id'          => true,
			),
			'style'    => array(
				'type'  => true,
				'media' => true,
				'id'    => true,
			),
			'meta'     => array(
				'name'       => true,
				'content'    => true,
				'property'   => true,
				'charset'    => true,
				'http-equiv' => true,
			),
			'link'     => array(
				'rel'         => true,
				'href'        => true,
				'type'        => true,
				'media'       => true,
				'sizes'       => true,
				'crossorigin' => true,
				'integrity'   => true,
			),
			'noscript' => array(),
			'iframe'   => array(
				'src'             => true,
				'height'          => true,
				'width'           => true,
				'frameborder'     => true,
				'style'           => true,
				'allowfullscreen' => true,
			),
		);
		return wp_kses( $input, $allowed );
	}
}
