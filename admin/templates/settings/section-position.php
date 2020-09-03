<?php
/**
 * Render the Position section settings
 */
?>
<p class="description"><?php esc_html_e( 'Choose where to display image sources in the frontend', 'image-source-control-isc' ); ?></p>
<p>
	<?php
	printf(
		wp_kses(
		// translators: %1$s is the beginning link tag, %2$s is the closing one.
			__( 'If you don’t want to use any of these methods, you can still place the image source list manually as described %1$shere%2$s', 'image-source-control-isc' ),
			array(
				'a' => array( 'href' ),
			)
		),
		'<a href="http://webgilde.com/en/image-source-control/image-sources-frontend/" target="_blank">',
		'</a>'
	)
	?>
</p>