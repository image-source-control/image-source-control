<?php

namespace ISC\Image_Sources;

use ISC_Log;
use ISC_Model;
use ISC\Indexer;

/**
 * Update post meta indexes for image sources
 */
class Post_Meta {

	const BEFORE_UPDATE_META_KEY = 'isc_post_images_before_update';
	const POST_IMAGE_META_KEY    = 'isc_post_images';
	const IMAGE_POST_META_KEY    = 'isc_image_posts';

	/**
	 * Prepares a post for re-indexing on the next frontend visit.
	 * Moves the current post-image index to a temporary key if it exists.
	 *
	 * Hooked to 'wp_insert_post'.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool True if preparation was done (index moved), false otherwise.
	 */
	public static function prepare_for_reindex( int $post_id ): bool {
		if ( ! Indexer::can_save_image_information( $post_id ) ) {
			ISC_Log::log( sprintf( 'Skipping prepare_for_reindex for post %d: Cannot save image information.', $post_id ) );
			return false;
		}

		$old_value = get_post_meta( $post_id, self::POST_IMAGE_META_KEY, true );

		if ( is_array( $old_value ) ) {
			ISC_Log::log( sprintf( 'Preparing post %d for frontend re-index. Moving existing index.', $post_id ) );
			update_post_meta( $post_id, self::BEFORE_UPDATE_META_KEY, $old_value );
			delete_post_meta( $post_id, self::POST_IMAGE_META_KEY );
			return true;
		} else {
			ISC_Log::log( sprintf( 'Skipping prepare_for_reindex for post %d: No existing index found or already prepared.', $post_id ) );
			return false;
		}
	}

	/**
	 * Retrieves the pre-update state of the post-image index.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array The old index map, or an empty array if none found.
	 */
	public static function get_pre_update_state( int $post_id ): array {
		$state = get_post_meta( $post_id, self::BEFORE_UPDATE_META_KEY, true );
		return is_array( $state ) ? $state : [];
	}

	/**
	 * Compares old and new image associations for a post and updates
	 * the 'isc_image_posts' meta field on individual images accordingly.
	 *
	 * @param int   $post_id       The ID of the post being updated.
	 * @param array $old_image_map Map of images previously associated [id => data].
	 * @param array $new_image_map Map of images currently associated [id => data].
	 */
	public static function sync_image_post_associations( int $post_id, array $old_image_map, array $new_image_map ) {
		ISC_Log::log( sprintf( 'Entering sync_image_post_associations for post %d.', $post_id ) );

		// 1. Calculate differences based on image IDs (keys).
		$old_ids = array_keys( $old_image_map );
		$new_ids = array_keys( $new_image_map );

		// Find IDs present in the new map but not in the old map.
		$added_ids = array_diff( $new_ids, $old_ids );
		// Find IDs present in the old map but not in the new map.
		$removed_ids = array_diff( $old_ids, $new_ids );

		ISC_Log::log( sprintf( 'Post %d - Sync Calculated Added IDs: %s', $post_id, ! empty( $added_ids ) ? implode( ', ', $added_ids ) : 'None' ) );
		ISC_Log::log( sprintf( 'Post %d - Sync Calculated Removed IDs: %s', $post_id, ! empty( $removed_ids ) ? implode( ', ', $removed_ids ) : 'None' ) );

		// 2. Update added associations
		foreach ( $added_ids as $id ) {
			// Basic validation for ID
			if ( ! empty( $id ) && is_numeric( $id ) ) {
				self::add_image_post_association( (int) $id, $post_id );
			} else {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				ISC_Log::log( sprintf( 'Skipping add association for invalid ID: %s', print_r( $id, true ) ) );
			}
		}

		// 3. Update removed associations
		foreach ( $removed_ids as $id ) {
			// Basic validation for ID
			if ( ! empty( $id ) && is_numeric( $id ) ) {
				self::remove_image_post_association( (int) $id, $post_id );
			} else {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				ISC_Log::log( sprintf( 'Skipping remove association for invalid ID: %s', print_r( $id, true ) ) );
			}
		}
		ISC_Log::log( sprintf( 'Exiting sync_image_post_associations for post %d.', $post_id ) );
	}

	/**
	 * Adds an association between an image and a post in the image's meta.
	 *
	 * @param int $image_id Image ID.
	 * @param int $post_id  Post ID.
	 */
	public static function add_image_post_association( int $image_id, int $post_id ) {
		if ( empty( $image_id ) ) {
			return;
		}

		$meta = get_post_meta( $image_id, self::IMAGE_POST_META_KEY, true );
		if ( ! is_array( $meta ) ) {
			$meta = [];
		}
		if ( ! in_array( $post_id, $meta, true ) ) {
			$meta[] = $post_id;
			ISC_Log::log( sprintf( 'Adding post %d to %s for image %d.', $post_id, self::IMAGE_POST_META_KEY, $image_id ) );
			// Use the existing Model method for the actual update + limit logic
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

		$meta = get_post_meta( $image_id, self::IMAGE_POST_META_KEY, true );
		if ( is_array( $meta ) ) {
			$offset = array_search( $post_id, $meta, true );
			if ( $offset !== false ) {
				array_splice( $meta, $offset, 1 );
				ISC_Log::log( sprintf( 'Removing post %d from %s for image %d.', $post_id, self::IMAGE_POST_META_KEY, $image_id ) );
				// Use the existing Model method for the actual update + limit logic
				self::update_image_posts_meta_with_limit( $image_id, array_unique( $meta ) );
			}
		}
	}

	/**
	 * Updates the main post-image index ('isc_post_images') for a post.
	 *
	 * @param int   $post_id       Post ID.
	 * @param array $new_image_map Map of images currently associated [id => data].
	 */
	public static function update_post_image_index( int $post_id, array $new_image_map ) {
		ISC_Log::log( sprintf( 'Updating %s for post %d.', self::POST_IMAGE_META_KEY, $post_id ) );
		// Use the existing Model method which handles thumbnails etc.
		self::update_post_images_meta( $post_id, $new_image_map );
	}

	/**
	 * Cleans up the temporary meta key after re-indexing.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function cleanup_after_reindex( int $post_id ) {
		ISC_Log::log( sprintf( 'Deleting temporary index key %s for post %d.', self::BEFORE_UPDATE_META_KEY, $post_id ) );
		delete_post_meta( $post_id, self::BEFORE_UPDATE_META_KEY );
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

		ISC_Model::update_post_meta( $post_id, 'isc_image_posts', $image_ids );
	}

	/**
	 * Retrieve images added to a post or page and save all information as a post meta value for the post
	 *
	 * @param integer $post_id   ID of a post.
	 * @param array   $image_ids IDs of the attachments in the content.
	 */
	public static function update_post_images_meta( $post_id, $image_ids ) {
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

		ISC_Log::log( 'save isc_post_images with size of ' . count( $image_ids ) );

		ISC_Model::update_post_meta( $post_id, 'isc_post_images', $image_ids );
	}

	/**
	 * Remove the post_images index from a single post
	 * namely the post meta field `isc_post_images`
	 *
	 * @param int $post_id Post ID.
	 */
	public static function clear_single_post_images_index( $post_id ): void {
		delete_post_meta( $post_id, 'isc_post_images' );
	}

	/**
	 * Remove post_images index
	 * namely the post meta field `isc_post_images`
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function clear_post_images_index() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		return $wpdb->delete( $wpdb->postmeta, [ 'meta_key' => 'isc_post_images' ], [ '%s' ] );
	}

	/**
	 * Remove image_posts index
	 * namely the post meta field `isc_image_posts`
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function clear_image_posts_index() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		return $wpdb->delete( $wpdb->postmeta, [ 'meta_key' => 'isc_image_posts' ], [ '%s' ] );
	}

	/**
	 * Remove all image-post relations
	 * this concerns the post meta fields `isc_image_posts` and `isc_post_images`
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public static function clear_index() {
		$rows_deleted_1 = self::clear_post_images_index();
		$rows_deleted_2 = self::clear_image_posts_index();

		if ( $rows_deleted_1 !== false && $rows_deleted_2 !== false ) {
			return $rows_deleted_1 + $rows_deleted_2;
		}

		return false;
	}
}
