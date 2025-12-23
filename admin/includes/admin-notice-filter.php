<?php

namespace ISC\Admin;

use ISC\Admin_Utils;

/**
 * Filter admin notices on ISC pages to only show whitelisted notices
 */
class Admin_Notice_Filter {

	/**
	 * List of callbacks that are allowed to display notices on ISC pages
	 *
	 * @var array
	 */
	private $whitelisted_callbacks = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		// Hook very early to filter admin notices before they are registered
		add_action( 'admin_notices', [ $this, 'filter_admin_notices' ], -9999 );
	}

	/**
	 * Filter admin notices by removing non-whitelisted callbacks
	 */
	public function filter_admin_notices() {
		// Only filter on ISC pages
		if ( ! Admin_Utils::is_isc_page() ) {
			return;
		}

		// Build the whitelist and remove non-whitelisted callbacks
		$this->build_whitelist();
	}

	/**
	 * Build the whitelist of allowed callbacks
	 */
	private function build_whitelist() {
		global $wp_filter;

		$this->whitelisted_callbacks = [];

		// Whitelist ISC's own notices
		$this->whitelisted_callbacks[] = [ \ISC\Admin::class, 'branded_admin_header' ];
		$this->whitelisted_callbacks[] = [ \ISC\Image_Sources\Admin_Notices::class, 'admin_notices' ];
		$this->whitelisted_callbacks[] = [ \ISC\Image_Sources\Admin_Media_Library_Filters::class, 'check_and_display_admin_notice' ];

		// Whitelist WordPress core settings notices
		$this->whitelisted_callbacks[] = 'settings_errors';

		/**
		 * Filter the list of whitelisted admin notice callbacks on ISC pages
		 *
		 * This filter allows developers to add their own callbacks to the whitelist
		 * so that their notices will be displayed on ISC admin pages.
		 *
		 * @since 3.6.2
		 *
		 * @param array $whitelisted_callbacks Array of callbacks that are allowed to display notices.
		 *                                     Callbacks can be either:
		 *                                     - String function names (e.g., 'my_custom_notice')
		 *                                     - Array with class and method (e.g., [ MyClass::class, 'my_method' ])
		 *
		 * @example
		 * // Add a custom notice callback to the whitelist
		 * add_filter( 'isc_admin_notice_whitelist', function( $whitelist ) {
		 *     $whitelist[] = 'my_custom_notice_function';
		 *     $whitelist[] = [ MyPlugin\Admin::class, 'display_notice' ];
		 *     return $whitelist;
		 * } );
		 */
		$this->whitelisted_callbacks = apply_filters( 'isc_admin_notice_whitelist', $this->whitelisted_callbacks );

		// Now remove non-whitelisted callbacks from the admin_notices hook
		if ( isset( $wp_filter['admin_notices'] ) ) {
			foreach ( $wp_filter['admin_notices']->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $key => $callback_data ) {
					if ( ! $this->is_callback_whitelisted( $callback_data['function'] ) ) {
						remove_action( 'admin_notices', $callback_data['function'], $priority );
					}
				}
			}
		}
	}

	/**
	 * Check if a callback is whitelisted
	 *
	 * @param callable $callback The callback to check.
	 *
	 * @return bool
	 */
	private function is_callback_whitelisted( $callback ): bool {
		foreach ( $this->whitelisted_callbacks as $whitelisted ) {
			if ( $this->callbacks_match( $callback, $whitelisted ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if two callbacks match
	 *
	 * @param callable $callback1 First callback.
	 * @param callable $callback2 Second callback.
	 *
	 * @return bool
	 */
	private function callbacks_match( $callback1, $callback2 ): bool {
		// Handle string function names
		if ( is_string( $callback1 ) && is_string( $callback2 ) ) {
			return $callback1 === $callback2;
		}

		// Handle array callbacks [class, method]
		if ( is_array( $callback1 ) && is_array( $callback2 ) ) {
			// Both should have 2 elements
			if ( count( $callback1 ) !== 2 || count( $callback2 ) !== 2 ) {
				return false;
			}

			// Compare class names (handle both object instances and class names)
			$class1 = is_object( $callback1[0] ) ? get_class( $callback1[0] ) : $callback1[0];
			$class2 = is_object( $callback2[0] ) ? get_class( $callback2[0] ) : $callback2[0];

			// Compare methods
			return $class1 === $class2 && $callback1[1] === $callback2[1];
		}

		return false;
	}
}
