<?php

namespace ISC\Settings;

use ISC_Class;

/**
 * Main settings class
 */
class Section extends ISC_Class {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->add_settings_section();

		// validate settings on save
		add_filter( 'isc_settings_on_save_after_validation', [ $this, 'validate_settings' ], 10, 2 );
	}

	/**
	 * Add settings section
	 */
	public function add_settings_section() {
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
		return $output;
	}
}
