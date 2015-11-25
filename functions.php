<?php
/**
 * functions that can be directly used in the frontend
 */

/**
 * echoes a list with image sources for a given post
 *
 * @global obj $my_isc isc class
 * @param int $post_id post id
 */
function isc_list($post_id = 0) {
    $isc_public = ISC_Class::get_instance();
    echo $isc_public->list_post_attachments_with_sources($post_id);
}

/**
 * returns the source html of a given image
 *
 * @global obj $my_isc isc class
 * @param int $attachment_id id of the image
 */
function isc_image_source($attachment_id = 0) {
    $isc_public = ISC_Class::get_instance();
    echo $isc_public->render_image_source_string($attachment_id);
}

/**
 * return the source html of the featured image
 *
 * @since 1.8
 * @global obj $my_isc isc class
 * @global obj $post current post
 * @param int $post_id id of the post; will use current post if empty
 */
function isc_thumbnail_source($post_id = 0) {
    global $post;
    $isc_public = ISC_Class::get_instance();

    if( empty($post_id) && isset($post->ID) ){
        $post_id = $post->ID;
    }

    echo $isc_public->get_thumbnail_source_string($post_id);
}
