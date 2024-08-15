<?php
/**
 * Render view for possibly unused attachments
 *
 * @var int $attachment_count number of attachments
 * @var int $files total number of image files (including scaled versions)
 * @var int $filesize total size of all attachments
 */
?>
<p>
<?php
if ( $attachment_count >= \ISC\Unused_Images::ESTIMATE_LIMIT ) {
	printf(
	// translators: %1$d is the number of unused attachments and %2$s the number of files
		esc_html__( 'At least %1$d unused images with %2$d files.', 'image-source-control-isc' ),
		(int) $attachment_count,
		(int) $files
	);
} else {
	printf(
	// translators: %1$d is the number of unused attachments and %2$d the number of files
		esc_html__( '%1$d possibly unused images with %2$d files.', 'image-source-control-isc' ),
		(int) $attachment_count,
		(int) $files
	);
}
if ( $filesize > 1000000 ) {
	echo ' ';
	printf(
	// translators: %s is the number of unused attachments
		esc_html__( 'They take up at least %s in disk space on your server.', 'image-source-control-isc' ),
		esc_html( size_format( $filesize ) )
	);
}
?>
</p>
<?php
if ( ! \ISC\Plugin::is_pro() ) :
	?>
	<a href="<?php echo esc_url( ISC_Admin::get_isc_localized_website_url( 'l/_unused-images', 'z/_ungenutzte-bilder', 'unused-images' ) ); ?>" target="_blank" class="button button-primary"><?php esc_html_e( 'Clean up unused images', 'image-source-control-isc' ); ?> (Pro)</a>
	<?php
else :
	?>
	<a href="<?php echo esc_url( admin_url( 'upload.php?page=isc-unused-images' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Clean up unused images', 'image-source-control-isc' ); ?></a>
	<?php
endif;
