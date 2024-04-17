<?php
/**
 * Render the setting for the headline of source lists below the content
 *
 * @var array $options ISC options.
 */
?>
<input type="text" name="isc_options[image_list_headline]" id="list-head" value="<?php echo esc_attr( $options['image_list_headline'] ); ?>" class="regular-text" />
<p class="description"><?php esc_html_e( 'The headline of the image list added via shortcode or function in your theme.', 'image-source-control-isc' ); ?></p>