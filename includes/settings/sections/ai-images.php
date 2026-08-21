<?php

namespace ISC\Settings\Sections;

use ISC\Settings;

/**
 * Handle settings for AI images.
 */
class Ai_Images extends Settings\Section {

	/**
	 * Add settings section.
	 */
	public function add_settings_section() {
		add_settings_section( 'isc_settings_section_ai_images', __( 'AI Images', 'image-source-control-isc' ), [ $this, 'render_section' ], 'isc_settings_page' );
		add_settings_field( 'enable_ai_labels', __( 'Enable', 'image-source-control-isc' ), [ $this, 'render_field_enable_ai_labels' ], 'isc_settings_page', 'isc_settings_section_ai_images' );
		add_settings_field( 'ai_label_remove_wrapper_if_source_empty', '', [ $this, 'render_field_remove_wrapper_if_source_empty' ], 'isc_settings_page', 'isc_settings_section_ai_images' );
		add_settings_field( 'ai_label_icons', __( 'Icons', 'image-source-control-isc' ), [ $this, 'render_field_ai_label_icons' ], 'isc_settings_page', 'isc_settings_section_ai_images' );
	}

	/**
	 * Render the section description.
	 */
	public function render_section() {
		?><p><?php esc_html_e( 'This is a beta feature. Visible changes are likely.', 'image-source-control-isc' ); ?> <a href="<?php echo esc_url( \ISC\Admin_Utils::get_manual_url( 'settings-ai-images' ) ); ?>" target="_blank"><?php esc_html_e( 'Manual', 'image-source-control-isc' ); ?></a></p><?php
	}

	/**
	 * Render option to enable AI image labels.
	 */
	public function render_field_enable_ai_labels() {
		$options = $this->get_options();
		require_once ISCPATH . '/admin/templates/settings/ai-images/enable.php';
	}

	/**
	 * Render option to remove wrapper styles if no source text exists.
	 */
	public function render_field_remove_wrapper_if_source_empty() {
		$options = $this->get_options();
		require_once ISCPATH . '/admin/templates/settings/ai-images/remove-wrapper-if-source-empty.php';
	}

	/**
	 * Render the available AI image icons.
	 */
	public function render_field_ai_label_icons() {
		require_once ISCPATH . '/admin/templates/settings/ai-images/icons.php';
	}

	/**
	 * Validate settings.
	 *
	 * @param array $output output data.
	 * @param array $input  input data.
	 *
	 * @return array
	 */
	public function validate_settings( array $output, array $input ): array {
		$output['ai_images']                                   = isset( $output['ai_images'] ) && is_array( $output['ai_images'] ) ? $output['ai_images'] : [];
		$output['ai_images']['show_label']                     = ! empty( $input['ai_images']['show_label'] );
		$output['ai_images']['remove_wrapper_if_source_empty'] = ! empty( $input['ai_images']['remove_wrapper_if_source_empty'] );

		return $output;
	}
}
