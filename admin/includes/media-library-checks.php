<?php

namespace ISC\Admin;

use ISC\Helpers;

/**
 * Display a warning on the attachment edit screen when the GUID does not match
 * the expected path.
 */
class Media_Library_Checks {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'add_warning_meta_box' ], 10, 2 );
	}

	/**
	 * Add a warning meta box when the GUID domain or path does not match the attachment metadata.
	 *
	 * @param string   $post_type Post type.
	 * @param \WP_Post $post      Attachment post object.
	 */
	public function add_warning_meta_box( $post_type, \WP_Post $post ) {
		if ( 'attachment' !== $post_type ) {
			return;
		}

		// Only add the meta box if there's any kind of GUID mismatch.
		if ( $this->has_guid_domain_mismatch( $post ) || $this->has_guid_path_mismatch( $post ) ) {
			add_meta_box(
				'isc-error-check',
				__( 'Error', 'image-source-control-isc' ),
				[ $this, 'render_warning_meta_box' ],
				'attachment',
				'side',
				'high'
			);
		}
	}

	/**
	 * Determine if the GUID domain does not match the site's domain.
	 *
	 * @param \WP_Post $post Attachment post object.
	 *
	 * @return bool True if domain mismatched, false otherwise.
	 */
	public function has_guid_domain_mismatch( \WP_Post $post ): bool {
		$host_guid = wp_parse_url( $post->guid, PHP_URL_HOST );
		$host_site = wp_parse_url( home_url(), PHP_URL_HOST );

		return $host_guid && $host_guid !== $host_site;
	}

	/**
	 * Determine if the GUID path (directory part) does not match the expected path from metadata.
	 * This comparison specifically ignores the filename itself, allowing for variations like '-scaled'.
	 *
	 * @param \WP_Post $post Attachment post object.
	 *
	 * @return bool True if path (directory) mismatched, false otherwise.
	 */
	public function has_guid_path_mismatch( \WP_Post $post ): bool {
		$meta = Helpers::maybe_unserialize( get_post_meta( $post->ID, '_wp_attachment_metadata', true ) );
		// Get the file path from metadata, remove leading slash for consistent pathinfo behavior.
		$meta_file = isset( $meta['file'] ) ? ltrim( $meta['file'], '/' ) : '';

		// If no meta file is available, we cannot determine a path mismatch.
		if ( empty( $meta_file ) ) {
			return false;
		}

		$path_guid    = wp_parse_url( $post->guid, PHP_URL_PATH );
		$uploads_path = wp_parse_url( wp_get_upload_dir()['baseurl'], PHP_URL_PATH );

		// Ensure uploads_path ends with a slash for consistent string manipulation.
		if ( substr( $uploads_path, -1 ) !== '/' ) {
			$uploads_path .= '/';
		}

		// Check if the GUID path starts with the uploads path. If not, it's a mismatch.
		// This handles cases where the GUID points to a completely different base directory.
		if ( strpos( $path_guid, $uploads_path ) !== 0 ) {
			return true;
		}

		// Get the relative path and filename from the GUID, e.g., '2025/07/ai-image-929772310.jpg'.
		$guid_relative_path_and_filename = substr( $path_guid, strlen( $uploads_path ) );

		// Extract the directory part from both the meta file path and the GUID's relative path.
		$meta_file_dirname     = pathinfo( $meta_file, PATHINFO_DIRNAME );
		$guid_relative_dirname = pathinfo( $guid_relative_path_and_filename, PATHINFO_DIRNAME );

		// If pathinfo returns '.' for the directory (meaning no directory part, just a filename),
		// treat it as an empty string for consistent comparison.
		if ( '.' === $meta_file_dirname ) {
			$meta_file_dirname = '';
		}
		if ( '.' === $guid_relative_dirname ) {
			$guid_relative_dirname = '';
		}

		// Compare the directory paths.
		return $meta_file_dirname !== $guid_relative_dirname;
	}

	/**
	 * Render the warning meta box content.
	 * Displays specific warnings based on the type of GUID mismatch.
	 *
	 * @param \WP_Post $post Attachment post object.
	 */
	public function render_warning_meta_box( \WP_Post $post ) {
		$domain_mismatch = $this->has_guid_domain_mismatch( $post );
		$path_mismatch   = $this->has_guid_path_mismatch( $post );

		if ( $domain_mismatch || $path_mismatch ) {
			echo '<p style="color: red;">' .
				esc_html__( 'The GUID URL does not match the site URL.', 'image-source-control-isc' ) . ' ' .
				esc_html__( 'This might indicate a migration issue.', 'image-source-control-isc' ) .
				'</p>';
		}
	}
}
