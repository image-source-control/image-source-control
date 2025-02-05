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

	/**
	 * Return true if the mentioned module is enabled
	 *
	 * @param string $module module name.
	 *
	 * @return bool
	 */
	public static function is_module_enabled( string $module ) {
		$options = Options::get_options();

		// all modules are enabled by default; i.e., when the option is not set
		if ( ! isset( $options['modules'] ) ) {
			return true;
		}

		return ( is_array( $options['modules'] ) && in_array( $module, $options['modules'], true ) );
	}
}
