<?php
/**
 * Renders the call-to-action for the filtered Media Library view of missing-image sources.
 */
$missing_sources = (int) get_transient( 'isc-show-missing-sources-warning' );
if ( ! $missing_sources ) {
	$missing_sources = ISC_Model::update_missing_sources_transient();
}
?>
<p>
<?php
printf(
	esc_html( _n( '%s image has no credits.', '%s images have no credits.', $missing_sources, 'image-source-control-isc' ) ),
	absint( $missing_sources )
);
?>
</p>
<p>
<?php esc_html_e( 'Open the Media Library filtered to images without sources so you can view and edit them there.', 'image-source-control-isc' ); ?>
</p>
<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'upload.php?mode=list&isc_filter=without_source' ) ); ?>"><?php esc_html_e( 'Show and edit missing sources', 'image-source-control-isc' ); ?></a>