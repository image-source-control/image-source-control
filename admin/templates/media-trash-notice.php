<?php
/**
 * Admin notice for Media Trash module
 */
?>
<div class="notice notice-info is-dismissible">
	<p>
		<strong><?php esc_html_e( 'Media Trash is enabled', 'image-source-control-isc' ); ?></strong>
	</p>
	<p>
		<?php
		esc_html_e( 'Media files will be moved to the isc-trash folder when trashed and automatically deleted after 30 days. Files are restored to their original location when untrashed.', 'image-source-control-isc' );
		?>
	</p>
</div>
