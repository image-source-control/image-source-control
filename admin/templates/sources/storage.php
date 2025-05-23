<?php
/**
 * Render the Debug section on the ISC Tools page
 *
 * @var integer $storage_size size of the storage
 */

?>
<p class="description"><?php esc_html_e( 'ISC keeps an internal index of image URLs and IDs from the media library to limit the number of database requests in the frontend.', 'image-source-control-isc' ); ?>
	<br/><?php esc_html_e( 'Click the button above to clear that index.', 'image-source-control-isc' ); ?>
	<a href="<?php echo esc_url( ISC\Admin_Utils::get_manual_url( 'tools-clear-index' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Manual', 'image-source-control-isc' ); ?></a>
</p>
<p>
<?php
printf(
	// translators: %d is the number of images in storage
	esc_html__( '%d images in storage', 'image-source-control-isc' ),
	(int) $storage_size
);
?>
	</p>
<button id="isc-clear-storage" class="button button-secondary"><?php esc_html_e( 'clear storage', 'image-source-control-isc' ); ?></button>
<div id="isc-clear-storage-feedback"></div>
<?php
// if the WordPress debug mode is enabled, show the button to dump the storage
if ( $storage_size > 0 && defined( 'WP_DEBUG' ) && WP_DEBUG ) :
	?>
	<br/>
	<button id="isc-show-storage" class="button button-secondary"><?php esc_html_e( 'show storage', 'image-source-control-isc' ); ?></button>
	<pre id="isc-show-storage-output"></pre>
	<?php
endif; ?>