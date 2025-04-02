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
	 * @param int $post_id Post ID.
	 *
	 * @return array|string The post images meta value, or false if not found.
	 */
	public static function get( $post_id ) {
		$value = get_post_meta( $post_id, self::META_KEY, true );
		return is_array( $value ) ? $value : '';
	}

	/**
	 * Updater – also sets a new value
	 *
	 * @param int   $post_id Post ID.
	 * @param array $value   The new value to set.
	 */
	public static function update( $post_id, $value ) {
		ISC_Model::update_post_meta( $post_id, self::META_KEY, $value );
	}

	/**
	 * Delete the post-images meta value
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public static function delete( $post_id ) {
		delete_post_meta( $post_id, self::META_KEY );
	}

	/**
	 * Remove post_images index
	 * namely the post meta field `isc_image_posts`
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function delete_all() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		return $wpdb->delete( $wpdb->postmeta, [ 'meta_key' => self::META_KEY ], [ '%s' ] );
	}

	/**
	 * Update the isc_image_posts meta field with a filtered limit
	 *
	 * @param integer $post_id   ID of the target post.
	 * @param array   $image_ids IDs of the attachments in the content.
	 */
	public static function update_image_posts_meta_with_limit( int $post_id, array $image_ids ) {
		// limit the number of post IDs to 10
		$image_ids = array_slice( $image_ids, 0, apply_filters( 'isc_image_posts_meta_limit', 10 ) );

		self::update( $post_id, $image_ids );
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

		$meta = get_post_meta( $image_id, self::META_KEY, true );
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
