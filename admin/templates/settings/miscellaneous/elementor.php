<?php
/**
 * Render the Elementor setting
 */
?>
<label>
	<input type="checkbox" disabled="disabled">
	<?php echo ISC_Admin::get_pro_link( 'elementor' ); ?>
	<?php esc_html_e( 'Enable support for Elementor background images.', 'image-source-control-isc' ); ?>
</label>