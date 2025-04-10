<?php

namespace ISC\Image_Sources\Post_Meta;

use ISC_Log;
use ISC_Model;

/**
 * Handles the image-posts post meta value added to attachments.
 */
class Image_Posts_Meta {

	const META_KEY = 'isc_image_posts';

	/**
	 * Getter
	 *
	 * @param int $image_id Attachment post ID.
	 *
	 * @return array|false The post images meta value, or empty string if not found.
	 */
	public static function get( $image_id ) {
		$value = get_post_meta( $image_id, self::META_KEY, true );

		if ( $value === false ) {
			ISC_Log::log( sprintf( 'Error getting %s for post %d', self::META_KEY, $image_id ) );
		}

		return is_array( $value ) ? $value : '';
	}

	/**
	 * Updater – also sets a new value
	 *
	 * @param int   $image_id Attachment post ID.
	 * @param array $value   The new value to set.
	 *
	 * @return bool|int
	 */
	public static function update( $image_id, $value ) {
		$return = ISC_Model::update_post_meta( $image_id, self::META_KEY, $value );

		// Log on error
		if ( $return === false ) {
			ISC_Log::log( sprintf( 'Error updating %s for post %d', self::META_KEY, $image_id ) );
		}

		return $return;
	}

	/**
	 * Delete the post-images meta value
	 *
	 * @param int $image_id Attachment post ID.
	 *
	 * @return bool
	 */
	public static function delete( $image_id ): bool {
		$return = delete_post_meta( $image_id, self::META_KEY );

		if ( $return === false ) {
			ISC_Log::log( sprintf( 'Error deleting %s for post %d', self::META_KEY, $image_id ) );
		}

		return $return;
	}

	/**
	 * Remove post_images index
	 * namely the post meta field `isc_image_posts`
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function delete_all(): bool {
		return delete_post_meta_by_key( self::META_KEY );
	}

	/**
	 * Update the isc_image_posts meta field with a filtered limit
	 *
	 * @param integer $image_id   ID of the image.
	 * @param array   $post_ids IDs of the posts in which the image appears in.
	 */
	public static function update_image_posts_meta_with_limit( int $image_id, array $post_ids ) {
		// limit the number of post IDs to 10
		$post_ids = array_slice( $post_ids, 0, apply_filters( 'isc_image_posts_meta_limit', 10 ) );

		self::update( $image_id, $post_ids );
	}

	/**
	 * Adds an association between an image and a post in the image's meta.
	 *
	 * @param int $image_id Image ID.
	 * @param int $post_id  Post ID.
	 *
	 * @return void
	 */
	public static function add_image_post_association( int $image_id, int $post_id ): void {
		if ( empty( $image_id ) ) {
			return;
		}

		$meta = get_post_meta( $image_id, self::META_KEY, true );
		if ( ! is_array( $meta ) ) {
			$meta = [];
		}
		if ( ! in_array( $post_id, $meta, true ) ) {
			$meta[] = $post_id;
			ISC_Log::log( sprintf( 'Adding post %d to %s for image %d.', $post_id, self::META_KEY, $image_id ) );
			self::update_image_posts_meta_with_limit( $image_id, array_unique( $meta ) );
		}
	}

	/**
	 * Removes an association between an image and a post in the image's meta.
	 *
	 * @param int $image_id Image ID.
	 * @param int $post_id  Post ID.
	 */
	public static function remove_image_post_association( int $image_id, int $post_id ) {
		if ( empty( $image_id ) ) {
			return;
		}

		$meta = self::get( $image_id );
		if ( is_array( $meta ) ) {
			$offset = array_search( $post_id, $meta, true );
			if ( $offset !== false ) {
				array_splice( $meta, $offset, 1 );
				ISC_Log::log( sprintf( 'Removing post %d from %s for image %d.', $post_id, self::META_KEY, $image_id ) );
				self::update_image_posts_meta_with_limit( $image_id, array_unique( $meta ) );
			}
		}
	}
}
