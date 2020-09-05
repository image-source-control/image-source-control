<?php
/**
 * Render the Thumbnail width setting
 *
 * @var array $options ISC options.
 */
?>
<input type="text" id="isc-settings-custom-width" name="isc_options[thumbnail_width]" class="small-text" value="<?php echo esc_attr( $options['thumbnail_width'] ); ?>" /> px
<p class="description"><?php esc_html_e( 'Custom value of the maximum allowed width for thumbnail.', 'image-source-control-isc' ); ?></p>
