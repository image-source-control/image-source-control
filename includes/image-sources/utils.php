<?php

namespace ISC\Image_Sources;

/**
 * Image Sources Utils
 */
class Utils {
	/**
	 * Supported AI image labels.
	 *
	 * @return string[]
	 */
	public static function get_ai_image_options(): array {
		return [
			'ai'           => 'AI',
			'ai-modified'  => 'AI-modified',
			'ai-generated' => 'AI-generated',
		];
	}

	/**
	 * Sanitize the selected AI image label.
	 *
	 * @param string $value Selected label.
	 *
	 * @return string
	 */
	public static function sanitize_ai_image_value( string $value = '' ): string {
		$value = sanitize_key( $value );

		return array_key_exists( $value, self::get_ai_image_options() ) ? $value : '';
	}

	/**
	 * Get the inline SVG icon markup for an AI image label.
	 *
	 * @param string $value Selected label.
	 *
	 * @return string
	 */
	public static function get_ai_image_icon( string $value = '' ): string {
		$value = self::sanitize_ai_image_value( $value );

		if ( '' === $value ) {
			return '';
		}

		$icon_path = ISCPATH . '/public/assets/images/ai-images/' . $value . '.svg';

		if ( ! is_readable( $icon_path ) ) {
			return '';
		}

		$icon = file_get_contents( $icon_path );

		if ( ! is_string( $icon ) || '' === $icon ) {
			return '';
		}

		return $icon;
	}

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