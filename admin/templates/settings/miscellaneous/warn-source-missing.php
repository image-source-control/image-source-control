<?php
/**
 * Render the Warn about missing sources setting
 *
 * @var array $options ISC options.
 */
?>
<input type="checkbox" name="isc_options[warning_onesource_missing]" value="1" <?php checked( $options['warning_onesource_missing'] ); ?>/>
<p class="description"><?php esc_html_e( 'Display an admin notice in admin pages when one or more image sources are missing.', 'image-source-control-isc' ); ?></p>
