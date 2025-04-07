<?php

namespace ISC;

/**
 * Class to check media types
 */
class Media_Type_Checker {

	/**
	 * Check if an attachment is an image
	 *
	 * @param int|\WP_Post $attachment Attachment ID or post object.
	 *
	 * @return bool True if the attachment is an image, false otherwise.
	 */
	public static function is_image( $attachment ): bool {
		if ( ! is_int( $attachment ) && ! $attachment instanceof \WP_Post ) {
			return false;
		}

		$mime_type = get_post_mime_type( $attachment );
		if ( ! $mime_type ) {
			return false;
		}

		return strpos( $mime_type, 'image/' ) === 0;
	}

	/**
	 * Check the images-only option
	 *
	 * @return bool True if images-only is enabled, false otherwise.
	 */
	public static function enabled_images_only_option(): bool {
		$options = \ISC\Plugin::get_options();

		// Check if images_only is enabled
		return ! empty( $options['images_only'] );
	}

	/**
	 * Check if we should process this attachment based on settings
	 *
	 * @param int|\WP_Post $attachment Attachment ID or post object.
	 *
	 * @return bool True if we should process this attachment, false otherwise.
	 */
	public static function should_process_attachment( $attachment ): bool {
		// If images_only is enabled, only process images
		if ( self::enabled_images_only_option() ) {
			return self::is_image( $attachment );
		}

		// Otherwise process all attachments
		return true;
	}
}
