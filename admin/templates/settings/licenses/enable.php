<?php
/**
 * Render the setting to enable Licenses
 *
 * @var array $options ISC options.
 */

?>
<label>
	<input type="checkbox" name="isc_options[enable_licences]" id="isc-settings-licenses-enable" <?php checked( $options['enable_licences'] ); ?> />
	<?php esc_html_e( 'Add and display copyright licenses for your images.', 'image-source-control-isc' ); ?>
</label>
