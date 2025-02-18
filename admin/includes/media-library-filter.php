<?php

namespace ISC\Admin;

use ISC\Admin_Utils;

/**
 * Add a filter field to the Media Library view.
 */
class Media_Library_Filter {

	/**
	 * Media_Library_Filter constructor.
	 */
	public function __construct() {
		add_action( 'restrict_manage_posts', [ $this, 'add_media_library_filter' ] );
	}

	/**
	 * Add a filter to the Media Library list view.
	 */
	public function add_media_library_filter() {
		Admin_Utils::is_media_library_list_view_page();

		$filters = apply_filters( 'isc_admin_media_library_filters', [] );

		if ( ! is_array( $filters ) || empty( $filters ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filter_current = isset( $_GET['isc_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['isc_filter'] ) ) : '';

		include ISCPATH . '/admin/templates/media-library-filter.php';
	}
}