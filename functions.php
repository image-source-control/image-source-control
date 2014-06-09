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
    global $my_isc;
    echo $my_isc->list_post_attachments_with_sources($post_id);
}

/**
 * returns the source html of a given image
 *
 * @global obj $my_isc isc class
 * @param int $attachment_id id of the image
 */
function isc_image_source($attachment_id = 0) {
    global $my_isc;
    echo $my_isc->render_image_source_string($attachment_id);
}
