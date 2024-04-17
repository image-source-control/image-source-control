<?php

namespace ISC\Settings\Sections;

use ISC\Settings;

/**
 * Handle settings for licenses
 */
class Licenses extends Settings\Section {

	/**
	 * Add settings section
	 */
	public function add_settings_section() {
		add_settings_section( 'isc_settings_section_licenses', __( 'Image licenses', 'image-source-control-isc' ), '__return_false', 'isc_settings_page' );
		add_settings_field( 'enable_licences', __( 'Enable', 'image-source-control-isc' ), [ $this, 'render_field_enable_licences' ], 'isc_settings_page', 'isc_settings_section_licenses' );
		add_settings_field( 'licences', __( 'List of licenses', 'image-source-control-isc' ), [ $this, 'render_field_licences' ], 'isc_settings_page', 'isc_settings_section_licenses' );
	}

	/**
	 * Render option to enable the license settings.
	 */
	public function render_field_enable_licences() {
		$options = $this->get_isc_options();
		require_once ISCPATH . '/admin/templates/settings/licenses/enable.php';
	}

	/**
	 * Render option to define the available licenses
	 */
	public function render_field_licences() {
		$options = $this->get_isc_options();

		// fall back to default if field is empty
		if ( empty( $options['licences'] ) ) {
			// retrieve default options
			$default = \ISC_Class::get_instance()->default_options();
			if ( ! empty( $default['licences'] ) ) {
				$options['licences'] = $default['licences'];
			}
		}

		require_once ISCPATH . '/admin/templates/settings/licenses/licenses.php';
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
		$output['enable_licences'] = ! empty( $input['enable_licences'] );

		if ( isset( $input['licences'] ) ) {
			$output['licences'] = esc_textarea( $input['licences'] );
		} else {
			$output['licences'] = false;
		}

		return $output;
	}
}
