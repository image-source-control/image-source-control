<?php
/**
 * Test the license class
 */
namespace ISC\Tests\WPUnit;

//require_once dirname( __FILE__, 6 ) . '/pro/admin/includes/unused-images.php';

//use \ISC_Pro_Admin_Unused_Images;

class License_Test extends \Codeception\TestCase\WPTestCase {

	private $instance;

	protected function _before() {
		parent::_before();
		$this->instance = new \ISC\Pro\Admin\License();
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