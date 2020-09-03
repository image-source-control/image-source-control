<?php
/**
 * Render the Debug Log setting
 *
 * @var bool $checked if the log file option is enabled.
 * @var string $log_file_url URL of the log file.
 */
?>
<input type="checkbox" name="isc_options[enable_log]" value="1" <?php checked( $checked ); ?>/>
<p class="description">
	<?php
	echo sprintf(
	// translators: $s is replaced by starting and ending a tags to create a link
		esc_html__( 'Writes image source activity to the %1$sisc.log%2$s file.', 'image-source-control-isc' ),
		'<a href="' . esc_url( $log_file_url ) . '" target="_blank">',
		'</a>'
	);
	?>
</p>
