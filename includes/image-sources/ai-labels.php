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
	public static function get_labels(): array {
		return [
			'ai'           => 'AI',
			'ai-modified'  => 'AI-modified',
			'ai-generated' => 'AI-generated',
		];
	}

	/**
	 * Sanitize the selected AI image label.
	 *
	 * @param string $label Selected label.
	 *
	 * @return string
	 */
	public static function sanitize_label( string $label = '' ): string {
		$label = sanitize_key( $label );

		return array_key_exists( $label, self::get_labels() ) ? $label : '';
	}

	/**
	 * Get the inline SVG icon markup for an AI image label.
	 *
	 * @param string $label Selected label.
	 *
	 * @return string
	 */
	public static function get_icon( string $label = '' ): string {
		if ( '' === $label || ! array_key_exists( $label, self::get_labels() ) ) {
			return '';
		}

		$icon_path = ISCPATH . '/public/assets/images/ai-images/' . $label . '.svg';

		if ( ! is_readable( $icon_path ) ) {
			return '';
		}

		$icon = file_get_contents( $icon_path );

		if ( ! is_string( $icon ) || '' === $icon ) {
			return '';
		}

		$icon = preg_replace( '/<\?xml[^>]*>\s*/i', '', $icon );
		$icon = preg_replace( '/<svg\b([^>]*)>/', '<svg$1 class="isc-ai-label-icon" aria-hidden="true" focusable="false">', $icon, 1 );

		if ( ! is_string( $icon ) || '' === $icon ) {
			return '';
		}

		return $icon;
	}
}
