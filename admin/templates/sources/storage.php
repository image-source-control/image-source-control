<?php
/**
 * Render the Debug section on the ISC Tools page
 *
 * @var integer $storage_size size of the storage
 */
?>
<p class="description"><?php esc_html_e( 'ISC keeps an internal index of image URLs and IDs from the media library to limit the number of database requests in the frontend.', 'image-source-control-isc' ); ?>
	<br/><?php esc_html_e( 'Click the button above to clear that index.', 'image-source-control-isc' ); ?>
	<a href="<?php echo ISC_Admin::get_manual_url( 'tools-clear-index' ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Manual', 'image-source-control-isc' ); ?></a>
</p>
<p><?php printf( esc_html__( '%d images in storage', 'image-source-control-isc'), $storage_size ); ?></p>
<button id="isc-clear-storage" class="button button-secondary"><?php esc_html_e( 'clear storage', 'image-source-control-isc' ); ?></button>
<div id="isc-clear-storage-feedback"></div>