<?php

namespace ISC\Tests\WPUnit\Admin\Includes;

use ISC\Admin\Admin_Notice_Filter;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test ISC\Admin\Admin_Notice_Filter
 *
 * Tests the admin_notice_filter class which filters admin notices on ISC pages
 */
class Admin_Notice_Filter_Test extends WPTestCase {

	/**
	 * Test that get_callback_key correctly generates unique keys for string callbacks
	 */
	public function test_get_callback_key_with_string_callbacks(): void {
		$filter = new Admin_Notice_Filter();
		
		// Use reflection to test the private method
		$reflection = new \ReflectionClass( $filter );
		$method = $reflection->getMethod( 'get_callback_key' );
		$method->setAccessible( true );

		// Test string callback
		$result = $method->invoke( $filter, 'settings_errors' );
		$this->assertSame( 'string:settings_errors', $result, 'Expected string callback to have string: prefix' );

		// Test different string callbacks produce different keys
		$result1 = $method->invoke( $filter, 'settings_errors' );
		$result2 = $method->invoke( $filter, 'other_function' );
		$this->assertNotSame( $result1, $result2, 'Expected different string callbacks to have different keys' );
	}

	/**
	 * Test that get_callback_key correctly generates unique keys for array callbacks
	 */
	public function test_get_callback_key_with_array_callbacks(): void {
		$filter = new Admin_Notice_Filter();
		
		// Use reflection to test the private method
		$reflection = new \ReflectionClass( $filter );
		$method = $reflection->getMethod( 'get_callback_key' );
		$method->setAccessible( true );

		// Test array callback
		$callback = [ \ISC\Admin::class, 'branded_admin_header' ];
		$result = $method->invoke( $filter, $callback );
		$expected = 'array:ISC\Admin::branded_admin_header';
		$this->assertSame( $expected, $result, 'Expected array callback to have array: prefix with class::method' );

		// Test different array callbacks produce different keys
		$callback1 = [ \ISC\Admin::class, 'branded_admin_header' ];
		$callback2 = [ \ISC\Admin::class, 'different_method' ];
		$result1 = $method->invoke( $filter, $callback1 );
		$result2 = $method->invoke( $filter, $callback2 );
		$this->assertNotSame( $result1, $result2, 'Expected different methods to have different keys' );
	}

	/**
	 * Test that get_callback_key handles object instances correctly
	 */
	public function test_get_callback_key_with_object_instances(): void {
		$filter = new Admin_Notice_Filter();
		
		// Use reflection to test the private method
		$reflection = new \ReflectionClass( $filter );
		$method = $reflection->getMethod( 'get_callback_key' );
		$method->setAccessible( true );

		// Create an instance
		$instance = new \ISC\Admin();

		// Test object instance produces same key as class name
		$callback1 = [ $instance, 'branded_admin_header' ];
		$callback2 = [ \ISC\Admin::class, 'branded_admin_header' ];
		$result1 = $method->invoke( $filter, $callback1 );
		$result2 = $method->invoke( $filter, $callback2 );
		$this->assertSame( $result1, $result2, 'Expected object instance to produce same key as class name' );
	}

	/**
	 * Test that is_callback_whitelisted correctly identifies whitelisted callbacks
	 */
	public function test_is_callback_whitelisted(): void {
		$filter = new Admin_Notice_Filter();
		
		// Use reflection to access private method and property
		$reflection = new \ReflectionClass( $filter );
		$method = $reflection->getMethod( 'is_callback_whitelisted' );
		$method->setAccessible( true );
		
		$whitelist_property = $reflection->getProperty( 'whitelisted_callbacks' );
		$whitelist_property->setAccessible( true );
		
		// Set up a simple whitelist
		$whitelist_property->setValue( $filter, [
			'settings_errors',
			[ \ISC\Admin::class, 'branded_admin_header' ],
		] );

		// Test whitelisted string callback
		$result = $method->invoke( $filter, 'settings_errors' );
		$this->assertTrue( $result, 'Expected whitelisted string callback to return true' );

		// Test non-whitelisted string callback
		$result = $method->invoke( $filter, 'other_function' );
		$this->assertFalse( $result, 'Expected non-whitelisted string callback to return false' );

		// Test whitelisted array callback
		$result = $method->invoke( $filter, [ \ISC\Admin::class, 'branded_admin_header' ] );
		$this->assertTrue( $result, 'Expected whitelisted array callback to return true' );

		// Test non-whitelisted array callback
		$result = $method->invoke( $filter, [ \ISC\Admin::class, 'other_method' ] );
		$this->assertFalse( $result, 'Expected non-whitelisted array callback to return false' );
	}

	/**
	 * Test that the whitelist filter is properly applied
	 */
	public function test_whitelist_filter_applied(): void {
		// Add a custom callback to the whitelist
		$custom_callback = 'my_custom_notice';
		add_filter( 'isc_admin_notice_whitelist', function( $whitelist ) use ( $custom_callback ) {
			$whitelist[] = $custom_callback;
			return $whitelist;
		} );

		$filter = new Admin_Notice_Filter();
		
		// Use reflection to access private method and property
		$reflection = new \ReflectionClass( $filter );
		$build_method = $reflection->getMethod( 'build_whitelist_and_filter_callbacks' );
		$build_method->setAccessible( true );
		
		$whitelist_property = $reflection->getProperty( 'whitelisted_callbacks' );
		$whitelist_property->setAccessible( true );

		// Build the whitelist
		$build_method->invoke( $filter );
		$whitelist = $whitelist_property->getValue( $filter );

		// Check if our custom callback is in the whitelist
		$this->assertContains( $custom_callback, $whitelist, 'Expected custom callback to be in whitelist' );
	}
}
