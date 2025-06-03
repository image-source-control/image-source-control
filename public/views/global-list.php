<?php
/**
 * Render the global list of image sources
 * @var array $options plugin options.
 * @var array $atts attachment information.
 *
 * Added comment `isc_stop_overlay` as a class to the table to suppress overlays within it starting at that point
 * todo: allow overlays to start again after the table
 **/
?>
<div class="isc_all_image_list_box isc_stop_overlay" style="overflow: scroll;">
	<table>
		<thead>
		<?php if ( $options['thumbnail_in_list'] ) : ?>
			<th><?php esc_html_e( 'Thumbnail', 'image-source-control-isc' ); ?></th>
		<?php endif; ?>
		<th><?php esc_html_e( 'Attachment ID', 'image-source-control-isc' ); ?></th>
		<th><?php esc_html_e( 'Title', 'image-source-control-isc' ); ?></th>
		<th><?php esc_html_e( 'Attached to', 'image-source-control-isc' ); ?></th>
		<th><?php esc_html_e( 'Source', 'image-source-control-isc' ); ?></th>
		</thead>
		<tbody>
		<?php foreach ( $atts as $id => $data ) : ?>
			<?php
			$source = ISC\Image_Sources\Renderer\Image_Source_String::get( $id );
			?>
			<tr>
				<?php
				$v_align = '';
				if ( $options['thumbnail_in_list'] ) :
					$v_align = 'style="vertical-align: top;"';
					?><td><?php \ISC\Image_Sources\Renderer\Global_List::render_global_list_thumbnail( $id ); ?></td><?php
				endif; ?>
				<td <?php echo $v_align; ?>><?php echo $id; ?></td>
				<td <?php echo $v_align; ?>><?php echo $data['title']; ?></td>
				<td <?php echo $v_align; ?>><?php echo $data['posts']; ?></td>
				<td <?php echo $v_align; ?>><?php echo $source; ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table></div>