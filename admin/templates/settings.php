<div class="wrap metabox-holder">
	<h1><?php esc_html_e( 'ISC settings', 'image-source-control-isc' ); ?></h1>
<p>
	<?php
	printf(
		wp_kses(
		// translators: %1$s is a starting a-tag, %2$s is the closing one.
			__( 'You can manage and debug image sources under %1$sMedia > Image Sources%2$s.', 'image-source-control-isc' ),
			array(
				'a' => array(
					'href' => array(),
				),
			)
		),
		'<a href="' . esc_url( admin_url( 'upload.php?page=isc-sources' ) ) . '">',
		'</a>'
	);
	?>
</p>
	<form id="image-settings-form" method="post" action="options.php">
		<?php
		ISC_Admin::do_settings_sections( 'isc_settings_page' );
		settings_fields( 'isc_options_group' );
		?>
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Save Changes' ); ?>">
		</p>
	</form>
</div>
