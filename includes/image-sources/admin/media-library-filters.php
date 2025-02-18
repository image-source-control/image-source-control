<?php

namespace ISC\Image_Sources;

use ISC\Admin_Utils;

/**
 * Handle filters in the Media Library
 */
class Admin_Media_Library_Filters {

	/**
	 * Media_Library_Filters constructor.
	 */
	public function __construct() {
		add_action( 'isc_admin_media_library_filters', [ $this, 'add_media_library_filter' ] );
		add_action( 'pre_get_posts', [ $this, 'filter_media_library' ] );
		add_action( 'admin_notices', [ $this, 'check_and_display_admin_notice' ] );
	}

	/**
	 * Add filters to the Media Library list view.
	 *
	 * @param array $filters The current filters.
	 */
	public function add_media_library_filter( array $filters ): array {

		$filters[] = [
			'value' => 'with_source',
			'label' => __( 'Images with sources', 'image-source-control-isc' ),
		];

		$filters[] = [
			'value' => 'without_source',
			'label' => __( 'Images without sources', 'image-source-control-isc' ),
		];

		return $filters;
	}

	/**
	 * Filter the media library based on the selected filter.
	 *
	 * @param \WP_Query $query The current query.
	 */
	public function filter_media_library( \WP_Query $query ) {
		Admin_Utils::is_media_library_list_view_page();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filter = isset( $_GET['isc_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['isc_filter'] ) ) : '';

		if ( $filter === 'with_source' ) {
			$query->set(
				'meta_query',
				[
					'relation' => 'OR',
					[
						'key'     => 'isc_image_source_own',
						'compare' => 'EXISTS',
					],
					[
						'relation' => 'AND',
						[
							'key'     => 'isc_image_source',
							'compare' => 'EXISTS',
						],
						[
							'key'     => 'isc_image_source',
							'value'   => '',
							'compare' => '!=',
						],
					],
				]
			);
		} elseif ( $filter === 'without_source' ) {
			$query->set(
				'meta_query',
				[
					'relation' => 'AND',
					// images with empty or missing source string that are not using the standard source option
					[
						'relation' => 'OR',
						[
							'key'     => 'isc_image_source_own',
							'value'   => '1',
							'compare' => '!=',
						],
						[
							'key'     => 'isc_image_source_own',
							'compare' => 'NOT EXISTS',
						],
					],
					[
						'relation' => 'OR',
						[
							'key'     => 'isc_image_source',
							'value'   => '',
							'compare' => '=',
						],
						[
							'key'     => 'isc_image_source',
							'compare' => 'NOT EXISTS',
						],
					],
				]
			);
		}
	}

	/**
	 * Check if the Image Sources column is enabled.
	 *
	 * @return bool
	 */
	private function is_image_sources_column_enabled(): bool {
		$user           = wp_get_current_user();
		$screen         = get_current_screen();
		$option         = "manage{$screen->id}columnshidden";
		$hidden_columns = get_user_option( $option, $user->ID );

		return ! in_array( 'isc_fields', $hidden_columns, true );
	}

	/**
	 * Display an admin notice if the Image Sources column is not enabled in list view.
	 */
	public function check_and_display_admin_notice() {
		Admin_Utils::is_media_library_list_view_page();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filter = isset( $_GET['isc_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['isc_filter'] ) ) : '';

		if ( $filter && ! $this->is_image_sources_column_enabled() ) {
			echo '<div class="notice notice-error">';
			echo '<p>' . esc_html__( 'The Image Sources column is hidden. Please enable it in the Screen Options to bulk-edit image sources.', 'image-source-control-isc' ) . '</p>';
			// add pro link
			if ( ! \ISC\Plugin::is_pro() ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<p>' . \ISC\Admin_Utils::get_pro_link( 'media-library-missing-image-sources-column-notice' ) . '</p>';
			}
			echo '</div>';
		}
	}
}
