<?php
/**
 * The class is responsible for locating and loading the autoloader file used in the plugin.
 */

namespace ISC;

/**
 * Autoloader.
 */
class Autoloader {

	/**
	 * Hold autoloader.
	 *
	 * @var mixed
	 */
	private $autoloader;

	/**
	 * Main instance
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @return Autoloader
	 */
	public static function get(): Autoloader {
		static $instance;

		if ( null === $instance ) {
			$instance = new Autoloader();
		}

		return $instance;
	}

	/**
	 * Get hold autoloader.
	 *
	 * @return mixed
	 */
	public function get_autoloader() {
		return $this->autoloader;
	}

	/**
	 * Get plugin directory.
	 *
	 * @return string
	 */
	public function get_directory(): string {
		return ISCPATH;
	}

	/**
	 * Runs this initializer.
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->autoloader = require $this->locate();
	}

	/**
	 * Locate the autoload file
	 *
	 * This function searches for the autoload file in the packages directory and vendor directory.
	 *
	 * @return bool|string
	 */
	private function locate() {
		$directory = $this->get_directory();
		$lib       = $directory . 'lib/autoload.php';
		$vendors   = $directory . 'vendor/autoload.php';

		if ( is_readable( $lib ) && ( ! self::is_test() ) ) {
			return $lib;
		}

		if ( is_readable( $vendors ) ) {
			return $vendors;
		}

		return false;
	}

	/**
	 * Check if this is a test run.
	 *
	 * @return bool
	 */
	public static function is_test(): bool {
		return ( isset( $_SERVER['TEST_SITE_USER_AGENT'] ) && $_SERVER['TEST_SITE_USER_AGENT'] === 'ISC_Test' ); // wp-browser unit test
	}
}