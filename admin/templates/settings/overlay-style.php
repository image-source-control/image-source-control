<?php
/**
 * Render the Overlay Style setting
 *
 * @var array $caption_style Caption style
 */
?>
<label><input type="checkbox" name="isc_options[caption_style]" value="none" <?php checked( $caption_style, 'none' ); ?>/>
	<?php esc_html_e( 'Remove markup and style', 'image-source-control-isc' ); ?>
</label>
<p class="description"><?php esc_html_e( 'Deliver the overlay content without any markup and style.', 'image-source-control-isc' ); ?>
	<?php
	echo sprintf(
	// translators: $s is replaced with the name of the script file no longer enqueued when the option with this label is selected
		esc_html__( 'Removes also %s.', 'image-source-control-isc' ),
		'<code>captions.js</code>'
	);
	?>
  </p>
