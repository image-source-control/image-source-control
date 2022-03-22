<?php
/**
 * Renders the ISC debugging page which shows image sources and allows debugging of existing entries.
 *
 * @var int $storage_size amount of elements in the ISC storage.
 */
?>
<?php
$attachments = ISC_Model::get_attachments_with_empty_sources();
if ( ! empty( $attachments ) ) :
	?>
	<h2><?php esc_html_e( 'Images with empty sources', 'image-source-control-isc' ); ?></h2>
	<p><?php esc_html_e( 'Images with empty sources and not belonging to an author.', 'image-source-control-isc' ); ?></p>
	<table class="widefat isc-table" style="width: 80%;" >
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'image-source-control-isc' ); ?></th>
				<th><?php esc_html_e( 'Image title', 'image-source-control-isc' ); ?></th>
				<th><?php esc_html_e( 'Post / Page', 'image-source-control-isc' ); ?></th>
			</tr>
		</thead><tbody>
		<?php
		foreach ( $attachments as $_attachment ) :
			?>
		<tr>
			<td><?php echo absint( $_attachment->ID ); ?></td>
			<td><a href="<?php echo esc_url( admin_url( 'media.php?attachment_id=' . $_attachment->ID . '&action=edit' ) ); ?>" title="<?php esc_html_e( 'edit this image', 'image-source-control-isc' ); ?>"><?php echo esc_html( $_attachment->post_title ); ?></a></td>
			<td>
			<?php
			if ( $_attachment->post_parent ) :
				?>
				<a href="<?php echo esc_url( get_edit_post_link( $_attachment->post_parent ) ); ?>" title="<?php esc_html_e( 'show parent post’s edit page', 'image-source-control-isc' ); ?>"><?php echo esc_html( get_the_title( $_attachment->post_parent ) ); ?></a>
				<?php
else :
	esc_html_e( 'no connection', 'image-source-control-isc' );
	?>
	<?php endif; ?></td></tr>
		<?php endforeach; ?>
	</tbody></table>
	<?php
else :
	?>
	<div class="notice notice-success"><p><span class="dashicons dashicons-yes" style="color: #46b450"></span><?php esc_html_e( 'All images found in the frontend have sources assigned.', 'image-source-control-isc' ); ?></p></div>
	<?php
endif;

$attachments = ISC_Model::get_unused_attachments();
if ( ! empty( $attachments ) ) :
	?>
	<h2><?php esc_html_e( 'Images with unknown position', 'image-source-control-isc' ); ?></h2>
	<p><?php esc_html_e( 'The list contains images that neither have sources nor were yet found by ISC on your site.', 'image-source-control-isc' ); ?>&nbsp;
	<?php esc_html_e( 'They might not need a source after all.', 'image-source-control-isc' ); ?></p>
	<table class="widefat isc-table" style="width: 80%;" >
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'image-source-control-isc' ); ?></th>
				<th><?php esc_html_e( 'Image title', 'image-source-control-isc' ); ?></th>
			</tr>
		</thead><tbody>
		<?php
		foreach ( $attachments as $_attachment ) :
			?>
		<tr>
			<td><?php echo absint( $_attachment->ID ); ?></td>
			<td><a href="<?php echo esc_url( admin_url( 'media.php?attachment_id=' . $_attachment->ID . '&action=edit' ) ); ?>" title="<?php esc_html_e( 'edit this image', 'image-source-control-isc' ); ?>"><?php echo esc_html( $_attachment->post_title ); ?></a></td>
		</tr>
		<?php endforeach; ?>
	</tbody></table>
	<?php
endif;

/**
 * Render ISC_Storage data for images that are hosted outside of the WP media library.
 */
$storage_model = new ISC_Storage_Model();
$stored_images = $storage_model->get_storage_without_wp_images();
if ( ! empty( $stored_images ) ) :
	?>
	<h2><?php esc_html_e( 'Additional images', 'image-source-control-isc' ); ?></h2>
	<p><?php esc_html_e( 'ISC found image URLs that are not part of the media library.', 'image-source-control-isc' ); ?></p>
	<table class="widefat isc-table isc-table-storage" style="width: 80%;">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Image URL', 'image-source-control-isc' ); ?></th>
			<th><?php esc_html_e( 'Image Source', 'image-source-control-isc' ); ?></th>
		</tr>
		</thead><tbody>
		<?php
		foreach ( $stored_images as $_image_url => $_stored_image ) :
			?>
			<tr>
				<td style="width: 60%;"><?php echo esc_url( $_image_url ); ?></td>
				<td>
					<?php
					if ( ! class_exists( 'ISC_Pro_Admin', false ) ) : echo ISC_Admin::get_pro_link( 'external-sources' ); endif;
					do_action( 'isc_admin_sources_storage_table_source_row', $_image_url );
					?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody></table>
	<?php
endif;
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