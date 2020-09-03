<?php
/**
 * Render the Position settings section
 *
 * @var array $options ISC options.
 */
?>
<div id="isc-settings-position">
<input type="hidden" name="isc_options[display_type]" value=""/>
<label>
	<input type="checkbox" name="isc_options[display_type][]" id="display-types-list" value="list" <?php checked( in_array( 'list', $options['display_type'], true ), true ); ?> />
	<?php
	esc_html_e( 'List below content', 'image-source-control-isc' );
	?>
</label>
<p class="description"><?php esc_html_e( 'Displays a list of image sources below singular pages.', 'image-source-control-isc' ); ?></p>

<label>
	<input type="checkbox" name="isc_options[display_type][]" id="display-types-overlay" value="overlay" <?php checked( in_array( 'overlay', $options['display_type'], true ), true ); ?> />
	<?php
	esc_html_e( 'Overlay', 'image-source-control-isc' );
	?>
</label>
<p class="description">
	<?php
	esc_html_e( 'Display image source as a simple overlay', 'image-source-control-isc' );
	?>
</p>
</div>
<?php
