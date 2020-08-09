<?php
/**
 * Load a list of posts / pages and the images that belong to them.
 *
 * @var WP_Query $posts_with_images query with posts that have the "isc_post_images" post meta.
 */
if ( $posts_with_images->have_posts() ) : ?>
	<table class="widefat isc-table isc-table-debug-list" style="width: 80%;" >
		<thead>
			<tr>
				<th><?php esc_html_e( 'Post / Page', 'image-source-control-isc' ); ?></th>
				<th><?php esc_html_e( 'Images', 'image-source-control-isc' ); ?></th>
			</tr>
		</thead><tbody>
		<?php

		while ( $posts_with_images->have_posts() ) :
			$posts_with_images->the_post();
			$_images = get_post_meta( get_the_ID(), 'isc_post_images', true );
			if ( is_array( $_images ) && count( $_images ) > 0 ) :
				?>
		<tr>
			<td><a href="<?php echo esc_url( get_edit_post_link( get_the_ID() ) ); ?>"><?php the_title(); ?></a></td>
			<td>
					<ul>
					<?php
					foreach ( $_images as $_image_id => $_image_url ) :
						?>
						<li><?php if ( $_image_id ) : ?>
							<a href="<?php echo esc_url( admin_url( 'media.php?attachment_id=' . $_image_id . '&action=edit' ) ); ?>" title="<?php esc_html_e( 'edit this image', 'image-source-control-isc' ); ?>"><?php echo esc_html( get_the_title( $_image_id ) ); ?></a></li>
						<?php else : ?>
							<?php print_r( $_images ); ?>
						<?php endif; ?>
					<?php endforeach; ?>
					</ul>
			</td>
		</tr>
		<?php endif; ?>
		<?php endwhile; ?>
	</tbody></table>
	<?php
endif;
