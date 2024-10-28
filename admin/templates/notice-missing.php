<?php
/**
 * Render warning about number of missing sources.
 *
 * @var integer $missing_sources number of missing sources.
 * @var WP_Screen $screen current screen.
 */
?>
<div class="wrap">
	<div class="error isc-notice"><p>
		<?php
		printf(
			// translators: %d is the number of images without a known position
			esc_html( _n( '%s image has no credits.', '%s images have no credits.', absint( $missing_sources ), 'image-source-control-isc' ) ),
			absint( $missing_sources )
		);
		echo ' ';
		printf(
			wp_kses(
			// translators: %1$s is an opening link tag, %2$s is a closing tag.
				__( 'See the %1$smissing sources%2$s list.', 'image-source-control-isc' ),
				[
					'a' => [
						'href' => [],
					],
				]
			),
			'<a href="' . esc_url( admin_url( 'upload.php?page=isc-sources' ) ) . '">',
			'</a>'
		);
		// show a link to the settings page
		if ( $screen->id !== 'settings_page_isc-settings' ) {
			echo ' ';
			printf(
				wp_kses(
				// translators: %1$s is an opening link tag, %2$s is a closing tag.
					__( 'You can %1$sdisable%2$s this warning in the settings.', 'image-source-control-isc' ),
					[
						'a' => [
							'href' => [],
						],
					]
				),
				'<a href="' . esc_url( admin_url( 'options-general.php?page=isc-settings#isc_settings_section_misc' ) ) . '">',
				'</a>'
			);
		}
		?>
	</p></div>
</div>
