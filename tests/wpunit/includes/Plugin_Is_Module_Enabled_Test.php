<?php

namespace ISC\Tests\WPUnit\Includes;

use \ISC\Tests\WPUnit\WPTestCase;
use ISC\Plugin;

/**
 * Test the method ISC\Plugin::is_module_enabled
 */
class Plugin_Is_Module_Enabled_Test extends WPTestCase {
	protected function setUp(): void {
		parent::setUp();
		// Clean up options before each test
		delete_option( 'isc_options' );
	}

	protected function tearDown(): void {
		// Clean up after each test
		delete_option( 'isc_options' );
		parent::tearDown();
	}

	public function test_module_enabled_by_default() {
		// When no options are set, all modules should be enabled by default
		$this->assertTrue(
			Plugin::is_module_enabled( 'test_module' ),
			'Modules should be enabled by default when no options are set'
		);
	}

	public function test_module_enabled_with_empty_modules_array() {
		// Set options with empty modules array
		update_option( 'isc_options', [ 'modules' => [] ] );

		$this->assertFalse(
			Plugin::is_module_enabled( 'test_module' ),
			'Module should be disabled when modules array is empty'
		);
	}

	public function test_module_enabled_when_in_modules_array() {
		// Set options with specific module enabled
		update_option( 'isc_options', [
			'modules' => [ 'test_module', 'another_module' ]
		] );

		$this->assertTrue(
			Plugin::is_module_enabled( 'test_module' ),
			'Module should be enabled when present in modules array'
		);
	}

	public function test_module_disabled_when_not_in_modules_array() {
		// Set options with other modules but not the test module
		update_option( 'isc_options', [
			'modules' => [ 'different_module', 'another_module' ]
		] );

		$this->assertFalse(
			Plugin::is_module_enabled( 'test_module' ),
			'Module should be disabled when not present in modules array'
		);
	}

	public function test_module_enabled_with_invalid_modules_option() {
		// Set options with invalid modules value (not an array)
		update_option( 'isc_options', [
			'modules' => 'invalid_value'
		] );

		$this->assertFalse(
			Plugin::is_module_enabled( 'test_module' ),
			'Module should be disabled when modules option is not an array'
		);
	}
}