<?php

namespace ISC\Image_Sources\Renderer;

use ISC\Image_Sources\Renderer;
use ISC\Image_Sources\Image_Sources;
use ISC\Standard_Source;

/**
 * Render the caption.
 */
class Image_Source_String extends Renderer {

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
	 * Render source string of single image by its id
	 *  this only returns the string with source and license (and urls),
	 *  but no wrapping, because the string is used in a lot of functions
	 *  (e.g. image source list where title is prepended)
	 *
	 * @param int|string $image_id   id of the image.
	 * @param string[]   $data metadata.
	 * @param array      $args arguments
	 *                         use "disable-links" = (any value), to disable any working links.
	 *
	 * @return bool|string false if no source was given, else string with source
	 */
	public static function get( $image_id, array $data = [], array $args = [] ) {
		$id = (int) $image_id;

		if ( ! $id ) {
			return false;
		}

		$options             = self::get_options();
		$metadata['source']  = $data['source'] ?? Image_Sources::get_image_source_text_raw( $id );
		$metadata['own']     = $data['own'] ?? Standard_Source::use_standard_source( $id );
		$metadata['licence'] = $data['licence'] ?? Image_Sources::get_image_license( $id );

		if ( ! isset( $args['disable-links'] ) ) {
			$metadata['source_url'] = $data['source_url'] ?? Image_Sources::get_image_source_url( $id );
		} else {
			$metadata['source_url'] = '';
		}

		$source = '';

		if ( $metadata['own'] ) {
			$source = Standard_Source::get_standard_source_text_for_attachment( $id );
		} elseif ( '' !== $metadata['source'] ) {
			$source = $metadata['source'];
		}

		if ( $source === '' ) {
			return false;
		}

		// wrap link around source, if given
		if ( '' !== $metadata['source_url'] ) {
			$classes      = apply_filters( 'isc_public_source_url_html_classes', [], $id, $data, $args, $metadata );
			$class_string = count( $classes ) > 0 ? ' class="' . esc_attr( implode( ' ', $classes ) ) . '"' : '';

			$source = apply_filters(
				'isc_public_source_url_html',
				sprintf( '<a href="%2$s" target="_blank" rel="nofollow"%3$s>%1$s</a>', $source, esc_url_raw( $metadata['source_url'] ), $class_string ),
				$id,
				$metadata
			);
		}

		// add license if enabled
		if ( $options['enable_licences'] && isset( $metadata['licence'] ) && $metadata['licence'] ) {
			$licences = \ISC\Image_Sources\Utils::licences_text_to_array( $options['licences'] );
			if ( ! isset( $args['disable-links'] ) && isset( $licences[ $metadata['licence'] ]['url'] ) ) {
				$licence_url = $licences[ $metadata['licence'] ]['url'];
			}

			if ( isset( $licence_url ) && $licence_url !== '' ) {
				$source = sprintf( '%1$s | <a href="%3$s" target="_blank" rel="nofollow">%2$s</a>', $source, $metadata['licence'], $licence_url );
			} else {
				$source = sprintf( '%1$s | %2$s', $source, $metadata['licence'] );
			}
		}

		return $source;
	}
}
