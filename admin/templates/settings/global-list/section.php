<?php
/**
 * Render the Global List settings section
 */

?>
<p class="description">
<?php
	printf(
		// translators: %s is a shortcode.
		esc_html__( 'You can show a list with all images and sources from your Media library. Just place the shortcode %s on any page.', 'image-source-control-isc' ),
		wp_kses(
			'<code>[isc_list_all]</code>',
			[
				'code' => [],
			]
		)
	);
	?>
</p>