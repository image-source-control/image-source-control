<?php

namespace ISC\Tests\WPUnit\Includes;

use ISC\Settings;
use \ISC\Tests\WPUnit\WPTestCase;
use \ISC_Public;

/**
 * Test if ISC_Log file path and URL methods work correctly.
 */
class Log_Test extends WPTestCase {

	/**
	 * Clean up the test environment.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		delete_option( 'isc_options' );
		delete_option( 'isc_license_status' );
		\ISC_Log::delete_log_file();
		unset( $_GET['isc-log'], $_GET['isc-settings'], $_REQUEST['isc-log'], $_REQUEST['isc-settings'] );
	}

	/**
	 * Test if get_file_name() returns the correct filename with 'image-source-control_' prefix
	 */
	public function test_get_file_name_has_correct_prefix() {
		$file_name = \ISC_Log::get_file_name();

		// Check that it starts with 'image-source-control_'
		$this->assertStringStartsWith( 'image-source-control_', $file_name );

		// Check that it ends with '.log'
		$this->assertStringEndsWith( '.log', $file_name );
	}

	/**
	 * Test if get_log_file_path() returns a path in the wp-uploads directory
	 */
	public function test_get_log_file_path_in_uploads_dir() {
		$log_path = \ISC_Log::get_log_file_path();
		$upload_dir = wp_upload_dir();

		// Check that the log path starts with the uploads directory path
		$this->assertStringStartsWith( $upload_dir['basedir'], $log_path );

		// Check that the filename is included in the path
		$this->assertStringContainsString( 'image-source-control_', $log_path );
	}

	/**
	 * Test if get_log_file_url() returns a URL in the wp-uploads directory
	 */
	public function test_get_log_file_url_in_uploads_dir() {
		$log_url = \ISC_Log::get_log_file_url();
		$upload_dir = wp_upload_dir();

		// Check that the log URL starts with the uploads directory URL
		$this->assertStringStartsWith( $upload_dir['baseurl'], $log_url );

		// Check that the filename is included in the URL
		$this->assertStringContainsString( 'image-source-control_', $log_url );
	}

	/**
	 * Test if delete_log_file() removes the log file
	 */
	public function test_delete_log_file() {
		$log_path = \ISC_Log::get_log_file_path();

		// Create a dummy log file
		file_put_contents( $log_path, "Test log content\n" );

		// Verify the file exists
		$this->assertFileExists( $log_path );

		// Delete the log file
		\ISC_Log::delete_log_file();

		// Verify the file was deleted
		$this->assertFileDoesNotExist( $log_path );
	}

	/**
	 * Test ISC\Settings::admin_head_scripts().
	 */
	public function test_admin_head_scripts_does_not_log_settings() {
		update_option(
			'isc_options',
			[
				'enable_log' => true,
			]
		);

		$_GET['isc-log'] = 'default';
		$_GET['isc-settings'] = '1';
		$_REQUEST['isc-log'] = 'default';
		$_REQUEST['isc-settings'] = '1';

		set_current_screen( 'settings_page_isc-settings' );
		$settings = ( new \ReflectionClass( Settings::class ) )->newInstanceWithoutConstructor();

		ob_start();
		$settings->admin_head_scripts();
		ob_end_clean();

		$this->assertFileDoesNotExist( \ISC_Log::get_log_file_path() );
	}

	/**
	 * Test ISC_Log::maybe_log_settings.
	 */
	public function test_maybe_log_settings_requires_settings_parameter() {
		update_option(
			'isc_options',
			[
				'enable_log' => true,
			]
		);

		$_GET['isc-log'] = 'default';
		$_REQUEST['isc-log'] = 'default';

		\ISC_Log::maybe_log_settings();

		$this->assertFileDoesNotExist( \ISC_Log::get_log_file_path() );
	}

	/**
	 * Test ISC_Public::prepare_log().
	 */
	public function test_prepare_log_logs_settings_without_license_key() {
		update_option(
			'isc_options',
			[
				'enable_log'  => true,
				'images_only' => true,
				'license-key' => 'secret-license-key',
			]
		);
		update_option( 'isc_license_status', 'valid' );

		$_GET['isc-log'] = 'default';
		$_GET['isc-settings'] = '1';
		$_REQUEST['isc-log'] = 'default';
		$_REQUEST['isc-settings'] = '1';

		( new ISC_Public() )->prepare_log();

		$log_content = file_get_contents( \ISC_Log::get_log_file_path() );

		$this->assertStringContainsString( '=== ISC SETTINGS ===', $log_content );
		$this->assertStringContainsString( '[images_only] => 1', $log_content );
		$this->assertStringContainsString( 'isc_license_status: valid', $log_content );
		$this->assertStringNotContainsString( 'license-key', $log_content );
		$this->assertStringNotContainsString( 'secret-license-key', $log_content );
	}

	/**
	 * Test ISC_Log::maybe_log_settings.
	 */
	public function test_maybe_log_settings_logs_missing_license_status_as_dash() {
		update_option(
			'isc_options',
			[
				'enable_log' => true,
			]
		);

		$_GET['isc-log'] = 'default';
		$_GET['isc-settings'] = '1';
		$_REQUEST['isc-log'] = 'default';
		$_REQUEST['isc-settings'] = '1';

		\ISC_Log::maybe_log_settings();

		$this->assertStringContainsString( 'isc_license_status: -', file_get_contents( \ISC_Log::get_log_file_path() ) );
	}
}
