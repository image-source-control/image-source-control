<?php
/**
 * Render setting to enable Image source overlay
 *
 * @var array $options ISC options.
 */
?>
<div class="isc-settings-highlighted">
	<label>
		<input type="checkbox" name="isc_options[display_type][]" id="isc-settings-overlay-enable" value="overlay" <?php checked( in_array( 'overlay', $options['display_type'], true ), true ); ?> />
		<?php
		esc_html_e( 'Display the image source as an overlay.', 'image-source-control-isc' );
		?>
	</label>
</div>
<?php
