<?php
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
		return 'isc_' . hash( 'crc32', AUTH_KEY ) . '.log';
	}

	/**
	 * Check if the log feature is enabled
	 *
	 * @return bool
	 */
	public static function enabled(): bool {
		// true if the Debug Log option is enabled and the ?isc-log query parameter is set
		return ( ! empty( ISC_Class::get_instance()->get_isc_options()['enable_log'] ) && isset( $_GET['isc-log'] ) );
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

		$message = is_array( $message ) ? print_r( $message, true ) : $message;

		error_log( '[' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) . "] $message\n", 3, self::get_log_file_path() );
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
	public static function get_log_file_URL(): string {
		return ISCBASEURL . self::get_file_name();
	}

	/**
	 * Get the path to the log file
	 *
	 * @return string
	 */
	public static function get_log_file_path(): string {
		return ISCPATH . '/' . self::get_file_name();
	}
}
