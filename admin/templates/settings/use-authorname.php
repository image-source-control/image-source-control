<?php
/**
 * Render the Use Author Name setting
 *
 * @var array $options ISC options.
 */
?>
<label>
	<input type="checkbox" name="isc_options[use_authorname]" id="use_authorname" <?php checked( $options['use_authorname'] ); ?> />
	<?php esc_html_e( 'Use author name', 'image-source-control-isc' ); ?>
</label>
<p class="description"><?php esc_html_e( "Display the author's public name as source when the image is owned by the author (the uploader of the image, not necessarily the author of the post the image is displayed on). Uncheck to use a custom text instead.", 'image-source-control-isc' ); ?></p>
