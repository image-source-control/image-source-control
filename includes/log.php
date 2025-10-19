<?php

use ISC\Plugin;

/**
 * Log events when creating the image source lists
 */
class ISC_Log {

	/**
	 * Get the name of the log file
	 *
	 * @return string
	 */
	public static function get_file_name(): string {
		// Hash the AUTH_KEY to create a unique but persistent filename
		return 'image-source-control_' . hash( 'crc32', AUTH_KEY ) . '.log';
	}

	/**
	 * Check the log type
	 *
	 * @param string $type Type of the log depth.
	 *
	 * @return bool
	 */
	public static function is_type( string $type ): bool {
		return self::get_type() === $type;
	}

	/**
	 * Return the log type
	 *
	 * Type of the log depth
	 * - default
	 * - content - logs the content
	 * - backtrace - logs the function backtrace
	 */
	private static function get_type(): string {
		$accepted_types = [ 'default', 'content', 'backtrace' ];

		// phpcs:ignore WordPress.Security.NonceVerification
		$isc_log = sanitize_key( wp_unslash( $_GET['isc-log'] ?? '' ) );

		return in_array( $isc_log, $accepted_types, true ) ? $isc_log : 'default';
	}

	/**
	 * Check if the log feature is enabled
	 *
	 * @return bool
	 */
	public static function enabled(): bool {
		// true if the Debug Log option is enabled and the ?isc-log query parameter is set
		// phpcs:ignore WordPress.Security.NonceVerification
		return ( ! empty( Plugin::get_options()['enable_log'] ) && isset( $_REQUEST['isc-log'] ) );
	}

	/**
	 * Log image source extraction into a separate file
	 * can be used for debugging
	 * set define( 'ISC_LOG', true ); in wp-config.php to enable it
	 *
	 * @param string|array $message log message. Arrays will be converted into strings.
	 */
	public static function log( $message = '' ) {

		if ( ! self::enabled() || null === $message ) {
			return;
		}

		// get the current function name
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
		$method    = sprintf( '%s:%s', $backtrace[1]['class'] ?? '', $backtrace[1]['function'] );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$message = is_array( $message ) ? print_r( $message, true ) : $message;

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) . "] $method: $message\n", 3, self::get_log_file_path() );
	}

	/**
	 * Delete the log file without any conditions
	 */
	public static function delete_log_file() {
		wp_delete_file( self::get_log_file_path() );
	}

	/**
	 * Get the URL to the log file
	 *
	 * @return string
	 */
	public static function get_log_file_url(): string {
		$upload_dir = wp_upload_dir();
		return $upload_dir['baseurl'] . '/' . self::get_file_name();
	}

	/**
	 * Get the path to the log file
	 *
	 * @return string
	 */
	public static function get_log_file_path(): string {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . '/' . self::get_file_name();
	}

	/**
	 * Check if the log file exists
	 *
	 * @return bool
	 */
	public static function log_file_exists(): bool {
		return file_exists( self::get_log_file_path() );
	}

	/**
	 * Return true if internal caches should be ignored
	 * only works in combination with an activated log
	 */
	public static function ignore_caches(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification
		return self::enabled() && isset( $_GET['isc-ignore-cache'] );
	}

	/**
	 * Return true if existing indexer data (Pro) should be ignored
	 * only works in combination with an activated log
	 */
	public static function ignore_index(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification
		return self::enabled() && isset( $_GET['isc-ignore-index'] );
	}

	/**
	 * Return true if the log should be cleared automatically before writing
	 */
	public static function clear_log(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification
		return self::enabled() && isset( $_GET['isc-clear-log'] );
	}

	/**
	 * Print a concise stack trace in the log
	 */
	public static function log_stack_trace() {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );

		// remove the "args" key to reduce the output size
		foreach ( $backtrace as $key => $trace ) {
			unset( $backtrace[ $key ]['args'] );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		self::log( 'Stack trace: ' . "\n - " . print_r( $backtrace, true ) );
	}
}
