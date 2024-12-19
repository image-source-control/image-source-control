<?php

namespace ISC\Image_Sources;

/**
 * Image Sources Utils
 */
trait Utils {
	/**
	 * Transform the licenses from the options textfield into an array
	 *
	 * @param string $licences text with licenses.
	 * @return array|bool $new_licences array with licenses and license information or false if no array created.
	 */
	public static function licences_text_to_array( $licences = '' ) {
		if ( $licences === '' ) {
			return false;
		}
		// split the text by line
		$licences_array = preg_split( '/\r?\n/', trim( $licences ) );
		if ( count( $licences_array ) === 0 ) {
			return false;
		}
		// create the array with licence => url
		$new_licences = [];
		foreach ( $licences_array as $_licence ) {
			if ( trim( $_licence ) !== '' ) {
				$temp                     = explode( '|', $_licence );
				$new_licences[ $temp[0] ] = [];
				if ( isset( $temp[1] ) ) {
					$new_licences[ $temp[0] ]['url'] = esc_url( $temp[1] );
				}
			}
		}

		if ( $new_licences === [] ) {
			return false;
		} else {
			return $new_licences;
		}
	}
}