<?php
/**
 * Render ISC_Storage data for images that are hosted outside of the WP media library.
 *
 * @var array $external_images list of images that are not part of the media library.
 */
?>
<p><?php esc_html_e( 'ISC found image URLs that are not part of the media library.', 'image-source-control-isc' ); ?></p>
<table class="widefat striped isc-table isc-table-storage" style="width: 80%;">
	<thead>
	<tr>
		<th><?php esc_html_e( 'Image URL', 'image-source-control-isc' ); ?></th>
		<th><?php esc_html_e( 'Image Source', 'image-source-control-isc' ); ?></th>
	</tr>
	</thead><tbody>
	<?php
	foreach ( $external_images as $_image_url => $_stored_image ) :
		// we add HTTPS by default, assuming, that this is a standard now, even though the image might not use HTTPS
		$image_url = 'https://' . $_image_url;
		?>
		<tr>
			<td style="width: 60%;"><a href="<?php echo esc_url( $image_url ); ?>" rel="noopener noreferrer"><?php echo esc_url( $image_url ); ?></a></td>
			<td>
				<?php
				if ( ! \ISC\Plugin::is_pro() ) : echo ISC_Admin::get_pro_link( 'external-sources' ); endif;
				do_action( 'isc_admin_sources_storage_table_source_row', $_image_url );
				?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>