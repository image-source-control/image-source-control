<?php

namespace ISC;

/**
 * Count unused images
 */
class Unused_Images {

	/**
	 * Estimate limit
	 */
	const ESTIMATE_LIMIT = 1000;

	/**
	 * Get all attachments that are not used
	 * We are using a custom query since WP_Query is not flexible enough
	 *
	 * @param array $args arguments for the query.
	 *
	 * @return array|object|null query results objects or post IDs.
	 */
	public static function get_unused_attachments( array $args = [] ) {
		global $wpdb;

		$offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;
		$limit  = isset( $args['limit'] ) ? (int) $args['limit'] : self::ESTIMATE_LIMIT;

		/**
		 * Notes on the query:
		 *
		 * If the `isc_image_posts` post meta value is not set or an empty array, the image is not known to ISC.
		 * `_thumbnail_id` is the post meta key for the featured image of a post. Image IDs in here are considered used images
		 *     though we cannot be 100% sure if the theme makes use of them
		 * we are not considering `post_parent` relevant here, since an image might have been uploaded to a post once, but no longer be used in there
		 */
		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.*, attachment_meta.meta_value as metadata
			    FROM {$wpdb->posts} p
			    LEFT JOIN {$wpdb->postmeta} attachment_meta ON attachment_meta.post_id = p.ID AND attachment_meta.meta_key = '_wp_attachment_metadata'
			    WHERE p.post_type = 'attachment'
			    AND NOT EXISTS (
			        SELECT 1 FROM {$wpdb->postmeta} pm
			        WHERE pm.post_id = p.ID
			        AND pm.meta_key = 'isc_image_posts'
			        AND pm.meta_value IS NOT NULL
			        AND pm.meta_value != 'a:0:{}'
			    )
			    AND NOT EXISTS (
			        SELECT 1 FROM {$wpdb->postmeta} featured
			        WHERE featured.meta_value = p.ID
			        AND featured.meta_key = '_thumbnail_id'
			    )
			    LIMIT %d, %d",
				$offset,
				$limit
			)
		);

		return $result;
	}

	/**
	 * Calculates the number of files and total filesize of all associated image files from the given metadata array.
	 *
	 * @param string|array $image_metadata The metadata array of the image.
	 * @return int[] analyzed information with [files] and [total_size]
	 */
	public static function analyze_unused_image( $image_metadata ): array {
		$information = [
			'files'      => 0,
			'total_size' => 0,
		];

		if ( empty( $image_metadata ) ) {
			return $information;
		}

		$image_metadata = Helpers::maybe_unserialize( $image_metadata );

		if ( isset( $image_metadata['file'] ) ) {
			++$information['files'];
		}
		if ( isset( $image_metadata['filesize'] ) ) {
			$information['total_size'] += (int) $image_metadata['filesize'];
		}

		if ( ! array_key_exists( 'sizes', $image_metadata ) || ! is_array( $image_metadata['sizes'] ) ) {
			return $information;
		}

		foreach ( $image_metadata['sizes'] as $value ) {
			if ( isset( $value['file'] ) ) {
				++$information['files'];
			}
			if ( isset( $value['filesize'] ) ) {
				$information['total_size'] += (int) $value['filesize'];
			}

			// If the current value contains nested arrays (e.g., 'sizes'), recursively call the function
			if ( is_array( reset( $value ) ) ) {
				$return                     = self::analyze_unused_image( $value );
				$information['files']      += (int) $return['files'] ?? 0;
				$information['total_size'] += (int) $return['total_size'] ?? 0;
			}
		}

		return $information;
	}

	/**
	 * Retrieves the statistics of unused attachments.
	 *
	 * @return array The statistics of unused attachments with [files], [filesize], and [attachment_count] keys.
	 */
	public static function get_unused_attachment_stats() {
		$existing_stats = get_transient( 'isc-unused-attachments-stats' );
		if ( $existing_stats === false ) {
			$existing_stats = self::calculate_attachment_stats();
			set_transient( 'isc-unused-attachments-stats', $existing_stats, DAY_IN_SECONDS );
		}
		return $existing_stats;
	}

	/**
	 * Calculates the statistics for unused attachments.
	 *
	 * @return array An array containing the attachment count, total number of files, and total file size.
	 */
	public static function calculate_attachment_stats() {
		$attachments = self::get_unused_attachments();
		$files       = 0;
		$filesize    = 0;

		if ( ! count( $attachments ) ) {
			return [];
		}

		foreach ( $attachments as $attachment ) {
			if ( empty( $attachment->metadata ) ) {
				continue;
			}
			$file_info = self::analyze_unused_image( $attachment->metadata );
			$files    += $file_info['files'] ?? 0;
			$filesize += $file_info['total_size'] ?? 0;
		}

		return [
			'attachment_count' => count( $attachments ),
			'files'            => $files,
			'filesize'         => $filesize,
		];
	}
}
