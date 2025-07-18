<?php

namespace ISC\Admin;

use ISC\Helpers;

/**
 * Display a warning on the attachment edit screen when the GUID does not match
 * the expected path.
 */
class Guid_Warning {
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
	 * @param \WP_Post $post	  Attachment post object.
	 */
	public function add_warning_meta_box( $post_type, \WP_Post $post ) {
		if ( $post_type !== 'attachment' || ! $this->has_guid_mismatch( $post ) ) {
			return;
		}

		add_meta_box(
			'isc-guid-warning',
			__( 'Warning', 'image-source-control-isc' ),
			[ $this, 'render_warning_meta_box' ],
			'attachment',
			'side',
			'high'
		);
	}

	/**
	 * Determine if the GUID does not match the expected domain or path.
	 *
	 * @param \WP_Post $post Attachment post object.
	 *
	 * @return bool True if mismatched, false otherwise.
	 */
	private function has_guid_mismatch( \WP_Post $post ): bool {
		$host_guid = wp_parse_url( $post->guid, PHP_URL_HOST );
		$host_site = wp_parse_url( home_url(), PHP_URL_HOST );

		$meta	   = Helpers::maybe_unserialize( get_post_meta( $post->ID, '_wp_attachment_metadata', true ) );
		$meta_file = isset( $meta['file'] ) ? '/' . ltrim( $meta['file'], '/' ) : '';

		$path_guid	  = wp_parse_url( $post->guid, PHP_URL_PATH );
		$uploads_path = wp_parse_url( wp_get_upload_dir()['baseurl'], PHP_URL_PATH );
		$expected	  = $uploads_path . $meta_file;

		$wrong_domain = $host_guid && $host_guid !== $host_site;
		$wrong_path	  = $meta_file && $path_guid !== $expected;

		return $wrong_domain || $wrong_path;
	}

	/**
	 * Render the warning meta box content.
	 *
	 * @param \WP_Post $post Attachment post object.
	 */
	public function render_warning_meta_box( \WP_Post $post ) {
		echo '<p>' . esc_html__( 'The attachment URL does not match the current site. Please update the GUID or reupload the image.', 'image-source-control-isc' ) . '</p>';
	}
}

