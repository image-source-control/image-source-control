<?php
/**
 * Render the setting to enable AI image labels.
 *
 * @var array $options ISC options.
 */

?>
<label>
	<input type="checkbox" name="isc_options[enable_ai_images]" id="isc-settings-ai-images-enable" <?php checked( ! empty( $options['enable_ai_images'] ) ); ?> />
	<?php
	echo wp_kses_post(
		sprintf(
			/* translators: %1$s is an opening link tag, %2$s is the closing one. */
			__( 'Choose a label for AI-generated images. See the %1$smanual page%2$s.', 'image-source-control-isc' ),
			'<a href="https://example.com/manual/ai-images" target="_blank">',
			'</a>'
		)
	);
	?>
</label>
