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
	 * Test that callbacks_match correctly identifies matching string callbacks
	 */
	public function test_callbacks_match_with_string_callbacks(): void {
		$filter = new Admin_Notice_Filter();
		
		// Use reflection to test the private method
		$reflection = new \ReflectionClass( $filter );
		$method = $reflection->getMethod( 'callbacks_match' );
		$method->setAccessible( true );

		// Test identical string callbacks
		$result = $method->invoke( $filter, 'settings_errors', 'settings_errors' );
		$this->assertTrue( $result, 'Expected identical string callbacks to match' );

		// Test different string callbacks
		$result = $method->invoke( $filter, 'settings_errors', 'other_function' );
		$this->assertFalse( $result, 'Expected different string callbacks to not match' );
	}

	/**
	 * Test that callbacks_match correctly identifies matching array callbacks
	 */
	public function test_callbacks_match_with_array_callbacks(): void {
		$filter = new Admin_Notice_Filter();
		
		// Use reflection to test the private method
		$reflection = new \ReflectionClass( $filter );
		$method = $reflection->getMethod( 'callbacks_match' );
		$method->setAccessible( true );

		// Test matching class/method arrays
		$callback1 = [ \ISC\Admin::class, 'branded_admin_header' ];
		$callback2 = [ \ISC\Admin::class, 'branded_admin_header' ];
		$result = $method->invoke( $filter, $callback1, $callback2 );
		$this->assertTrue( $result, 'Expected matching class/method callbacks to match' );

		// Test different methods
		$callback1 = [ \ISC\Admin::class, 'branded_admin_header' ];
		$callback2 = [ \ISC\Admin::class, 'different_method' ];
		$result = $method->invoke( $filter, $callback1, $callback2 );
		$this->assertFalse( $result, 'Expected different methods to not match' );

		// Test different classes
		$callback1 = [ \ISC\Admin::class, 'branded_admin_header' ];
		$callback2 = [ \ISC\Settings::class, 'branded_admin_header' ];
		$result = $method->invoke( $filter, $callback1, $callback2 );
		$this->assertFalse( $result, 'Expected different classes to not match' );
	}

	/**
	 * Test that callbacks_match handles object instances correctly
	 */
	public function test_callbacks_match_with_object_instances(): void {
		$filter = new Admin_Notice_Filter();
		
		// Use reflection to test the private method
		$reflection = new \ReflectionClass( $filter );
		$method = $reflection->getMethod( 'callbacks_match' );
		$method->setAccessible( true );

		// Create an instance
		$instance = new \ISC\Admin();

		// Test object instance against class name
		$callback1 = [ $instance, 'branded_admin_header' ];
		$callback2 = [ \ISC\Admin::class, 'branded_admin_header' ];
		$result = $method->invoke( $filter, $callback1, $callback2 );
		$this->assertTrue( $result, 'Expected object instance to match class name' );
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
		$build_method = $reflection->getMethod( 'build_whitelist' );
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
