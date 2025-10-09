<?php
/**
 * Render the Debug Log setting
 *
 * @var bool $checked if the log file option is enabled.
 * @var bool $file_exists true if the log file exists.
 * @var string $log_file_url URL of the log file.
 */

?>
<input type="checkbox" name="isc_options[enable_log]" value="1" <?php checked( $checked ); ?> id="isc-enable-log-checkbox"/>
<p class="description">
	<?php
	$file_link = 'image-source-control.log';

	if ( $file_exists ) {
		$file_link = '<a href="' . esc_url( $log_file_url ) . '" target="_blank">' . $file_link . '</a>';
	}
	printf(
		// translators: %1$s is a file name, %2$s is a URL parameter.
		esc_html__( 'Writes image source activity to the %1$s file when %2$s is added to the URL of a page.', 'image-source-control-isc' ),
		// phpcs:ignore WordPress.Security.EscapeOutput
		$file_link,
		'<code>?isc-log</code>'
	);
	?>
</p>
<div id="isc-log-url-wrapper" style="margin-top: 10px; display: <?php echo $checked ? 'block' : 'none'; ?>;">
	<input type="text" id="isc-log-url-field" value="<?php echo esc_attr( $log_file_url ); ?>" readonly style="width: 100%; max-width: 500px; height: 28px; line-height: 28px; padding: 0 8px; vertical-align: middle;" />
	<button type="button" id="isc-copy-log-url-btn" class="button" style="height: 28px; line-height: 26px; padding: 0 10px; vertical-align: middle;">
		<span class="dashicons dashicons-admin-page" style="line-height: 28px;"></span>
	</button>
	<?php if ( $file_exists ) : ?>
	<button type="button" id="isc-download-log-btn" class="button" style="height: 28px; line-height: 26px; padding: 0 10px; vertical-align: middle;">
		<span class="dashicons dashicons-download" style="line-height: 28px;"></span>
	</button>
	<?php endif; ?>
	<span class="dashicons dashicons-yes" id="isc-copy-log-url-success" style="color: #46b450; line-height: 28px; vertical-align: middle; display: none;"></span>
</div>
