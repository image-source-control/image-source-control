<?php

namespace ISC\Settings\Sections;

use ISC\Settings;

/**
 * Handle settings for page-list image source displays
 */
class Page_List extends Settings\Section {

	/**
	 * Add settings section
	 */
	public function add_settings_section() {
		add_settings_section( 'isc_settings_section_list_below_content', __( 'Per-page list', 'image-source-control-isc' ), [ $this, 'render_settings_section' ], 'isc_settings_page' );
		add_settings_field( 'source_type_list', __( 'Enable', 'image-source-control-isc' ), [ $this, 'render_field_source_type_list' ], 'isc_settings_page', 'isc_settings_section_list_below_content' );
		add_settings_field( 'image_list_headline', __( 'Headline', 'image-source-control-isc' ), [ $this, 'render_field_list_headline' ], 'isc_settings_page', 'isc_settings_section_list_below_content' );
		add_settings_field( 'below_content_included_images', __( 'Included images', 'image-source-control-isc' ), [ $this, 'render_field_below_content_included_images' ], 'isc_settings_page', 'isc_settings_section_list_below_content' );
	}

	/**
	 * Render the settings section
	 */
	public function render_settings_section() {
		require_once ISCPATH . '/admin/templates/settings/page-list/section.php';
	}

	/**
	 * Render the options to enable the Per-page list
	 */
	public function render_field_source_type_list() {
		$options = $this->get_options();
		require_once ISCPATH . '/admin/templates/settings/page-list/enable.php';
	}

	/**
	 * Render option to define a headline for the image list
	 */
	public function render_field_list_headline() {
		$options = $this->get_options();
		require_once ISCPATH . '/admin/templates/settings/page-list/headline.php';
	}

	/**
	 * Render option to define which images to show on the sources list of the current page
	 */
	public function render_field_below_content_included_images() {
		$options                 = $this->get_options();
		$included_images         = ! empty( $options['list_included_images'] ) ? $options['list_included_images'] : '';
		$included_images_options = $this->get_included_images_options();
		require_once ISCPATH . '/admin/templates/settings/page-list/included-images.php';
	}


	/**
	 * Get the options for included images in the sources list
	 */
	public function get_included_images_options() {
		$included_images_options = [
			'default'   => [
				'label'       => __( 'Images in the content', 'image-source-control-isc' ),
				'description' => sprintf(
				// translators: %1$s is "img" and %2$s stands for "the_content" wrapped in "code" tags
					__( 'Technically: %1$s tags within %2$s and the featured image.', 'image-source-control-isc' ),
					'<code>img</code>',
					'<code>the_content</code>'
				),
				'value'       => '',
				'coming_soon' => false,
			],
			'body_img'  => [
				'label'       => __( 'Images on the whole page', 'image-source-control-isc' ),
				'description' =>
					__( 'Including header, sidebar, and footer.', 'image-source-control-isc' ) . ' ' .
					sprintf(
					// translators: %1$s is "img" and %2$s stands for "body" wrapped in "code" tags
						__( 'Technically: %1$s tags within %2$s.', 'image-source-control-isc' ),
						'<code>img</code>',
						'<code>body</code>'
					),
				'value'       => 'body_img',
				'is_pro'      => true,
			],
			'body_urls' => [
				'label'       => __( 'Any image URL', 'image-source-control-isc' ),
				'description' =>
					__( 'Including CSS background, JavaScript, or HTML attributes.', 'image-source-control-isc' ) . ' ' .
					sprintf(
					// translators: %s stands for "body" wrapped in "code" tags
						__( 'Technically: any image URL found in %s.', 'image-source-control-isc' ),
						'<code>html</code>'
					),
				'value'       => 'body_urls',
				'is_pro'      => true,
			],
		];

		return apply_filters( 'isc_list_included_images_options', $included_images_options );
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
		$output['list_on_archives']     = ! empty( $input['list_on_archives'] );
		$output['list_on_excerpts']     = ! empty( $input['list_on_excerpts'] );
		$output['image_list_headline']  = isset( $input['image_list_headline'] ) ? esc_html( $input['image_list_headline'] ) : '';
		$output['list_included_images'] = isset( $input['list_included_images'] ) ? esc_attr( $input['list_included_images'] ) : '';

		return $output;
	}
}
