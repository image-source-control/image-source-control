<?php

namespace ISC\Renderer;

use ISC\Renderer;
use ISC\Standard_Source;
use ISC_Log;

/**
 * Render the caption.
 */
class Caption extends Renderer {

	/**
	 * Main render function that can be called in the frontend.
	 *
	 * @param int $image_id Image ID.
	 */
	public static function render( int $image_id ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::get( $image_id );
	}

	/**
	 * Render caption string / markup
	 * including the style wrapper, if enabled in the plugin settings
	 *
	 * @param int      $image_id id of the image.
	 * @param string[] $data metadata.
	 * @param array    $args additional arguments
	 *                          use "disable-links" = (any value), to disable any working links.
	 *                          use "styled" = false to disable the style wrapper.
	 *
	 * @return string
	 */
	public static function get( int $image_id, array $data = [], array $args = [] ) {
		$source = \ISC_Public::get_instance()->render_image_source_string( $image_id, $data, $args );
		if ( ! $source ) {
			ISC_Log::log( sprintf( 'skipped overlay for empty sources string for ID "%s"', $image_id ) );
			return '';
		}

		// don’t render the caption for own images if the admin choose not to do so
		if ( Standard_Source::hide_standard_source_for_image( $image_id ) ) {
			ISC_Log::log( sprintf( 'skipped overlay for "own" image ID "%s"', $image_id ) );
			return '';
		}

		// add the prefix if not disabled
		if ( ! array_key_exists( 'prefix', $args ) || $args['prefix'] ) {
			$source = self::add_prefix( $source );
		}

		// add style wrapper if not disabled
		if ( ! array_key_exists( 'styled', $args ) || $args['styled'] ) {
			$source = self::add_style( $source, $image_id );
		}

		return $source;
	}

	/**
	 * Add style
	 *
	 * @param string $source Source string.
	 * @param int    $image_id Image ID.
	 * @return string
	 */
	public static function add_style( string $source, int $image_id ) {
		if ( self::has_caption_style() && apply_filters( 'isc_caption_apply_default_style', '__return_true' ) ) {
			$source = '<span class="isc-source-text">' . $source . '</span>';
		}

		return apply_filters( 'isc_overlay_html_source', $source, $image_id );
	}

	/**
	 * Add pre-text
	 *
	 * @param string $source Source string.
	 * @return string
	 */
	public static function add_prefix( $source ) {
		$options = self::get_options();

		if ( empty( $options['source_pretext'] ) ) {
			return $source;
		}

		return $options['source_pretext'] . ' ' . $source;
	}

	/**
	 * Check if the caption has a style in general
	 *
	 * @return bool
	 */
	public static function has_caption_style(): bool {
		$style = \ISC\Settings\Sections\Caption::get_instance()->get_isc_options()['caption_style'];
		return $style !== 'none';
	}
}
