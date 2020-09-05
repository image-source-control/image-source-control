<?php
/**
 * Render the Overlay Position setting
 *
 * @var array $options ISC options.
 */
?>
<select id="caption-pos" name="isc_options[cap_pos]">
	<?php foreach ( $this->caption_position as $pos ) : ?>
		<option value="<?php echo esc_attr( $pos ); ?>" <?php selected( $pos, $options['caption_position'] ); ?>><?php echo esc_html( $pos ); ?></option>
	<?php endforeach; ?>
</select>
<p class="description"><?php esc_html_e( 'Position of overlay into images', 'image-source-control-isc' ); ?></p>
