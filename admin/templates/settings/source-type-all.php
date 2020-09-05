<?php
/**
 * Render the Complete Image List settings
 */
?>
<p>
<?php
	printf(
		// translators: %s is a shortcode.
		esc_html__( 'You can show a list with all images and sources from your Media library. Just place the shortcode %s on any page.', 'image-source-control' ),
		wp_kses(
			'<code>[isc_list_all]</code>',
			array(
				'code' => array(),
			)
		)
	);
	?>
	 <?php
		esc_html_e( 'By default, it lists only images that are actively used within post and page content.', 'image-source-control' );
		?>
</p>
<p>
	<?php
	printf(
	// translators: %s is a shortcode.
		esc_html__( 'Use %s to show only a limited number of images per page.', 'image-source-control' ),
		wp_kses(
			'<code>[isc_list_all per_page="25"]</code>',
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
	// translators: %s is a shortcode.
		esc_html__( 'Use %s to show all images in the Media library, regardless of whether they are placed within post content or not.', 'image-source-control' ),
		wp_kses(
			'<code>[isc_list_all included="all"]</code>',
			array(
				'code' => array(),
			)
		)
	);
	?>
</p>
