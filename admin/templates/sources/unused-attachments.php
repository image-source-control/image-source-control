<?php
/**
 * Render view wit unused attachments
 *
 * @var array $attachments list of attachments without association to a post
 */
?>
<p><?php esc_html_e( 'The list contains images that neither have sources nor were yet found by ISC on your site.', 'image-source-control-isc' ); ?>&nbsp;
	<?php esc_html_e( 'They might not need a source after all.', 'image-source-control-isc' ); ?></p>
<?php if( count( $attachments ) >= ISC_Model::MAX_POSTS ) : ?>
<p><?php
	printf(
	// translators: %d is the number of entries in the following table
		esc_html__( 'The list only shows the last %d images.', 'image-source-control-isc' ),
		ISC_Model::MAX_POSTS
	); ?>
</p>
<?php endif; ?>
<table class="widefat striped isc-table" style="width: 80%;" >
	<thead>
	<tr>
		<th><?php esc_html_e( 'Thumbnail', 'image-source-control-isc' ); ?></th>
		<th><?php esc_html_e( 'Image title', 'image-source-control-isc' ); ?></th>
	</tr>
	</thead><tbody>
	<?php
	foreach ( $attachments as $_attachment ) :
		?>
		<tr>
			<td><?php edit_post_link( wp_get_attachment_image( $_attachment->ID, [ 60, 60 ] ), '', '', $_attachment->ID ); ?></td>
			<td><?php edit_post_link( esc_html( $_attachment->post_title ), '', '', $_attachment->ID ); ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>