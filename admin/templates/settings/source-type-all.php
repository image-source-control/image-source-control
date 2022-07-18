<?php
/**
 * Render the Complete Image List settings
 */
?>
<p>
<?php
	printf(
		// translators: %s is a shortcode.
		esc_html__( 'You can show a list with all images and sources from your Media library. Just place the shortcode %s on any page.', 'image-source-control-isc' ),
		wp_kses(
			'<code>[isc_list_all]</code>',
			array(
				'code' => array(),
			)
		)
	);
	?>
</p>
<p>
	<?php
	printf(
	// translators: %s is an starting link tag. %s is a closing link tag
		esc_html__( 'See the %1$sGlobal list%2$s settings to control the output.', 'image-source-control-isc' ),
		'<a href="#isc_settings_section_complete_list">',
		'</a>'
	);
	?>
</p>
