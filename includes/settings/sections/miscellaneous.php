<?php

namespace ISC\Settings\Sections;

use ISC\Settings;

/**
 * Handle settings for miscellaneous options
 */
class Miscellaneous extends Settings\Section {

	/**
	 * Add settings section
	 */
	public function add_settings_section() {
		// Misc settings group
		add_settings_section( 'isc_settings_section_misc', __( 'Miscellaneous settings', 'image-source-control-isc' ), '__return_false', 'isc_settings_page' );
		add_settings_field( 'standard_source', __( 'Standard source', 'image-source-control-isc' ), [ $this, 'render_field_standard_source' ], 'isc_settings_page', 'isc_settings_section_misc' );
		add_settings_field( 'block_options', __( 'Block options', 'image-source-control-isc' ), [ $this, 'render_field_block_options' ], 'isc_settings_page', 'isc_settings_section_misc' );
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			add_settings_field( 'elementor', 'Elementor', [ $this, 'render_field_elementor' ], 'isc_settings_page', 'isc_settings_section_misc' );
		}
		add_settings_field( 'warning_one_source', __( 'Warn about missing sources', 'image-source-control-isc' ), [ $this, 'render_field_warning_source_missing' ], 'isc_settings_page', 'isc_settings_section_misc' );
		add_settings_field( 'enable_log', __( 'Debug log', 'image-source-control-isc' ), [ $this, 'render_field_enable_log' ], 'isc_settings_page', 'isc_settings_section_misc' );
	}


	/**
	 * Render options for standard image sources
	 */
	public function render_field_standard_source() {
		$standard_source      = \ISC\Standard_Source::get_standard_source();
		$standard_source_text = \ISC\Standard_Source::get_standard_source_text();
		require_once ISCPATH . '/admin/templates/settings/miscellaneous/standard-source.php';

		do_action( 'isc_admin_settings_template_after_standard_source' );
	}

	/**
	 * Render options for block editor support
	 */
	public function render_field_block_options() {
		$options  = $this->get_options();
		$checked  = \ISC_Block_Options::enabled();
		$disabled = apply_filters( 'isc_force_block_options', false );
		require_once ISCPATH . '/admin/templates/settings/miscellaneous/block-options.php';
	}

	/**
	 * Render option for Elementor support
	 */
	public function render_field_elementor() {
		if ( ! \ISC\Plugin::is_pro() ) {
			require_once ISCPATH . '/admin/templates/settings/miscellaneous/elementor.php';
		}

		do_action( 'isc_admin_settings_template_after_elementor' );
	}

	/**
	 * Render the option to display a warning in the admin area if an image source is missing.
	 */
	public function render_field_warning_source_missing() {
		$options = $this->get_options();
		require_once ISCPATH . '/admin/templates/settings/miscellaneous/warn-source-missing.php';
	}

	/**
	 * Render the option to log image source activity in isc.log
	 */
	public function render_field_enable_log() {
		$options      = $this->get_options();
		$checked      = ! empty( $options['enable_log'] );
		$log_file_url = \ISC_Log::get_log_file_url();
		require_once ISCPATH . '/admin/templates/settings/miscellaneous/log-enable.php';
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

		$output['warning_onesource_missing'] = ! empty( $input['warning_onesource_missing'] );

		// remove the debug log file when it was disabled
		if ( isset( $output['enable_log'] ) && ! isset( $input['enable_log'] ) ) {
			\ISC_Log::delete_log_file();
		}
		$output['enable_log']    = ! empty( $input['enable_log'] );
		$output['block_options'] = ! empty( $input['block_options'] );

		/**
		 * 2.0 moved the options to handle "own images" into "standard sources" and only offers a single choice for one of the options now
		 * this section maps old to new settings
		 */
		if ( ! empty( $input['exclude_own_images'] ) ) {
			// don’t show sources for marked images
			$output['standard_source'] = 'exclude';
		} elseif ( ! empty( $input['use_authorname'] ) ) {
			// show author name
			$output['standard_source'] = 'author_name';
		} else {
			$output['standard_source'] = isset( $input['standard_source'] ) ? esc_attr( $input['standard_source'] ) : 'custom_text';
		}

		// custom source text
		if ( isset( $input['by_author_text'] ) ) {
			$output['standard_source_text'] = esc_html( $input['by_author_text'] );
		} else {
			$output['standard_source_text'] = isset( $input['standard_source_text'] ) ? esc_attr( $input['standard_source_text'] ) : \ISC\Standard_Source::get_standard_source_text();
		}

		return $output;
	}
}
