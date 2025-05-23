<?php
namespace ISC\Tests\WPUnit\Pro\Admin\Includes;

use \ISC\Tests\WPUnit\WPTestCase;

/**
 * Test the license class
 */
class License_Test extends WPTestCase {

	private $instance;

	/**
	 * Set up the test environment before each test method.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->instance = new \ISC\Pro\Admin\License();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		delete_option( 'isc_license' );
		delete_option( 'isc_license_status' );
		delete_option( 'isc_license_expires' );

		// Reset the instance if needed, though PHPUnit creates a new instance per test.
		$this->instance = null;

		parent::tearDown();
	}

	/**
	 * Test if get_account_url() returns the DE domain for DE license keys
	 */
	public function test_get_account_url_de() {
		$this->assertEquals( 'https://shop.imagesourcecontrol.de/', $this->instance->get_account_url( 'DE312' ) );
	}

	/**
	 * Test if get_account_url() returns the EN domain for EN license keys
	 */
	public function test_get_account_url_en() {
		$this->assertEquals( 'https://shop.imagesourcecontrol.com/', $this->instance->get_account_url( 'EN312' ) );
	}

	/**
	 * Test get_license()
	 */
	public function test_get_license() {
		update_option( 'isc_license', 'DE312' );
		$this->assertEquals( 'DE312', $this->instance->get_license() );
	}

	/**
	 * Test get_license_status()
	 */
	public function test_get_license_status() {
		update_option( 'isc_license_status', 'valid' );
		$this->assertEquals( 'valid', $this->instance->get_license_status() );
	}

	/**
	 * Test get_license_expires()
	 */
	public function test_get_license_expires() {
		update_option( 'isc_license_expires', '2020-12-31' );
		$this->assertEquals( '2020-12-31', $this->instance->get_license_expires() );
	}

	/**
	 * Test is_valid()
	 */
	public function test_is_valid() {
		update_option( 'isc_license_status', 'valid' );
		$this->assertTrue( $this->instance->is_valid() );
	}

}