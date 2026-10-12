<?php
/**
 * Renders the view of images without source information on the ISC tools page
 *
 * @var array $attachments list of attachments without source information
 */
?>
<?php if ( count( $attachments ) >= ISC_Model::MAX_POSTS ) : ?>
	<p>
	<?php
		printf(
			// translators: %d is the number of entries in the following table
			esc_html__( 'The list only shows the last %d images.', 'image-source-control-isc' ),
			(int) ISC_Model::MAX_POSTS
		);
	?>
	</p>
<?php endif; ?>
<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'upload.php?mode=list&isc_filter=without_source' ) ); ?>"><?php esc_html_e( 'Bulk edit missing sources', 'image-source-control-isc' ); ?></a>
<table class="widefat striped isc-table" style="width: 80%;" >
	<thead>
		<tr>
			<th><?php esc_html_e( 'Image', 'image-source-control-isc' ); ?></th>
			<th><?php esc_html_e( 'Image title', 'image-source-control-isc' ); ?></th>
			<th><?php esc_html_e( 'Post / Page', 'image-source-control-isc' ); ?></th>
		</tr>
	</thead><tbody>
	<?php
	foreach ( $attachments as $_attachment ) :
		?>
	<tr>
		<td><?php edit_post_link( wp_get_attachment_image( $_attachment->ID, [ 60, 60 ] ), '', '', $_attachment->ID ); ?></td>
		<td><?php edit_post_link( esc_html( $_attachment->post_title ), '', '', $_attachment->ID ); ?></td>
		<td>
		<?php
		if ( $_attachment->post_parent ) :
			$edit_post_link = get_edit_post_link( $_attachment->post_parent );
			if ( $edit_post_link ) :
				?>
		<a href="<?php echo esc_url( $edit_post_link ); ?>"><?php echo esc_html( get_the_title( $_attachment->post_parent ) ); ?></a>
				<?php
			else :
				echo esc_html( get_the_title( $_attachment->post_parent ) );
			endif;
else :
	echo '–';
endif;
?>
</td></tr>
	<?php endforeach; ?>
</tbody>
</table>