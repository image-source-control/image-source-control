<?php

namespace ISC;

/**
 * Plugin class
 */
class Plugin {
	/**
	 * Check if this is the pro version
	 *
	 * @return bool
	 */
	public static function is_pro(): bool {
		return defined( 'ISCPRO' );
	}

	/**
	 * Returns isc_options if it exists, returns the default options otherwise.
	 *
	 * @return array
	 */
	public static function get_options() {
		return Options::get_options();
	}
}
