<?php
/**
 * Load a list of images with the posts they are displayed in.
 *
 * @var WP_Query $images_with_posts query with posts that have the "isc_image_posts" post meta.
 */
?>
<table class="widefat isc-table isc-table-debug-list" style="width: 80%;" >
	<thead>
		<tr>
			<th><?php esc_html_e( 'Image', 'image-source-control-isc' ); ?></th>
			<th><?php esc_html_e( 'Posts', 'image-source-control-isc' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'image-source-control-isc' ); ?></th>
		</tr>
	</thead><tbody>
	<?php

	while ( $images_with_posts->have_posts() ) :
		$images_with_posts->the_post();
		$_posts = get_post_meta( get_the_ID(), 'isc_image_posts', true );
		if ( is_array( $_posts ) && count( $_posts ) > 0 ) :
			?>
	<tr>
		<td>
			<?php edit_post_link( get_the_title(), '', '', get_the_ID() ); ?>
		</td>
		<td>
				<ul>
				<?php
				foreach ( $_posts as $_post_id ) :
					// skip if this is a revision.
					if ( wp_is_post_revision( $_post_id ) ) {
						continue;
					}
					?>
					<li><a href="<?php echo esc_url( get_edit_post_link( $_post_id ) ); ?>"><?php echo esc_html( get_the_title( $_post_id ) ); ?></a></li>
				<?php endforeach; ?>
				</ul>
		</td>
		<td>
			<button type="button" class="button isc-button-delete-image-posts-index" data-image-id="<?php echo absint( get_the_ID() ); ?>">
				<?php esc_html_e( 'Delete', 'image-source-control-isc' ); ?>
			</button>
		</td>
	</tr>
	<?php endif; ?>
	<?php endwhile; ?>
</tbody></table>
<?php
