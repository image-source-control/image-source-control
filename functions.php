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
	$isc_public = ISC_Class::get_instance();
	echo $isc_public->list_post_attachments_with_sources( $post_id );
}

/**
 * Returns the source html of a given image
 *
 * @global object$my_isc isc class
 * @param integer $attachment_id id of the image.
 */
function isc_image_source( $attachment_id = 0 ) {
	$isc_public = ISC_Class::get_instance();
	echo $isc_public->render_image_source_string( $attachment_id );
}

/**
 * Return the source html of the featured image
 *
 * @global object $my_isc isc class
 * @global object $post current post
 * @param integer $post_id id of the post; will use current post if empty.
 */
function isc_thumbnail_source( $post_id = 0 ) {
	global $post;
	$isc_public = ISC_Class::get_instance();

	if ( empty( $post_id ) && isset( $post->ID ) ) {
		$post_id = $post->ID;
	}

	echo $isc_public->get_thumbnail_source_string( $post_id );
}
