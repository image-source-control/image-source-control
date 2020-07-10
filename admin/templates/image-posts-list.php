<?php
/**
 * Load a list of images with the posts they are displayed in.
 *
 * @var WP_Query $images_with_posts query with posts that have the "isc_image_posts" post meta.
 */
if ( $images_with_posts->have_posts() ) : ?>
	<table class="widefat isc-table" style="width: 80%;" >
		<thead>
			<tr>
				<th><?php esc_html_e( 'Image', 'image-source-control-isc' ); ?></th>
				<th><?php esc_html_e( 'Posts', 'image-source-control-isc' ); ?></th>
			</tr>
		</thead><tbody>
		<?php

		while ( $images_with_posts->have_posts() ) :
			$images_with_posts->the_post();
			$_posts = get_post_meta( get_the_ID(), 'isc_image_posts', true );
			if ( is_array( $_posts ) && count( $_posts ) > 0 ) :
				?>
		<tr>
			<td><a href="<?php echo esc_url( admin_url( 'media.php?attachment_id=' . get_the_ID() . '&action=edit' ) ); ?>"><?php the_title(); ?></a></td>
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
		</tr>
		<?php endif; ?>
		<?php endwhile; ?>
	</tbody></table>
	<?php
endif;
