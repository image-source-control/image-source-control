<?php
/**
 * Render the Complete Image List settings
 */
?>
<p>
<?php
	printf(
		// translators: %s is a shortcode.
		esc_html__( 'You can add a paginated list with ALL attachments and sources attached to posts and pages using the shortcode %s on any page.', 'image-source-control' ),
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
		esc_html__( 'Use %s to show all attachments in the list, including those not explicitly attached to a post.', 'image-source-control' ),
		wp_kses(
			'<code>[isc_list_all included="all"]</code>',
			array(
				'code' => array(),
			)
		)
	);
	?>
</p>
