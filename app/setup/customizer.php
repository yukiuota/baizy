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
				'sanitize_callback' => array( $this, 'unslash_code' ),
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
				'sanitize_callback' => array( $this, 'unslash_code' ),
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

	public function unslash_code( string $input ): string {
		// 管理者（edit_theme_options 権限）専用設定のため意図的にサニタイズしない。
		// GTM 等の script タグをそのまま出力する必要があり、wp_kses はタグ内コンテンツを破壊する。
		return wp_unslash( $input );
	}
}
