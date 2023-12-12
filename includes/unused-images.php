<?php
/**
 * Show unused images
 */
namespace ISC;

class Unused_Images {
	/**
	 * Get all attachments that are not used
	 * We are using a custom query since WP_Query is not flexible enough
	 *
	 * @param array $args arguments for the query.
	 *
	 * @return array|object|stdClass[]|null query results objects or post IDs.
	 */
	public static function get_unused_attachments( array $args = [] ) {
		global $wpdb;

		$offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;
		$limit  = isset( $args['limit'] ) ? (int) $args['limit'] : \ISC_Model::MAX_POSTS;

		/**
		 * Notes on the query:
		 *
		 * if the `isc_image_posts` post meta value is not set or an empty array, the image is not known to ISC.
		 * `_thumbnail_id` is the post meta key for the featured image of a post. Image IDs in here are considered used images
		 *     though we cannot be 100% sure if the theme makes use of them
		 * we are not considering `post_parent` relevant here, since an image might have been uploaded to a post once, but no longer be used in there
		 */
		$query = "SELECT DISTINCT p.*, attachment_meta.meta_value as metadata
		FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'isc_image_posts'
		LEFT JOIN {$wpdb->postmeta} featured ON featured.meta_value = p.ID AND featured.meta_key = '_thumbnail_id'
		LEFT JOIN {$wpdb->postmeta} attachment_meta ON attachment_meta.post_id = p.ID AND attachment_meta.meta_key = '_wp_attachment_metadata'
		WHERE p.post_type = 'attachment'
		AND (pm.meta_value IS NULL OR pm.meta_value = 'a:0:{}')
		AND featured.meta_value IS NULL
		LIMIT $offset, $limit";

		return $wpdb->get_results( $query );
	}

	/**
	 * Calculates the number of files and total filesize of all associated image files from the given metadata array.
	 *
	 * @param string|array $image_metadata The metadata array of the image.
	 * @return int[] analyzed information with [files] and [total_size]
	 */
	public static function analyze_unused_image( $image_metadata ): array {
		$information = [
			'files' => 0,
			'total_size'  => 0,
		];

		if ( empty( $image_metadata ) ) {
			return $information;
		}

		$image_metadata = maybe_unserialize( $image_metadata );

		if ( isset( $image_metadata['filesize'] ) ) {
			$information['files']++;
			$information['total_size'] += (int) $image_metadata['filesize'];
		}

		foreach ( $image_metadata as $value ) {
			if ( is_array( $value ) ) {
				if ( isset( $value['filesize'] ) ) {
					$information['files']++;
					$information['total_size'] += (int) $value['filesize'];
				}

				// If the current value contains nested arrays (e.g., 'sizes'), recursively call the function
				if ( is_array( reset( $value ) ) ) {
					$return = self::analyze_unused_image( $value );
					$information['files'] += (int) $return['files'] ?? 0;
					$information['total_size'] += (int) $return['total_size'] ?? 0;
				}
			}
		}

		return $information;
	}
}