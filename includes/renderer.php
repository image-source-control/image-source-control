<?php

namespace ISC;

use ISC_Class;

/**
 * Main renderer class
 */
class Renderer {

	/**
	 * Get the ISC options
	 *
	 * @return array
	 */
	protected static function get_options(): ?array {
		return ISC_Class::get_instance()->get_isc_options();
	}
}
