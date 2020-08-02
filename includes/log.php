<?php
/**
 * Log events when creating the image source lists
 */
class ISC_Log {

	/**
	 * Name of the log file
	 */
	const FILENAME = 'isc.log';

	/**
	 * Log image source extraction into a separate file
	 * can be used for debugging
	 * set define( 'ISC_LOG', true ); in wp-config.php to enable it
	 *
	 * @param string|array $message log message. Arrays will be converted into strings.
	 */
	public static function log( $message = '' ) {

		$options = ISC_Class::get_instance()->get_isc_options();

		if ( empty( $options['enable_log'] ) || null === $message ) {
			return;
		}

		// get calling function
		// source: https://stackoverflow.com/questions/190421/get-name-of-caller-function-in-php
		$trace            = debug_backtrace();
		$caller           = $trace[1];
		$calling_function = '';
		if ( isset( $caller['function'] ) ) {
			if ( isset( $caller['class'] ) ) {
				$calling_function .= "{$caller['class']}::{$caller['function']}";
			} else {
				$calling_function .= "{$caller['function']}";
			}
		}

		$message = is_array( $message ) ? print_r( $message, true ) : $message;

		error_log( '[' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) . ", $calling_function] $message \n", 3, self::get_log_file_path() );
	}

	/**
	 * Delete the log file without any conditions
	 */
	public static function delete_log_file() {
		wp_delete_file( self::get_log_file_path() );
	}

	/**
	 * Get the URL to the log file
	 */
	public static function get_log_file_URL() {
		return ISCBASEURL . self::FILENAME;
	}

	/**
	 * Get the path to the log file
	 */
	public static function get_log_file_path() {
		return ISCPATH . '/' . self::FILENAME;
	}
}
