<?php
/**
 * Render the setting to remove the source wrapper for AI labels without source text.
 *
 * @var array $options ISC options.
 */

?>
<label>
	<input type="checkbox" name="isc_options[ai_images][remove_wrapper_if_source_empty]" <?php checked( ! empty( $options['ai_images']['remove_wrapper_if_source_empty'] ) ); ?> />
	<?php esc_html_e( 'Show the plain AI label when the source text is empty.', 'image-source-control-isc' ); ?>
</label>
