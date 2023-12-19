<?php
/**
 * Functions that can be directly used in the frontend
 */

/**
 * Echoes a list with image sources for a given post
 *
 * @global object $my_isc isc class
 * @param integer $post_id post id.
 */
function isc_list( $post_id = 0 ) {
	echo ISC_Class::get_instance()->list_post_attachments_with_sources( $post_id );
}

/**
 * Returns the source html of a given image
 *
 * @global object$my_isc isc class
 * @param integer $attachment_id id of the image.
 */
function isc_image_source( $attachment_id = 0 ) {
	echo ISC_Class::get_instance()->render_image_source_string( $attachment_id );
}

/**
 * Return the source html of the featured image
 *
 * @param integer $post_id id of the post; will use current post if empty.
 *
 * @global object $post    current post
 * @global object $my_isc  isc class
 */
function isc_thumbnail_source( int $post_id = 0 ) {
	global $post;

	if ( empty( $post_id ) && isset( $post->ID ) ) {
		$post_id = $post->ID;
	}

	echo ISC_Class::get_instance()->get_thumbnail_source_string( $post_id );
}
