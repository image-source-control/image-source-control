<?php
/**
 * Render the Thumbnail height setting
 *
 * @var array $options ISC options.
 */
?>
<input type="text" id="custom-height" name="isc_options[thumbnail_height]" class="small-text" value="<?php echo esc_attr( $options['thumbnail_height'] ); ?>"/> px
<p class="description"><?php esc_html_e( 'Custom value of the maximum allowed height for thumbnail.', 'image-source-control-isc' ); ?></p>