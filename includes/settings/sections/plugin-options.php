<?php

namespace ISC\Settings\Sections;

use ISC\Settings;

/**
 * Handle settings for general plugin options
 */
class Plugin_Options extends Settings\Section {

	/**
	 * Add settings section
	 */
	public function add_settings_section() {
		// Misc settings group
		add_settings_section( 'isc_settings_section_plugin', __( 'Plugin options', 'image-source-control-isc' ), '__return_false', 'isc_settings_page' );
		add_settings_field( 'modules', __( 'Modules', 'image-source-control-isc' ), [ $this, 'render_field_modules' ], 'isc_settings_page', 'isc_settings_section_plugin' );
		add_settings_field( 'remove_on_uninstall', __( 'Delete data on uninstall', 'image-source-control-isc' ), [ $this, 'render_field_remove_on_uninstall' ], 'isc_settings_page', 'isc_settings_section_plugin' );
	}


	/**
	 * Render options for plugin modules
	 */
	public function render_field_modules() {
		$modules_options = $this->get_modules_options();
		// return keys of $modules_options
		$all_options = array_keys( $modules_options );
		$options     = $this->get_options();
		// all modules are enabled, if none is selected to prevent accidental disabling; especially, after this option was introduced
		$modules_options_selected = isset( $options['modules'] ) && is_array( $options['modules'] ) ? $options['modules'] : $all_options;
		require_once ISCPATH . '/admin/templates/settings/plugin/modules.php';

		do_action( 'isc_admin_settings_template_after_plugin_options' );
	}

	/**
	 * Render the option to remove all options and meta data when the plugin is deleted.
	 */
	public function render_field_remove_on_uninstall() {
		$options = $this->get_options();
		$checked = ! empty( $options['remove_on_uninstall'] );
		require_once ISCPATH . '/admin/templates/settings/plugin/remove-on-uninstall.php';
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

		$output['modules']             = ( isset( $input['modules'] ) && is_array( $input['modules'] ) ) ? $input['modules'] : [];
		$output['remove_on_uninstall'] = ! empty( $input['remove_on_uninstall'] );

		return $output;
	}

	/**
	 * Get the options for modules
	 */
	public function get_modules_options() {
		$modules_options = [
			'image_sources' => [
				'label'       => __( 'Image Sources', 'image-source-control-isc' ),
				'description' => __( 'Manage and display author attributions for images.', 'image-source-control-isc' ),
				'value'       => 'image_sources',
				'coming_soon' => false,
			],
			'unused_images' => [
				'label'       => __( 'Unused Images', 'image-source-control-isc' ),
				'description' => __( 'Identify unused images to clean up your site.', 'image-source-control-isc' ),
				'value'       => 'body_img',
				'is_pro'      => true,
			],
		];

		return apply_filters( 'isc_plugin_options_modules', $modules_options );
	}
}
