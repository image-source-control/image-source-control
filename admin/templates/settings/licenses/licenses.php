<?php
/**
 * Render the Licences setting to change default license texts
 *
 * @var array $options ISC options.
 */

?>
<div id="isc-settings-licenses">
	<textarea name="isc_options[licences]"><?php echo esc_html( $options['licences'] ); ?></textarea>
	<p class="description"><?php esc_html_e( 'List of licenses the author can choose for an image. Enter a license per line and separate the name from the optional link with a pipe symbol (e.g. CC BY 2.0|http://creativecommons.org/licenses/by/2.0/legalcode).', 'image-source-control-isc' ); ?></p>
</div>