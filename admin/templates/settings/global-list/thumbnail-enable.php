<?php
/**
 * Render the Thumbnail settings for the Global List
 *
 * @var array $options ISC options.
 * @var array $sizes available sizes for images.
 * @var array $sizes_labels translatable labels for the sizes.
 */
?>
<label>
	<input type="checkbox" id="use-thumbnail" name="isc_options[thumbnail_in_list]" value="1" <?php checked( $options['thumbnail_in_list'] ); ?> />
	<?php esc_html_e( 'Thumbnail', 'image-source-control-isc' ); ?>
</label>
<select id="thumbnail-size-select" name="isc_options[thumbnail_size]" <?php echo ! $options['thumbnail_in_list'] ? 'class="hidden"' : ''; ?>>
	<?php foreach ( $sizes as $_name => $_sizes ) : ?>
		<option value="<?php echo esc_html( $_name ); ?>" <?php selected( $_name, $options['thumbnail_size'] ); ?>>
			<?php
			echo isset( $sizes_labels[ $_name ] ) ? esc_html( $sizes_labels[ $_name ] ) : esc_html( $_name );
			if ( is_array( $_sizes ) && isset( $_sizes['width'] ) && isset( $_sizes['height'] ) ) :
				echo esc_html( sprintf( ' (%1$dx%2$d)', $_sizes['width'], $_sizes['height'] ) );
			endif;
			?>
		</option>
	<?php endforeach; ?>
</select>
<span id="isc-settings-custom-size" class="<?php echo ! $options['thumbnail_in_list'] ? 'hidden' : ''; ?>">
	<input type="text" name="isc_options[thumbnail_width]" class="small-text" value="<?php echo esc_attr( $options['thumbnail_width'] ); ?>" />
	×
	<input type="text" name="isc_options[thumbnail_height]" class="small-text" value="<?php echo esc_attr( $options['thumbnail_height'] ); ?>"/>
</span>
<br/>