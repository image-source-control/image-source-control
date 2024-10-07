<?php

namespace ISC;

use ISC_Log, ISC_Model;

/**
 * Index content for images and write isc_post_images and isc_image_posts post meta
 */
class Indexer {

	/**
	 * Handle index updates
	 *
	 * @param string $content content of the target post.
	 *
	 * @return void
	 */
	public static function update_indexes( string $content ) {

		if ( ! self::can_index_the_page() ) {
			return;
		}

		// Skip indexing if this is a page with a Global list.
		if ( has_shortcode( $content, '[isc_list_all]' ) || false !== strpos( $content, 'isc_all_image_list_box' ) ) {
			return;
		}

		global $post;
		$attachments = self::get_attachments_for_index( $post->ID );

		/**
		 * $attachments is an empty string if it was never set and an array if it was set
		 * the array is empty if no images were found in the past. This prevents re-indexing as well
		 */
		if ( $attachments !== '' ) {
			return;
		}

		ISC_Log::log( 'start updating index for post ID ' . $post->ID );

		// check if we can even save the image information
		// abort on archive pages since some output from other plugins might be disabled here
		if (
			is_archive()
			|| is_home()
			|| ! self::can_save_image_information( $post->ID ) ) {
			return;
		}

		$image_ids = ISC_Model::filter_image_ids( $content );

		ISC_Log::log( 'updating index with image IDs ' . implode( ', ', $image_ids ) );

		// retrieve images added to a post or page and save all information as a post meta value for the post
		ISC_Model::update_post_images_meta( $post->ID, $image_ids );

		// add the post ID to the list of posts associated with a given image
		ISC_Model::update_image_posts_meta( $post->ID, $image_ids );
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
}
