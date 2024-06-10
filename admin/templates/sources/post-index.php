<?php
/**
 * List the number of public posts by post type and the amount of them being indexed by ISC.
 *
 * @var array $post_type_image_index list of post types with the number of public posts and the number of indexed posts
 */
?>
<p class="description">
	<?php esc_html_e( 'If you are using the Global List with “Images in the content” selected, only images from “indexed” post types appear in it.', 'image-source-control-isc' ); ?>
	<br><?php esc_html_e( 'Image Source Control knows posts that have been visited in the frontend or were saved in the backend.', 'image-source-control-isc' ); ?>
	<br><?php esc_html_e( 'The table below shows the number of indexed published posts.', 'image-source-control-isc' ); ?>
</p>
<table class="widefat striped isc-table" style="width: 80%;" >
	<thead>
		<tr>
			<th><?php esc_html_e( 'Post type', 'image-source-control-isc' ); ?></th>
			<th><?php esc_html_e( 'Indexed posts', 'image-source-control-isc' ); ?></th>
		</tr>
	</thead><tbody>
	<?php
	foreach ( $post_type_image_index as $post_type => $_post_type_data ) :
		if ( ! $_post_type_data['total_posts'] ) {
			continue;
		}
		$post_type_object = get_post_type_object( $post_type ); // Get the post type object
		$post_type_label = $post_type_object->labels->name; // Get the public label of the post type
		?>
		<tr>
			<td><?php echo esc_html( $post_type_label ); ?></td>
			<td><?php echo (int) $_post_type_data['with_meta_field']; ?> / <?php echo (int) $_post_type_data['total_posts']; ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
<p><?php esc_html_e( 'The following options allow you to see if ISC was able to detect all images.', 'image-source-control-isc' ); ?></p>
<button id="isc-list-post-image-relation" class="button button-secondary"><?php esc_html_e( 'list post-image relations', 'image-source-control-isc' ); ?></button>
<p class="description"><?php esc_html_e( 'A list of posts and the images in them.', 'image-source-control-isc' ); ?>
	<?php esc_html_e( 'This index is used to generate the Per-post List.', 'image-source-control-isc' ); ?>
	<?php esc_html_e( 'Delete an entry to re-index a specific post.', 'image-source-control-isc' ); ?>
</p>
<div id="isc-post-image-relations"></div>
<hr/>
<button id="isc-list-image-post-relation" class="button button-secondary"><?php esc_html_e( 'list image-post relations', 'image-source-control-isc' ); ?></button>
<p class="description"><?php esc_html_e( 'A list of images and the posts they appear in.', 'image-source-control-isc' ); ?>
	<?php esc_html_e( 'This index is used to generate the Global List.', 'image-source-control-isc' ); ?>
</p>
<div id="isc-image-post-relations"></div>
<hr/>
<button id="isc-clear-index" class="button button-secondary"><?php esc_html_e( 'clear image-post index', 'image-source-control-isc' ); ?></button>
<p class="description"><?php esc_html_e( 'Click the button to remove the connections between images and posts as listed above.', 'image-source-control-isc' ); ?>
	<br/><?php esc_html_e( 'The index is rebuilt automatically when a page with images on it is visited in the frontend.', 'image-source-control-isc' ); ?></p>
<div id="isc-clear-index-feedback"></div>
<hr/>