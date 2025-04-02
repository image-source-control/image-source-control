<?php

namespace ISC;

use ISC\Image_Sources\Post_Meta;
use ISC_Log, ISC_Model;

/**
 * Index content for images and write isc_post_images and isc_image_posts post meta
 */
class Indexer {

	/**
	 * Handle index updates on frontend visit after a post save.
	 * Compares pre-save state with current render and updates indexes.
	 *
	 * @param string $content Rendered content of the target post.
	 *
	 * @return void
	 */
	public static function update_indexes( string $content ) {

		if ( ! self::can_index_the_page() ) {
			return;
		}

		global $post;

		// Skip indexing if this is a page with a Global list.
		if ( has_shortcode( $content, '[isc_list_all]' ) || false !== strpos( $content, 'isc_all_image_list_box' ) ) {
			// Ensure no temporary meta is left behind if user adds shortcode later.
			Post_Meta::cleanup_after_reindex( $post->ID ); // Use the cleanup method
			// An empty isc_post_images meta value indicates the post was indexed (or intentionally skipped).
			if ( get_post_meta( $post->ID, Post_Meta::POST_IMAGE_META_KEY, true ) === '' ) {
				Post_Meta::update_post_image_index( $post->ID, [] );
			}
			ISC_Log::log( sprintf( 'Exiting update_indexes for post %d: Global list page.', $post->ID ) );
			return;
		}

		/**
		 * Triggered before updating the indexes.
		 * Useful to run code with the index even though the Image Source already have an index
		 *
		 * @param int $post->ID Post ID.
		 * @param string $content Post content.
		 */
		do_action( 'isc_before_update_indexes', $post->ID, $content );

		// ignore existing indexes if the bot is running
		if ( ! self::is_index_bot() ) {
			$attachments = self::get_attachments_for_index( $post->ID );

			/**
			 * $attachments is an empty string if it was never set and an array if it was set
			 * the array is empty if no images were found in the past. This prevents re-indexing as well
			 */
			if ( $attachments !== '' ) {
				// Remove the temporary data since we are not updating the index
				Post_Meta::cleanup_after_reindex( $post->ID );
				return;
			}
		}

		ISC_Log::log( 'Start updating index for post ID ' . $post->ID );

		// Check if we can even save the image information
		// Abort on archive pages, home, or unsupported post types
		if ( is_archive() || is_home() || ! self::can_save_image_information( $post->ID ) ) {
			ISC_Log::log( sprintf( 'Exiting update_indexes for post %d: Cannot save image information (archive/home/post type).', $post->ID ) );
			// Clean up temporary meta if we abort here
			Post_Meta::cleanup_after_reindex( $post->ID );
			return;
		}

		// 1. Get the state before the last update(s).
		$old_indexed_data = Post_Meta::get_pre_update_state( $post->ID );

		// 2. Get the image IDs from the currently rendered content.
		// Call filter_image_ids only ONCE here.
		$new_rendered_ids = ISC_Model::filter_image_ids( $content );

		$thumb_id = get_post_thumbnail_id( $post->ID );
		if ( ! empty( $thumb_id ) && ! isset( $new_rendered_ids[ $thumb_id ] ) ) {
			// Add thumbnail to the list if it's not already there from content parsing.
			// The value structure should match what filter_image_ids returns,
			// though sync_image_post_associations only cares about the keys.
			// The 'thumbnail' flag itself is added later in update_post_image_index.
			$thumb_url = wp_get_attachment_url( $thumb_id );
			if ( $thumb_url ) {
				$new_rendered_ids[ $thumb_id ] = [ 'src' => $thumb_url ];
				ISC_Log::log( sprintf( 'Added thumbnail ID %d to new_rendered_ids for post %d.', $thumb_id, $post->ID ) );
			}
		}

		// Allows developers to modify the list before synchronization.
		$new_rendered_ids = apply_filters( 'isc_images_in_posts_simple', $new_rendered_ids, $post->ID );
		if ( has_filter( 'isc_images_in_posts_simple' ) ) {
			ISC_Log::log( sprintf( 'Post %d - new_rendered_ids after isc_images_in_posts_simple filter ran: %s', $post->ID, ! empty( $new_rendered_ids ) ? implode( ', ', array_keys( $new_rendered_ids ) ) : 'Empty' ) );
		}

		// Check if image IDs refer to a valid post type (default: 'attachment').
		$valid_image_post_types = apply_filters( 'isc_valid_post_types', [ 'attachment' ] );
		if ( ! empty( $new_rendered_ids ) ) { // Avoid errors if array is empty
			foreach ( $new_rendered_ids as $_id => $_data ) {
				// Ensure ID is numeric before checking post type
				if ( ! is_numeric( $_id ) || $_id <= 0 ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
					ISC_Log::log( sprintf( 'Removing invalid image ID %s from index for post %d.', print_r( $_id, true ), $post->ID ) );
					unset( $new_rendered_ids[ $_id ] );
					continue;
				}
				$post_type = get_post_type( (int) $_id );
				if ( ! $post_type || ! in_array( $post_type, $valid_image_post_types, true ) ) {
					ISC_Log::log( sprintf( 'Removing image ID %d (type: %s) due to invalid post type for post %d.', $_id, $post_type ? $post_type : 'unknown', $post->ID ) );
					unset( $new_rendered_ids[ $_id ] );
				}
			}
		}

		// 3. Sync the image->post associations based on comparison.
		// This handles adding/removing $post->ID from individual image's isc_image_posts meta.
		Post_Meta::sync_image_post_associations( $post->ID, $old_indexed_data, $new_rendered_ids );

		// 4. Update the main post->image index ('isc_post_images') with the current state.
		// This saves the $new_rendered_ids to the post's meta.
		Post_Meta::update_post_image_index( $post->ID, $new_rendered_ids );

		// 5. Clean up the temporary meta key.
		Post_Meta::cleanup_after_reindex( $post->ID );

		/**
		 * Triggered after updating the indexes.
		 *
		 * @param int    $post_id          Post ID.
		 * @param string $content          Post content.
		 * @param array  $new_rendered_ids Image IDs found in the content ([id => data]).
		 */
		do_action( 'isc_after_update_indexes', $post->ID, $content, $new_rendered_ids );
	}

	/**
	 * Return the attachments array used for indexing image-post relations
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string|array
	 */
	public static function get_attachments_for_index( int $post_id ) {
		$attachments   = '';
		$ignore_caches = apply_filters( 'isc_add_sources_to_content_ignore_post_images_index', ISC_Log::ignore_caches() );

		if ( $ignore_caches ) {
			ISC_Log::log( 'ignoring post-image index' );
		} else {
			// check if a post-images index exists
			$attachments = get_post_meta( $post_id, 'isc_post_images', true );
			if ( $attachments === '' ) {
				ISC_Log::log( 'no post-images index found' );
			} elseif ( is_array( $attachments ) ) {
				ISC_Log::log( sprintf( 'found existing list of %d images for post ID %d', count( $attachments ), $post_id ) );
			}
		}

		return $attachments;
	}


	/**
	 * Return true if the current page can be indexed and sources should be added
	 *
	 * @return bool
	 */
	public static function can_index_the_page(): bool {
		// bail early if the content is used to create the excerpt
		if ( doing_filter( 'get_the_excerpt' ) ) {
			ISC_Log::log( 'skipped adding sources to the excerpt' );
			return false;
		}

		// disabling the content filters while working in page builders or block editor
		if ( wp_is_json_request() || defined( 'REST_REQUEST' ) ) {
			ISC_Log::log( 'skipped adding sources while working in page builders' );
			return false;
		}

		global $post;
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( empty( $post->ID ) ) {
			if ( $request_uri ) {
				ISC_Log::log( 'exit content for ' . $request_uri . ' due to missing post_id' );
			}
			return false;
		}

		if ( $request_uri ) {
			ISC_Log::log( 'can index content for ' . $request_uri . ' and post ID ' . $post->ID );
		}

		return true;
	}

	/**
	 * Don’t save meta data for non-public post types, since those shouldn’t be visible in the frontend
	 * ignore also attachment posts
	 * ignore revisions
	 *
	 * @param integer|null $post_id WP_Post ID. Useful if post object is not given.
	 */
	public static function can_save_image_information( int $post_id = null ): bool {
		$post = get_post( $post_id );

		if ( ! isset( $post->post_type )
			|| ! in_array( $post->post_type, get_post_types( [ 'public' => true ] ), true ) // is the post type public
			|| $post->post_type === 'attachment'
			|| $post->post_type === 'revision' ) {
			return false;
		}

		return true;
	}

	/**
	 * Return true if the current user agent is the index bot
	 */
	public static function is_index_bot(): bool {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		// Check the user agent first
		$is_bot = strpos( $user_agent, 'ISC Index Bot' ) !== false;

		// Apply a filter to allow overriding the result, passing the original check result
		return apply_filters( 'isc_is_index_bot', $is_bot );
	}
}
