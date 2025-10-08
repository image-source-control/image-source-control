<?php

namespace ISC\Tests\WPUnit\Includes;

use \ISC\Tests\WPUnit\WPTestCase;

/**
 * Test if ISC_Log file path and URL methods work correctly.
 */
class Log_Test extends WPTestCase {

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
}
