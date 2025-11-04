<?php
/**
 * Render the Layout option for the Global List.
 *
 * Practically, there are no options here, but users regularly ask for layout tips, so we included the link to the manual here.
 *
 * @var string $manual_url URL to the manual page for this setting.
 */

?>
<p>
	<a href="<?php echo esc_url( $manual_url ); ?>" target="_blank"><?php esc_html_e( 'Manual', 'image-source-control-isc' ); ?></a>
</p>
