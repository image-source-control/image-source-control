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
}
