<?php
/**
 * Render the Exclude setting for the "Use default source" option
 *
 * @var string $default_source value of the Default Source option
 * @var string $default_source_text text in the Default Source Text option
 */
?>
<p><?php esc_html_e( 'Choose how to handle images with the “Use default source” option enabled.', 'image-source-control-isc' ); ?></p>
<br/>
<div class="isc-settings-highlighted isc-settings-default-source">
<label>
	<input type="radio" name="isc_options[default_source]" value="exclude" <?php checked( $default_source, 'exclude' ); ?> />
	<?php esc_html_e( 'Exclude from lists', 'image-source-control-isc' ); ?>
</label>
<p class="description"><?php esc_html_e( 'Hide images sources in the frontend. You can still manage them in the dashboard.', 'image-source-control-isc' ); ?></p>
<label>
	<input type="radio" name="isc_options[default_source]" value="author_name" <?php checked( $default_source, 'author_name' ); ?> />
	<?php esc_html_e( 'Author name', 'image-source-control-isc' ); ?>
</label>
<p class="description"><?php esc_html_e( 'Use the uploader’s public name as the image source.', 'image-source-control-isc' ); ?></p>
<label>
	<input type="radio" name="isc_options[default_source]" id="isc-custom-text-select" value="custom_text" <?php checked( $default_source, 'custom_text' ); ?> />
	<?php esc_html_e( 'Custom text', 'image-source-control-isc' ); ?>
</label>
<input type="text" id="isc-custom-text" name="isc_options[default_source_text]" value="<?php echo esc_attr( $default_source_text ); ?>" <?php disabled( $default_source != 'custom_text' ); ?> class="regular-text" placeholder="<?php echo esc_attr( $this->get_default_source_text() ); ?>"/>
</div>
