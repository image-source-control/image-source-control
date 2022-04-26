<?php
/**
 * Render the Overlay Position setting
 *
 * @var array $options ISC options.
 */
?>
<label for="caption-pos"><?php esc_html_e( 'Overlay position', 'image-source-control-isc' ); ?></label>
<select id="caption-pos" name="isc_options[caption_position]">
	<?php foreach ( $this->caption_position as $pos ) : ?>
		<option value="<?php echo esc_attr( $pos ); ?>" <?php selected( $pos, $options['caption_position'] ); ?>><?php echo esc_html( $pos ); ?></option>
	<?php endforeach; ?>
</select>
<p class="description"><?php esc_html_e( 'Position of overlay into images', 'image-source-control-isc' ); ?></p>
