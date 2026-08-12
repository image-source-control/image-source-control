<?php
/**
 * Render the setting to enable AI image labels.
 *
 * @var array $options ISC options.
 */

?>
<label>
	<input type="checkbox" name="isc_options[ai_images][show_label]" id="isc-settings-ai-images-enable" <?php checked( ! empty( $options['ai_images']['show_label'] ) ); ?> />
	<?php esc_html_e( 'Choose a label for AI-generated images.', 'image-source-control-isc' ); ?>
</label>
<p><a href="<?php echo esc_url( ISC\Admin_Utils::get_manual_url( 'settings-ai-images' ) ); ?>" target="_blank"><?php esc_html_e( 'Manual', 'image-source-control-isc' ); ?></a></p>
