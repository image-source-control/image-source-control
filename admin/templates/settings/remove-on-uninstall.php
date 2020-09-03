<?php
/**
 * Render the Remove On Uninstall setting
 *
 * @var bool $checked if the option is enabled.
 */
?>
<input type="checkbox" name="isc_options[remove_on_uninstall]" value="1" <?php checked( $checked ); ?>/>
<p class="description"><?php esc_html_e( 'Remove plugin options and image sources from the database when you delete the plugin.', 'image-source-control-isc' ); ?></p>
