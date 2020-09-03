<?php
/**
 * Render the Exclude Own Images setting
 *
 * @var array $options ISC options.
 */
?>
<label>
	<input type="checkbox" name="isc_options[exclude_own_images]" id="exclude_own_images" <?php checked( $options['exclude_own_images'] ); ?> />
	<?php esc_html_e( 'Hide sources for own images', 'image-source-control-isc' ); ?>
</label>
<p class="description"><?php esc_html_e( "Exclude images marked as 'own image' from image lists (post and full) and overlay in the frontend. You can still manage them in the dashboard.", 'image-source-control-isc' ); ?></p>
