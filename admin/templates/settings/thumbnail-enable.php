<?php
/**
 * Render the Thumbnail setting to enable the use of thumbnails
 *
 * @var array $options ISC options.
 * @var array $sizes available sizes for images.
 */
?>
<input type="checkbox" id="use-thumbnail" name="isc_options[use_thumbnail]" value="1" <?php checked( $options['thumbnail_in_list'] ); ?> />
<select id="thumbnail-size-select" name="isc_options[size_select]" <?php disabled( ! $options['thumbnail_in_list'] ); ?>>
	<?php foreach ( $sizes as $_name => $_sizes ) : ?>
		<option value="<?php echo esc_html( $_name ); ?>" <?php selected( $_name, $options['thumbnail_size'] ); ?>>
			<?php
			echo esc_html( $_name );
			if ( is_array( $_sizes ) && isset( $_sizes['width'] ) && isset( $_sizes['height'] ) ) :
				echo esc_html( sprintf( ' (%1$dx%2$d)', $_sizes['width'], $_sizes['height'] ) );
			endif;
			?>

		</option>
	<?php endforeach; ?>
</select>
<p class="description"><?php esc_html_e( 'Display thumbnails on the list of all images in the blog.', 'image-source-control-isc' ); ?></p>
