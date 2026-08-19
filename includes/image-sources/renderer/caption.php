<?php

namespace ISC\Image_Sources\Renderer;

use ISC\Image_Sources\Image_Sources;
use ISC\Image_Sources\Renderer;
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
		$source = Image_Source_String::get( $image_id, $data, $args );
		if ( ! $source ) {
			ISC_Log::log( sprintf( 'skipped overlay for empty sources string for ID "%s"', $image_id ) );
			return '';
		}

		// don’t render the caption for own images if the admin choose not to do so
		if ( Standard_Source::hide_standard_source_for_image( $image_id ) ) {
			ISC_Log::log( sprintf( 'skipped overlay for "own" image ID "%s"', $image_id ) );
			return '';
		}

		$options                       = self::get_options();
		$remove_wrapper_if_source_empty = ! empty( $options['ai_images']['remove_wrapper_if_source_empty'] ) && self::source_is_empty( $image_id, $data );

		// add the prefix if not disabled
		if ( ( ! array_key_exists( 'prefix', $args ) || $args['prefix'] ) && ! $remove_wrapper_if_source_empty ) {
			$source = self::add_prefix( $source );
		}

		// add style wrapper if not disabled
		if ( ! array_key_exists( 'styled', $args ) || $args['styled'] ) {
			$source = self::add_style( $source, $image_id, $remove_wrapper_if_source_empty );
		}

		return $source;
	}

	/**
	 * Add style
	 *
	 * @param string $source Source string.
	 * @param int    $image_id Image ID.
	 * @param bool   $remove_wrapper_if_source_empty Whether wrapper styling should be removed.
	 * @return string
	 */
	public static function add_style( string $source, int $image_id, bool $remove_wrapper_if_source_empty = false ) {
		if ( self::has_caption_style() && apply_filters( 'isc_caption_apply_default_style', '__return_true' ) ) {
			$source = '<span class="isc-source-text' . ( $remove_wrapper_if_source_empty ? ' isc-source-text-empty-source' : '' ) . '">' . $source . '</span>';
		}

		return apply_filters( 'isc_overlay_html_source', $source, $image_id );
	}

	/**
	 * Check whether the source text is empty before AI labels are added.
	 *
	 * @param int      $image_id Image ID.
	 * @param string[] $data     Metadata.
	 *
	 * @return bool
	 */
	private static function source_is_empty( int $image_id, array $data = [] ): bool {
		$source = $data['source'] ?? Image_Sources::get_image_source_text_raw( $image_id );
		$own    = $data['own'] ?? Standard_Source::use_standard_source( $image_id );

		if ( $own && ! Standard_Source::hide_standard_source_for_image( $image_id ) ) {
			$source = Standard_Source::get_standard_source_text_for_attachment( $image_id );
		}

		return '' === $source;
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
		$style = self::get_options()['caption_style'];
		return $style !== 'none';
	}
}
