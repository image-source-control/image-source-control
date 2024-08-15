<?php
/**
 * Render the Exclude setting for the "Use standard source" option
 *
 * @var string $standard_source      value of the Standard Source option
 * @var string $standard_source_text text in the Standard Source Text option
 */
?>
<p><?php esc_html_e( 'Choose how to handle images with the “Use standard source” option enabled.', 'image-source-control-isc' ); ?></p>
<br/>
<div class="isc-settings-highlighted isc-settings-standard-source">
	<label>
		<input type="radio" name="isc_options[standard_source]" value="exclude" <?php checked( $standard_source, 'exclude' ); ?> />
		<?php esc_html_e( 'Exclude from lists', 'image-source-control-isc' ); ?>
	</label>
	<p class="description"><?php esc_html_e( 'Hide image sources in the frontend.', 'image-source-control-isc' ); ?></p>
	<label>
		<input type="radio" name="isc_options[standard_source]" value="author_name" <?php checked( $standard_source, 'author_name' ); ?> />
		<?php esc_html_e( 'Author name', 'image-source-control-isc' ); ?>
	</label>
	<p class="description"><?php esc_html_e( 'Use the uploader’s public name as the image source.', 'image-source-control-isc' ); ?></p>
	<label>
		<input type="radio" name="isc_options[standard_source]" id="isc-custom-text-select" value="custom_text" <?php checked( $standard_source, 'custom_text' ); ?> />
		<?php esc_html_e( 'Custom text', 'image-source-control-isc' ); ?>
	</label>
	<input type="text" id="isc-custom-text" name="isc_options[standard_source_text]" value="<?php echo esc_attr( $standard_source_text ); ?>" <?php disabled( $standard_source != 'custom_text' ); ?> class="regular-text" placeholder="<?php echo esc_attr( ISC\Standard_Source::get_standard_source_text() ); ?>"/>
	<?php do_action( 'isc_admin_settings_standard_source_options', $standard_source ); ?>
	<?php if ( ! \ISC\Plugin::is_pro() ) : ?>
		<br/>
		<label>
			<input type="radio" name="isc_options[standard_source]" <?php checked( $standard_source, 'wp_caption' ); ?> disabled/>
			<?php echo ISC_Admin::get_pro_link( 'standard-source-caption' ); ?>
			<?php esc_html_e( 'Caption', 'image-source-control-isc' ); ?>
		</label>
	<p class="description">
		<?php
		esc_html_e( 'Use the caption entered in the media library.', 'image-source-control-isc' );
		?>
	</p>
		<br/>
		<label>
			<input type="radio" name="isc_options[standard_source]" <?php checked( $standard_source, 'iptc' ); ?> disabled/>
			<?php echo ISC_Admin::get_pro_link( 'standard-source-iptc' ); ?>
			<?php esc_html_e( 'IPTC meta data', 'image-source-control-isc' ); ?>
		</label>
	<?php endif; ?>
</div>
<br/>
<?php if ( ! \ISC\Plugin::is_pro() ) : ?>
	<label>
		<input type="checkbox" disabled="disabled">
		<?php echo ISC_Admin::get_pro_link( 'standard-source' ); ?>
		<?php esc_html_e( 'Show the standard source for all images that don’t have a source.', 'image-source-control-isc' ); ?>
	</label>
<?php endif; ?>
