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
	 * Array elements can be:
	 * - String function names (e.g., 'settings_errors')
	 * - Array with class and method (e.g., [ MyClass::class, 'my_method' ])
	 *
	 * @var array
	 */
	private $whitelisted_callbacks = [];

	/**
	 * Lookup table of whitelisted callback keys for fast checking
	 *
	 * @var array
	 */
	private $whitelisted_keys = [];

	/**
	 * Whether the whitelist has been built
	 *
	 * @var bool
	 */
	private $whitelist_built = false;

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

		// Build the whitelist only once per request
		if ( ! $this->whitelist_built ) {
			$this->build_whitelist_and_filter_callbacks();
			$this->whitelist_built = true;
		}
	}

	/**
	 * Build the whitelist of allowed callbacks and remove non-whitelisted ones
	 */
	private function build_whitelist_and_filter_callbacks() {
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

		// Build the lookup table for fast checking
		$this->whitelisted_keys = [];
		foreach ( $this->whitelisted_callbacks as $callback ) {
			$key = $this->get_callback_key( $callback );
			$this->whitelisted_keys[ $key ] = true;
		}

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
		// Generate a unique key for this callback and check in the lookup table
		$callback_key = $this->get_callback_key( $callback );
		return isset( $this->whitelisted_keys[ $callback_key ] );
	}

	/**
	 * Generate a unique key for a callback
	 *
	 * @param callable $callback The callback.
	 *
	 * @return string
	 */
	private function get_callback_key( $callback ): string {
		if ( is_string( $callback ) ) {
			return 'string:' . $callback;
		}

		if ( is_array( $callback ) && count( $callback ) === 2 ) {
			$class = is_object( $callback[0] ) ? get_class( $callback[0] ) : $callback[0];
			return 'array:' . $class . '::' . $callback[1];
		}

		// For other types, use serialization as fallback
		return 'other:' . serialize( $callback );
	}
}
