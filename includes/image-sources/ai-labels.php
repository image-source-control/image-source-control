<?php

namespace ISC\Image_Sources;

/**
 * Handle AI image labels.
 */
class Ai_Labels {
	/**
	 * Meta key for AI labels.
	 */
	const META_KEY = 'isc_ai_label';

	/**
	 * Supported AI image labels.
	 *
	 * @return string[]
	 */
	public static function get_options(): array {
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
	public static function sanitize_value( string $value = '' ): string {
		$value = sanitize_key( $value );

		return array_key_exists( $value, self::get_options() ) ? $value : '';
	}

	/**
	 * Get the inline SVG icon markup for an AI image label.
	 *
	 * @param string $value Selected label.
	 *
	 * @return string
	 */
	public static function get_icon( string $value = '' ): string {
		$value = self::sanitize_value( $value );

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
}
