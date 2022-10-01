<?php
/**
 * Render the Images per page setting
 *
 * @var array $images_per_page number of images to display per page.
 */
?>
<input type="number" id='images-per-page' name="isc_options[images_per_page]" value="<?php echo esc_attr( $images_per_page ); ?>" />
<p class="description"><?php esc_html_e( 'The number of entries before a pagination is added.', 'image-source-control-isc' ); ?>
 <?php esc_html_e( 'Use 0 to show all entries.', 'image-source-control-isc' ); ?></p>
