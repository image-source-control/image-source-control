<?php

namespace ISC;

use ISC_Class;

/**
 * Render the standard source
 */
class Standard_Source {

	/**
	 * Get the ISC options
	 *
	 * @return array
	 */
	protected static function get_options(): ?array {
		return ISC_Class::get_instance()->get_isc_options();
	}

	/**
	 * Get the standard source text as set up under Settings > Standard Source > Custom text
	 * if there was no input, yet
	 *
	 * @return string
	 */
	public static function get_standard_source_text(): string {
		$options = self::get_options();

		if ( ! empty( $options['standard_source_text'] ) ) {
			return $options['standard_source_text'];
		} elseif ( isset( $options['by_author_text'] ) ) {
			return $options['by_author_text'];
		} else {
			return sprintf( '© %s', get_home_url() );
		}
	}

	/**
	 * Return the standard source string for a given image if standard source is used for it
	 *
	 * @param int $attachment_id attachment ID.
	 * @return string
	 */
	public static function get_standard_source_text_for_attachment( int $attachment_id ): string {

		if ( self::standard_source_is( 'author_name' ) ) {
			$author = get_post_field( 'post_author', $attachment_id );
			$source = ! empty( $author ) ? get_the_author_meta( 'display_name', $author ) : '';
		} else {
			$source = self::get_standard_source_text();
		}

		/**
		 * Filter the standard source text
		 *
		 * @param string $source standard source text.
		 * @param int    $attachment_id attachment ID.
		 */
		return apply_filters( 'isc_standard_source_text_for_attachment', $source, $attachment_id );
	}

	/**
	 * Verify the standard source option
	 *
	 * @param string $value value of the [standard_source] option.
	 *
	 * @return bool whether $value is identical to the standard source option or not.
	 */
	public static function standard_source_is( string $value ): bool {
		$options = self::get_options();

		if ( isset( $options['standard_source'] ) ) {
			return $options['standard_source'] === $value;
		}

		/**
		 * 2.0 moved the options to handle "own images" into "standard sources" and only offers a single choice for one of the options now
		 * this section maps old to new settings
		 */
		if ( ! empty( $options['exclude_own_images'] ) ) {
			return 'exclude' === $value;
		} elseif ( ! empty( $options['use_authorname'] ) ) {
			return 'author_name' === $value;
		}

		return false;
	}

	/**
	 * Get the standard source setting
	 *
	 * @return string
	 */
	public static function get_standard_source() {
		$options = self::get_options();

		// options since 2.0
		if ( ! empty( $options['standard_source'] ) ) {
			return $options['standard_source'];
		}

		/**
		 * 2.0 moved the options to handle "own images" into "standard sources" and only offers a single choice for one of the options now
		 * this section maps old to new settings
		 */
		if ( ! empty( $options['exclude_own_images'] ) ) {
			return 'exclude';
		} elseif ( ! empty( $options['use_authorname'] ) ) {
			return 'author_name';
		} elseif ( ! empty( $options['by_author_text'] ) ) {
			return 'custom_text';
		}

		return '';
	}

	/**
	 * Get the label of the standard source label
	 *
	 * @param string|null $value optional value, if missing, will use the stored value.
	 *
	 * @return string
	 */
	public static function get_standard_source_label( string $value = null ) {
		$labels = [
			'exclude'     => __( 'Exclude from lists', 'image-source-control-isc' ),
			'author_name' => __( 'Author name', 'image-source-control-isc' ),
			'wp_caption'  => __( 'Caption', 'image-source-control-isc' ),
			'custom_text' => __( 'Custom text', 'image-source-control-isc' ),
			'iptc'        => __( 'IPTC meta data', 'image-source-control-isc' ),
		];

		if ( ! $value ) {
			$value = self::get_standard_source();
		}

		return $labels[ $value ] ?? $value;
	}

	/**
	 * Check if the given attachment ought to use the standard source
	 *
	 * @param int $attachment_id attachment ID.
	 *
	 * @return bool true if standard source is used
	 */
	public static function use_standard_source( int $attachment_id ): bool {

		return (bool) apply_filters(
			'isc_use_standard_source_for_attachment',
			get_post_meta( $attachment_id, 'isc_image_source_own', true ),
			$attachment_id
		);
	}

	/**
	 * Return true if the standard source is hidden for the given attachment
	 * this is the case if the standard source is set to "exclude" and the attachment uses the standard source
	 *
	 * @param int $attachment_id attachment ID.
	 * @return bool
	 */
	public static function hide_standard_source_for_image( int $attachment_id ): bool {
		return self::standard_source_is( 'exclude' ) && self::use_standard_source( $attachment_id );
	}
}