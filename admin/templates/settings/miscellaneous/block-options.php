<?php
/**
 * Render the block options setting
 *
 * @var bool $checked if the block option is enabled.
 * @var bool $disabled if the block option is disabled.
 */

?>
<label>
	<input type="checkbox" name="isc_options[block_options]" value="1" <?php checked( $checked ); ?> <?php disabled( $disabled ); ?>/>
	<?php esc_html_e( 'Show source options in block settings.', 'image-source-control-isc' ); ?>
	<?php esc_html_e( 'If disabled, the source options will show in the media library overlay.', 'image-source-control-isc' ); ?>
</label>