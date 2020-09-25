<?php
/**
 * Render the Exclude setting for the "Use default source" option
 *
 * @var array $options ISC options.
 */
?>
<p><?php esc_html_e( 'Choose how to handle images with the “Use default source” option enabled.', 'image-source-control-isc' ); ?></p>
<br/>
<div class="isc-settings-highlighted isc-settings-default-source">
<label>
	<input type="radio" name="isc_options[default_source]" <?php checked( $options['exclude_own_images'] ); ?> />
	<?php esc_html_e( 'Exclude from lists', 'image-source-control-isc' ); ?>
</label>
<p class="description"><?php esc_html_e( 'Hide images sources in the frontend. You can still manage them in the dashboard.', 'image-source-control-isc' ); ?></p>
<label>
	<input type="radio" name="isc_options[default_source]" <?php checked( $options['use_authorname'] ); ?> />
	<?php esc_html_e( 'Author name', 'image-source-control-isc' ); ?>
</label>
<p class="description"><?php esc_html_e( 'Use the uploader’s public name as the image source.', 'image-source-control-isc' ); ?></p>
<label>
	<input type="radio" name="isc_options[default_source]" id="isc-custom-text-select" <?php checked( $options['by_author_text'] ); ?> />
	<?php esc_html_e( 'Custom text', 'image-source-control-isc' ); ?>
</label>
<input type="text" id="isc-custom-text" name="isc_options[by_author_text]" value="<?php echo esc_attr( $options['by_author_text'] ); ?>" <?php disabled( $options['use_authorname'] ); ?> class="regular-text" placeholder="<?php esc_html_e( 'Owned by the author', 'image-source-control' ); ?>"/>
</div>
