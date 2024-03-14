<?php

namespace ISC;

use ISC_Class;

/**
 * Main renderer class
 */
class Renderer {
	/**
	 * ISC options
	 *
	 * @var array
	 */
	protected static $options = null;

	/**
	 * Get the ISC options
	 *
	 * @return array
	 */
	protected static function get_options(): ?array {
		if ( self::$options === null ) {
			self::$options = ISC_Class::get_instance()->get_isc_options();
		}
		return self::$options;
	}
}
