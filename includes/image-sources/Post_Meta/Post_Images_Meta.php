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
	 * namely the post meta field `isc_post_images`
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function delete_all() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		return $wpdb->delete( $wpdb->postmeta, [ 'meta_key' => self::META_KEY ], [ '%s' ] );
	}

	/**
	 * Retrieve images added to a post or page and save all information as a post meta value for the post
	 *
	 * @param integer $post_id   ID of a post.
	 * @param array   $image_ids IDs of the attachments in the content.
	 */
	public static function update_images_in_posts( int $post_id, $image_ids ) {
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
