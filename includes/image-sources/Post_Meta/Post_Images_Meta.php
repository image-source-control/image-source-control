<?php

namespace ISC\Image_Sources\Post_Meta;

use ISC_Log;
use ISC_Model;

/**
 * Handles the post-images post meta value added to public post types.
 */
class Post_Images_Meta {

	const META_KEY = 'isc_post_images';

	/**
	 * Getter
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array|string The post images meta value, or empty string if not found.
	 */
	public static function get( $post_id ) {
		$value = get_post_meta( $post_id, self::META_KEY, true );

		if ( $value === false ) {
			ISC_Log::log( sprintf( 'Error getting %s for post %d', self::META_KEY, $post_id ) );
		}

		return is_array( $value ) ? $value : '';
	}

	/**
	 * Updater – also sets a new value
	 *
	 * @param int   $post_id Post ID.
	 * @param array $value   The new value to set.
	 *
	 * @return bool|int
	 */
	public static function update( $post_id, $value ) {
		$return = ISC_Model::update_post_meta( $post_id, self::META_KEY, $value );

		// Log on error
		if ( $return === false ) {
			ISC_Log::log( sprintf( 'Error updating %s for post %d', self::META_KEY, $post_id ) );
		}

		return $return;
	}

	/**
	 * Delete the post-images meta value
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	public static function delete( $post_id ): bool {
		$return = delete_post_meta( $post_id, self::META_KEY );

		if ( $return === false ) {
			ISC_Log::log( sprintf( 'Error deleting %s for post %d', self::META_KEY, $post_id ) );
		}

		return $return;
	}

	/**
	 * Remove post_images index
	 * namely the post meta field `isc_post_images`
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function delete_all(): bool {
		return delete_post_meta_by_key( self::META_KEY );
	}

	/**
	 * Updates the post meta index mapping a specific post to the images it contains.
	 *
	 * This method processes an array of image IDs found within a post's content,
	 * automatically includes the post's featured image (thumbnail), applies the
	 * `isc_images_in_posts` filter for extensibility, and then saves the final
	 * structured array to the post's meta field isc_post_images.
	 * The saved array maps image IDs to their data,
	 * including a flag indicating if an image is the featured image.
	 *
	 * @param int   $post_id   The ID of the post whose image index is being updated.
	 * @param array $image_ids An array mapping image attachment IDs found in the post's
	 *                         content to their data (e.g., ['src' => '...']).
	 *                         Example: [ 123 => ['src' => 'url1'], 456 => ['src' => 'url2'] ]
	 *                         The featured image ID will be added or updated automatically.
	 * @return void
	 */
	public static function update_images_in_posts( int $post_id, array $image_ids ) {
		// add thumbnail information
		$thumb_id = get_post_thumbnail_id( $post_id );

		/**
		 * If an image is used both inside the post and as post thumbnail, the thumbnail entry overrides the regular image.
		 */
		if ( ! empty( $thumb_id ) ) {
			$image_ids[ $thumb_id ] = [
				'src'       => wp_get_attachment_url( $thumb_id ),
				'thumbnail' => true,
			];
			ISC_Log::log( 'thumbnail found with ID ' . $thumb_id );
		}

		// apply filter to image array, so other developers can add their own logic
		$image_ids = apply_filters( 'isc_images_in_posts', $image_ids, $post_id );

		if ( empty( $image_ids ) ) {
			$image_ids = [];
		}

		ISC_Log::log( sprintf( 'Updating %s for post %d.', self::META_KEY, $post_id ) );

		self::update( $post_id, $image_ids );
	}
}
