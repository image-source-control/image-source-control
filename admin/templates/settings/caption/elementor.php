<?php
/**
 * Render the Elementor setting
 */

?>
<label>
	<input type="checkbox" disabled="disabled">
	<?php echo ISC\Admin_Utils::get_pro_link( 'elementor' ); ?>
	<?php
	printf(
	// translators: %s is the name of the theme or page builder, e.g. Divi.
		esc_html__( 'Enable support for %s background images.', 'image-source-control-isc' ),
		'Elementor'
	);
	?>
</label>