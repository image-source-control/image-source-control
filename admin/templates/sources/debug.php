<?php
/**
 * Render the Debug section on the ISC Tools page
 */
?>
<h2><?php esc_html_e( 'Debug', 'image-source-control-isc' ); ?></h2>
<p><?php esc_html_e( 'The following options allow you to see if ISC was able to detect all images.', 'image-source-control-isc' ); ?>
	<br/><?php esc_html_e( 'Please keep in mind that the plugin only knows about posts that have been visited at least once in the frontend.', 'image-source-control-isc' ); ?></p>
<button id="isc-list-post-image-relation" class="button button-secondary"><?php esc_html_e( 'list post-image relations', 'image-source-control-isc' ); ?></button>
<p class="description"><?php esc_html_e( 'A list of posts and the images in them.', 'image-source-control-isc' ); ?></p>
<div id="isc-post-image-relations"></div>
<hr/>
<button id="isc-list-image-post-relation" class="button button-secondary"><?php esc_html_e( 'list image-post relations', 'image-source-control-isc' ); ?></button>
<p class="description"><?php esc_html_e( 'A list of images and the posts they appear in.', 'image-source-control-isc' ); ?></p>
<div id="isc-image-post-relations"></div>
<hr/>
<button id="isc-clear-index" class="button button-secondary"><?php esc_html_e( 'clear image-post index', 'image-source-control-isc' ); ?></button>
<p class="description"><?php esc_html_e( 'Click the button to remove the connections between images and posts as listed above.', 'image-source-control-isc' ); ?>
	<br/><?php esc_html_e( 'The index is rebuilt automatically when a page with images on it is visited in the frontend.', 'image-source-control-isc' ); ?></p>
<div id="isc-clear-index-feedback"></div>
<hr/>
<p><?php printf( esc_html__( '%d images in storage', 'image-source-control-isc'), $storage_size ); ?></p>
<button id="isc-clear-storage" class="button button-secondary"><?php esc_html_e( 'clear storage', 'image-source-control-isc' ); ?></button>
<p class="description"><?php esc_html_e( 'ISC keeps an internal index of image URLs and IDs from the media library to limit the number of database requests in the frontend.', 'image-source-control-isc' ); ?>
	<br/><?php esc_html_e( 'Click the button above to clear that index.', 'image-source-control-isc' ); ?>
	<a href="<?php echo ISC_Admin::get_manual_url( 'tools-clear-index' ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Manual', 'image-source-control-isc' ); ?></a>
</p>
<div id="isc-clear-storage-feedback"></div>