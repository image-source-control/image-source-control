<?php
/**
 * Render setting to enable Image source overlay
 *
 * @var array $options ISC options.
 */
?>
<div id="isc-settings-highlighted">
	<label>
		<input type="checkbox" name="isc_options[display_type][]" id="display-types-overlay" value="overlay" <?php checked( in_array( 'overlay', $options['display_type'], true ), true ); ?> />
		<?php
		esc_html_e( 'Overlay', 'image-source-control-isc' );
		?>
	</label>
	<p class="description">
		<?php
		esc_html_e( 'Display the image source as an overlay.', 'image-source-control-isc' );
		?>
		 <?php
			esc_html_e( 'It only works for images in the content.', 'image-source-control-isc' );
			?>
	</p>
</div>
<?php
