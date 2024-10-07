<?php

namespace ISC\Settings\Sections;

use ISC\Settings;

/**
 * Handle settings for page-list image source displays
 */
class Global_List extends Settings\Section {

	/**
	 * Add settings section
	 */
	public function add_settings_section() {
		add_settings_section( 'isc_settings_section_complete_list', __( 'Global list', 'image-source-control-isc' ), [ $this, 'render_settings_section' ], 'isc_settings_page' );
		add_settings_field( 'global_list_included_images', __( 'Included images', 'image-source-control-isc' ), [ $this, 'render_field_global_list_included_images' ], 'isc_settings_page', 'isc_settings_section_complete_list' );
		add_settings_field( 'images_per_page_in_list', __( 'Images per page', 'image-source-control-isc' ), [ $this, 'render_field_images_per_page_in_list' ], 'isc_settings_page', 'isc_settings_section_complete_list' );
		add_settings_field( 'global_list_included_data', __( 'Included data', 'image-source-control-isc' ), [ $this, 'render_field_global_list_data' ], 'isc_settings_page', 'isc_settings_section_complete_list' );
	}

	/**
	 * Render the settings section
	 */
	public function render_settings_section() {
		require_once ISCPATH . '/admin/templates/settings/global-list/section.php';
	}

	/**
	 * Render option to define which images should show in the global list
	 */
	public function render_field_global_list_included_images() {
		$options                 = $this->get_isc_options();
		$included_images         = ! empty( $options['global_list_included_images'] ) ? $options['global_list_included_images'] : '';
		$included_images_options = $this->get_included_images_options();
		$indexed_images          = ! empty( $options['global_list_indexed_images'] );
		$is_pro_enabled          = \ISC\Plugin::is_pro();

		require_once ISCPATH . '/admin/templates/settings/global-list/included-images.php';
		require_once ISCPATH . '/admin/templates/settings/global-list/indexed-images.php';
	}

	/**
	 * Render option for the number of images per page in the Global list
	 */
	public function render_field_images_per_page_in_list() {
		$options         = $this->get_isc_options();
		$images_per_page = isset( $options['images_per_page'] ) ? absint( $options['images_per_page'] ) : 99999;
		require_once ISCPATH . '/admin/templates/settings/global-list/images-per-page.php';
	}

	/**
	 * Render option to define which columns show up the global list
	 */
	public function render_field_global_list_data() {
		$options                  = $this->get_isc_options();
		$included_columns         = ! empty( $options['global_list_included_data'] ) ? $options['global_list_included_data'] : [];
		$included_columns_options = $this->get_included_data_options();
		require_once ISCPATH . '/admin/templates/settings/global-list/data.php';
	}

	/**
	 * Render option to display thumbnails in the Global list
	 */
	public function render_field_thumbnail_in_list() {
		$options      = $this->get_isc_options();
		$sizes        = [];
		$sizes_labels = [
			'thumbnail' => _x( 'Thumbnail', 'image size label', 'image-source-control-isc' ),
			'medium'    => _x( 'Medium', 'image size label', 'image-source-control-isc' ),
			'large'     => _x( 'Large', 'image size label', 'image-source-control-isc' ),
			'custom'    => _x( 'Custom', 'image size label', 'image-source-control-isc' ),
		];

		// convert the sizes array to match key and value
		foreach ( $this->thumbnail_size as $_size ) {
			$sizes[ $_size ] = $_size;
		}

		// go through sizes we consider for thumbnails and get their current sizes as set up in WordPress
		$wp_image_sizes = wp_get_registered_image_subsizes();
		if ( is_array( $wp_image_sizes ) ) {
			foreach ( $wp_image_sizes as $_name => $_sizes ) {
				if ( isset( $sizes[ $_name ] ) ) {
					$sizes[ $_name ] = $_sizes;
				}
			}
		}

		require_once ISCPATH . '/admin/templates/settings/global-list/thumbnail-enable.php';
	}


	/**
	 * Get the options for which columns appear in the global list
	 */
	public function get_included_data_options() {
		$included_columns_options = [
			'attachment_id' => [
				'label'  => __( 'Attachment ID', 'image-source-control-isc' ),
				'is_pro' => true,
			],
			'title'         => [
				'label'  => __( 'Title', 'image-source-control-isc' ),
				'is_pro' => true,
			],
			'posts'         => [
				'label'  => __( 'Attached to', 'image-source-control-isc' ),
				'is_pro' => true,
			],
			'source'        => [
				'label'  => __( 'Source', 'image-source-control-isc' ),
				'is_pro' => true,
			],
		];

		return apply_filters( 'isc_global_list_included_data_options', $included_columns_options );
	}


	/**
	 * Get the options for images that appear in the global list
	 */
	public function get_included_images_options() {
		$included_images_options = [
			'in_posts'     => [
				'label'       => __( 'Images in the content', 'image-source-control-isc' ),
				'description' => __( 'Only images that are used within the post and page content.', 'image-source-control-isc' ),
				'value'       => '',
			],
			'all'          => [
				'label'       => __( 'All images', 'image-source-control-isc' ),
				'description' => __( 'All images in the Media library, regardless of whether they are used within the post and page content or not', 'image-source-control-isc' ),
				'value'       => 'all',
				'is_pro'      => false,
			],
			'with_sources' => [
				'label'       => __( 'Images with sources', 'image-source-control-isc' ),
				'description' => __( 'All images in the Media library that have an individual source or use the standard source.', 'image-source-control-isc' ),
				'value'       => 'with_sources',
				'is_pro'      => true,
			],
		];

		return apply_filters( 'isc_global_list_included_images_options', $included_images_options );
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
		$output['images_per_page']             = absint( $input['images_per_page'] );
		$output['global_list_included_images'] = isset( $input['global_list_included_images'] ) ? esc_attr( $input['global_list_included_images'] ) : '';

		if ( ! empty( $input['thumbnail_in_list'] ) ) {
			$output['thumbnail_in_list'] = true;
			if ( in_array( $input['thumbnail_size'], $this->thumbnail_size, true ) ) {
				$output['thumbnail_size'] = $input['thumbnail_size'];
			}
			if ( 'custom' === $input['thumbnail_size'] ) {
				if ( is_numeric( $input['thumbnail_width'] ) ) {
					// Ensures that the value stored in database in a positive integer.
					$output['thumbnail_width'] = absint( round( $input['thumbnail_width'] ) );
				}
				if ( is_numeric( $input['thumbnail_height'] ) ) {
					$output['thumbnail_height'] = absint( round( $input['thumbnail_height'] ) );
				}
			}
		} else {
			$output['thumbnail_in_list'] = false;
		}

		return $output;
	}
}
