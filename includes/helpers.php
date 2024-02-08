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
}
