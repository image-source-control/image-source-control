<?php
/**
 * Render the setting to enable Licences
 *
 * @var array $options ISC options.
 */
?>
<input type="checkbox" name="isc_options[enable_licences]" id="isc-settings-licenses-enable" <?php checked( $options['enable_licences'] ); ?> />
<p class="description"><?php esc_html_e( 'Enable this to be able to add and display copyright/copyleft licenses for your images and manage them in the field below.', 'image-source-control-isc' ); ?></p>
