<div class="wrap metabox-holder">
	<form id="isc-section-wrapper" method="post" action="options.php">
		<?php
		ISC_Admin::do_settings_sections( 'isc_settings_page' );
		settings_fields( 'isc_options_group' );
		?>
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Save Changes' ); ?>">
		</p>
	</form>
</div>
