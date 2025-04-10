<?php
/**
 * Template for the images only option
 *
 * @var bool $checked Whether the option is checked
 */

?>
<label>
	<input id="isc-settings-plugin-images-only" type="checkbox" name="isc_options[images_only]" value="1" <?php checked( $checked ); ?> />
</label>
<p class="description">
	<?php esc_html_e( 'When enabled, all plugin functions will only work with image files and ignore other media types.', 'image-source-control-isc' ); ?>
</p>
<p class="hidden" id="isc-settings-plugin-images-only-indexer">
	<i class="dashicons dashicons-info"></i>
	<?php
	if ( \ISC\Plugin::is_pro() ) :
		$open_a  = '<a href="' . admin_url( 'options.php?page=isc-indexer' ) . '">';
		$close_a = '</a>';
	else :
		// don’t link to the indexer page since it is not
		$open_a  = '';
		$close_a = '';
	endif;
	printf(
	// translators: %%1$s is an opening link tag, %2$s is the closing one
		esc_html__( 'Run the %1$sIndexer%2$s to update all data at once.', 'image-source-control-isc' ),
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$open_a,
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$close_a
	);
	?>
	<?php
	if ( ! \ISC\Plugin::is_pro() ) :
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo \ISC\Admin_Utils::get_pro_link( 'settings-images-only-indexer' );
	endif;
	?>
</p>
<p class="hidden" id="isc-settings-plugin-images-only-cleanup-wrapper">
	<label>
		<input type="checkbox" name="isc_options[images_only_cleanup]" value="1" />
		<?php esc_html_e( 'Remove all data from non-image files.', 'image-source-control-isc' ); ?>
	</label>
</p>
