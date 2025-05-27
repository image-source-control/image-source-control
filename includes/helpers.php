<?php

namespace ISC;

/**
 * Helper functions
 */
class Helpers {
	/**
	 * Helper function to search a value recursively in a multidimensional array.
	 *
	 * @param mixed $needle   The value to search for.
	 * @param array $haystack The array to search in.
	 *
	 * @return bool True if $needle is found in $haystack, false otherwise.
	 */
	public static function is_value_in_multidimensional_array( $needle, $haystack ) {
		if ( in_array( $needle, $haystack, true ) ) {
			return true;
		}
		foreach ( $haystack as $element ) {
			if ( is_array( $element ) && self::is_value_in_multidimensional_array( $needle, $element ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Securely unserialize a WordPress option
	 * a copy of WP core’s maybe_unserialize() function with additional code to prevent object injection
	 * See https://www.tenable.com/security/research/tra-2023-7 about the risks of object injections
	 *
	 * @param string $data value to unserialize.
	 *
	 * @return mixed Unserialized data can be any type.
	 */
	public static function maybe_unserialize( $data ) {
		if ( is_serialized( $data ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize, WordPress.PHP.NoSilencedErrors.Discouraged -- unserialize() is the only way to unserialize WP options
			return @unserialize( trim( $data ), [ 'allowed_classes' => false ] );
		}

		return $data;
	}

	/**
	 * Wrapper for wp_enqueue_script() to handle various script dependencies
	 *
	 * @param string $handle     The script handle.
	 * @param string $src        The relative script source path. The base URL will be added dynamically.
	 * @param array  $deps       An array of script handles this script depends on.
	 * @param bool   $in_footer   Whether to enqueue the script in the footer.
	 *
	 * @return void
	 */
	public static function enqueue_script( string $handle, string $src, array $deps = [], bool $in_footer = true ) {
		wp_enqueue_script( $handle, self::get_script_url( $src ), $deps, ISCVERSION, $in_footer );
	}

	/**
	 * Wrapper for wp_register_script() to handle various script dependencies
	 *
	 * @param string $handle     The script handle.
	 * @param string $src        The relative script source path. The base URL will be added dynamically.
	 * @param array  $deps       An array of script handles this script depends on.
	 * @param bool   $in_footer   Whether to enqueue the script in the footer.
	 *
	 * @return void
	 */
	public static function register_script( string $handle, string $src, array $deps = [], bool $in_footer = true ) {
		wp_register_script( $handle, self::get_script_url( $src ), $deps, ISCVERSION, $in_footer );
	}

	/**
	 * Get script URL
	 *
	 * @param string $src The relative script source path.
	 *
	 * @return string The full URL to the script, with the base URL prepended and minified if SCRIPT_DEBUG is off.
	 */
	public static function get_script_url( string $src ): string {
		// load the minified script version if SCRIPT_DEBUG is off
		if ( ! SCRIPT_DEBUG && strpos( $src, '.min.js' ) === false ) {
			$src = str_replace( '.js', '.min.js', $src );
		}

		// fix slashes
		return trailingslashit( ISCBASEURL ) . ltrim( $src, '/' );
	}
}
