<?php

namespace ISC\Admin;

/**
 * Handle admin-related AJAX calls
 */
class Admin_Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_isc_download_log', [ $this, 'download_log' ] );
	}

	/**
	 * Download log file via AJAX
	 */
	public function download_log() {
		check_ajax_referer( 'isc-admin-ajax-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have permission to access this file.', 403 );
		}

		$log_file_path = \ISC_Log::get_log_file_path();

		if ( ! \ISC_Log::log_file_exists() ) {
			wp_die( 'Log file does not exist.', 404 );
		}

		// Get file size for Content-Length header
		$file_size = filesize( $log_file_path );

		// Clear any output buffers
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		// Set headers for file download
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( basename( $log_file_path ) ) . '"' );
		header( 'Content-Length: ' . $file_size );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output the file content. readfile is supposedly more efficient than WP_Filesystem for large files.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $log_file_path );

		die();
	}
}