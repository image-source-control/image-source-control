<?php

namespace ISC;

/**
 * Class to check media types
 */
class Media_Type_Checker {

	/**
	 * Check if an attachment is an image
	 *
	 * @param int $attachment_id The attachment ID to check.
	 *
	 * @return bool True if the attachment is an image, false otherwise.
	 */
	public static function is_image( int $attachment_id ): bool {
		$mime_type = get_post_mime_type( $attachment_id );
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
	 * @param int $attachment_id The attachment ID to check.
	 *
	 * @return bool True if we should process this attachment, false otherwise.
	 */
	public static function should_process_attachment( int $attachment_id ): bool {
		// If images_only is enabled, only process images
		if ( self::enabled_images_only_option() ) {
			return self::is_image( $attachment_id );
		}

		// Otherwise process all attachments
		return true;
	}
}
