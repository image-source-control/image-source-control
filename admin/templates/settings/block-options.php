<?php
/**
 * Render the block options setting
 *
 * @var bool $checked if the block options option is enabled.
 */
?>
<label>
	<input type="checkbox" name="isc_options[block_options]" value="1" <?php checked( $checked ); ?>/>
	<?php esc_html_e( 'Show source options in block settings.', 'image-source-control-isc' ); ?>
</label>
