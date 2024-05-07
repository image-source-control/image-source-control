<?php

namespace ISC\Settings\Sections;

use ISC\Settings;

/**
 * Handle caption/overlay settings
 */
class Caption extends Settings\Section {

	/**
	 * Position of image's caption
	 *
	 * @var array available positions for the image source overlay.
	 */
	protected $caption_position = [
		'top-left',
		'top-center',
		'top-right',
		'center',
		'bottom-left',
		'bottom-center',
		'bottom-right',
	];

	/**
	 * Add settings section
	 */
	public function add_settings_section() {

		// source in caption
		add_settings_section( 'isc_settings_section_overlay', __( 'Overlay', 'image-source-control-isc' ), '__return_false', 'isc_settings_page' );
		add_settings_field( 'source_type_overlay', __( 'Enable', 'image-source-control-isc' ), [ $this, 'render_field_source_type_overlay' ], 'isc_settings_page', 'isc_settings_section_overlay' );
		add_settings_field( 'source_overlay', __( 'Overlay prefix', 'image-source-control-isc' ), [ $this, 'render_field_overlay_text' ], 'isc_settings_page', 'isc_settings_section_overlay' );
		add_settings_field( 'overlay_style', __( 'Layout', 'image-source-control-isc' ), [ $this, 'render_field_overlay_style' ], 'isc_settings_page', 'isc_settings_section_overlay' );
		add_settings_field( 'overlay_included_images', __( 'Included images', 'image-source-control-isc' ), [ $this, 'render_field_overlay_included_images' ], 'isc_settings_page', 'isc_settings_section_overlay' );
	}

	/**
	 * Render the option to enable Overlays
	 */
	public function render_field_source_type_overlay() {
		$options = $this->get_isc_options();
		require_once ISCPATH . '/admin/templates/settings/caption/enable.php';
	}

	/**
	 * Render option for the text preceding the source.
	 */
	public function render_field_overlay_text() {
		$options = $this->get_isc_options();
		require_once ISCPATH . '/admin/templates/settings/caption/overlay-text.php';
	}

	/**
	 * Render option for the style of the overlay
	 */
	public function render_field_overlay_style() {
		$options               = $this->get_isc_options();
		$caption_style         = $this->get_caption_style();
		$caption_style_options = $this->get_caption_style_options();
		require_once ISCPATH . '/admin/templates/settings/caption/style.php';
		require_once ISCPATH . '/admin/templates/settings/caption/overlay-position.php';
	}

	/**
	 * Render option to define which images should show the overlay
	 */
	public function render_field_overlay_included_images() {
		$options                 = $this->get_isc_options();
		$included_images         = ! empty( $options['overlay_included_images'] ) ? $options['overlay_included_images'] : '';
		$included_images_options = $this->get_included_images_options();
		require_once ISCPATH . '/admin/templates/settings/caption/overlay-included-images.php';
		$checked_advanced_options = ! empty( $options['overlay_included_advanced'] ) && is_array( $options['overlay_included_advanced'] ) ? $options['overlay_included_advanced'] : [];
		$advanced_options         = $this->get_advanced_included_images_options();
		require_once ISCPATH . '/admin/templates/settings/caption/overlay-advanced-included-images.php';
	}

	/**
	 * Get the selected caption style option
	 *
	 * @return string
	 */
	public function get_caption_style(): string {
		$options = $this->get_isc_options();
		return ! empty( $options['caption_style'] ) ? $options['caption_style'] : 'fulltext';
	}

	/**
	 * Get the options for the caption style
	 */
	public function get_caption_style_options() {
		$caption_style_options = [
			'fulltext' => [
				'label' => __( 'Full credit line', 'image-source-control-isc' ),
				'value' => 'fulltext',
			],
			'hover'    => [
				'label'  => __( 'Prefix extends on hover', 'image-source-control-isc' ),
				'value'  => 'hover',
				'is_pro' => true,
			],
			'click'    => [
				'label'  => __( 'Prefix extends on click', 'image-source-control-isc' ),
				'value'  => 'click',
				'is_pro' => true,
			],
			'none'     => [
				'label'       => __( 'Unstyled credit line below the image', 'image-source-control-isc' ),
				'description' => __( 'Deliver the overlay content without any markup and style.', 'image-source-control-isc' ) . ' ' .
					sprintf(
					// translators: %s is "<code>captions.js</code>"
						__( 'Removes also %s.', 'image-source-control-isc' ),
						'<code>captions.js</code>'
					),
				'value'       => 'none',
			],
		];

		return apply_filters( 'isc_caption_style_options', $caption_style_options );
	}

	/**
	 * Get the options for images that get an overlay with the source
	 */
	public function get_included_images_options() {
		$included_images_options = [
			'default'  => [
				'label'       => __( 'Images in the content', 'image-source-control-isc' ),
				'description' => sprintf(
				// translators: %1$s is "img" and %2$s stands for "the_content" wrapped in "code" tags
					__( 'Technically: %1$s tags within %2$s.', 'image-source-control-isc' ),
					'<code>img</code>',
					'<code>the_content</code>'
				),
				'value'       => '',
				'coming_soon' => false,
			],
			'body_img' => [
				'label'       => __( 'Images on the whole page', 'image-source-control-isc' ),
				'description' =>
					__( 'Including featured image, header, sidebar, and footer.', 'image-source-control-isc' ) . ' ' .
					sprintf(
					// translators: %1$s is "img" and %2$s stands for "body" wrapped in "code" tags
						__( 'Technically: %1$s tags within %2$s.', 'image-source-control-isc' ),
						'<code>img</code>',
						'<code>body</code>'
					),
				'value'       => 'body_img',
				'is_pro'      => true,
			],
		];

		return apply_filters( 'isc_overlay_included_images_options', $included_images_options );
	}

	/**
	 * Get the advanced options for images that get an overlay with the source
	 * These will be checkboxes. One can enable multiple options at once.
	 */
	public function get_advanced_included_images_options() {
		$options = [
			'inline_style_data' => [
				'label'  => __( 'Load the overlay text for inline styles', 'image-source-control-isc' ),
				'value'  => 'inline_style_data',
				'is_pro' => true,
			],
			'inline_style_show' => [
				'label'  => __( 'Display the overlay within HTML tags that use inline styles', 'image-source-control-isc' ),
				'value'  => 'inline_style_show',
				'is_pro' => true,
			],
			'style_block_data'  => [
				'label'  => sprintf(
					// translators: %s is "<code>style</code>"
					__( 'Load the overlay text for %s tags', 'image-source-control-isc' ),
					'<code>style</code>'
				),
				'value'  => 'style_block_data',
				'is_pro' => true,
			],
			'style_block_show'  => [
				'label'  => sprintf(
					// translators: %s is "<code>style</code>"
					__( 'Display the overlay after %s tags', 'image-source-control-isc' ),
					'<code>style</code>'
				),
				'value'  => 'style_block_show',
				'is_pro' => true,
			],
		];

		// push Avada Builder option as the first option
		if ( defined( 'FUSION_BUILDER_VERSION' ) ) {
			$avada_builder_option = [
				'label'  => 'Avada Builder: ' . __( 'Display the overlay text for background images', 'image-source-control-isc' ),
				'value'  => 'avada_background_overlay',
				'is_pro' => true,
			];
			array_unshift( $options, $avada_builder_option );
		}

		// push WP Bakery option as the first option
		if ( defined( 'WPB_VC_VERSION' ) ) {
			$wp_bakery_option = [
				'label'  => 'WP Bakery: ' . __( 'Display the overlay text for background images', 'image-source-control-isc' )
					. '. ' . sprintf(
						'<a href="%s" target="_blank">%s</a>',
						'https://imagesourcecontrol.com/documentation/compatibility/#WPBakery_Page_Builder',
						__( 'Manual', 'image-source-control-isc' )
					),
				'value'  => 'wp_bakery_background_overlay',
				'is_pro' => true,
			];
			array_unshift( $options, $wp_bakery_option );
		}

		return apply_filters( 'isc_overlay_advanced_included_images_options', $options );
	}

	/**
	 * Validate settings
	 *
	 * @param array $output output data.
	 * @param array $input  input data.
	 *
	 * @return array $output
	 */
	public function validate_settings( array $output, array $input ): array {
		if ( isset( $input['caption_position'] ) && in_array( $input['caption_position'], $this->caption_position, true ) ) {
			$output['caption_position'] = $input['caption_position'];
		}
		$output['caption_style'] = ! empty( $input['caption_style'] ) ? $input['caption_style'] : null;
		if ( isset( $input['source_pretext'] ) ) {
			$output['source_pretext'] = esc_textarea( $input['source_pretext'] );
		}

		$output['overlay_included_images']   = isset( $input['overlay_included_images'] ) ? esc_attr( $input['overlay_included_images'] ) : '';
		$output['overlay_included_advanced'] = isset( $input['overlay_included_advanced'] ) && is_array( $input['overlay_included_advanced'] ) ? $input['overlay_included_advanced'] : [];

		return $output;
	}
}
