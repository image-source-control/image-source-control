<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Admin;

use ISC\Pro\Unused_Images;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing \ISC\Pro\Unused_Images AJAX ignore/unignore handlers
 */
class Unused_Images_Ignore_Ajax_Test extends WPTestCase {

	/**
	 * Admin user ID
	 *
	 * @var int
	 */
	protected $admin_user_id;

	/**
	 * Test attachment ID
	 *
	 * @var int
	 */
	protected $attachment_id;

	/**
	 * Unused_Images instance
	 *
	 * @var Unused_Images
	 */
	protected $unused_images;

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create admin user with manage_options capability
		$this->admin_user_id = $this->factory->user->create( [
			'role' => 'administrator',
		] );

		// Set admin as current user
		wp_set_current_user( $this->admin_user_id );

		// Create a test attachment
		$this->attachment_id = $this->factory->attachment->create( [
			'post_title'     => 'Test Image for AJAX',
			'post_mime_type' => 'image/jpeg',
		] );

		// Initialize Unused_Images instance
		$this->unused_images = new Unused_Images();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		// Call parent tearDown first
		parent::tearDown();

		// Reset $_POST and $_REQUEST
		$_POST = [];
		$_REQUEST = [];

		// Clean up transient cache
		delete_transient( 'isc_has_ignored_images' );
	}

	/**
	 * Test AJAX ignore image success
	 *
	 * Tests: \ISC\Pro\Unused_Images::ajax_ignore_image()
	 */
	public function test_ajax_ignore_image_success() {
		// Set up AJAX request
		$_POST['action'] = 'isc-unused-images-ignore';
		$_REQUEST['action'] = 'isc-unused-images-ignore';
		$_POST['nonce'] = wp_create_nonce( 'isc-admin-ajax-nonce' );
		$_REQUEST['nonce'] = $_POST['nonce'];
		$_POST['image_id'] = $this->attachment_id;
		$_REQUEST['image_id'] = $this->attachment_id;

		// Capture output
		ob_start();
		$this->unused_images->ajax_ignore_image();
		$output = ob_get_clean();

		// Verify the response
		$response = json_decode( $output, true );
		$this->assertIsArray( $response, 'Response should be a JSON array' );
		$this->assertTrue( $response['success'], 'Response should indicate success' );

		// Verify the image was actually ignored
		$this->assertTrue( Unused_Images::is_ignored( $this->attachment_id ), 'Image should be marked as ignored' );
	}

	/**
	 * Test AJAX unignore image success
	 *
	 * Tests: \ISC\Pro\Unused_Images::ajax_unignore_image()
	 */
	public function test_ajax_unignore_image_success() {
		// First, mark the image as ignored
		Unused_Images::set_ignored_status( $this->attachment_id, true );
		$this->assertTrue( Unused_Images::is_ignored( $this->attachment_id ), 'Image should be ignored before test' );

		// Set up AJAX request
		$_POST['action'] = 'isc-unused-images-unignore';
		$_REQUEST['action'] = 'isc-unused-images-unignore';
		$_POST['nonce'] = wp_create_nonce( 'isc-admin-ajax-nonce' );
		$_REQUEST['nonce'] = $_POST['nonce'];
		$_POST['image_id'] = $this->attachment_id;
		$_REQUEST['image_id'] = $this->attachment_id;

		// Capture output
		ob_start();
		$this->unused_images->ajax_unignore_image();
		$output = ob_get_clean();

		// Verify the response
		$response = json_decode( $output, true );
		$this->assertIsArray( $response, 'Response should be a JSON array' );
		$this->assertTrue( $response['success'], 'Response should indicate success' );

		// Verify the image was actually unignored
		$this->assertFalse( Unused_Images::is_ignored( $this->attachment_id ), 'Image should not be marked as ignored' );
	}

	/**
	 * Test AJAX ignore fails without nonce
	 *
	 * Tests: \ISC\Pro\Unused_Images::ajax_ignore_image()
	 */
	public function test_ajax_ignore_fails_without_nonce() {
		// Set up AJAX request without nonce
		$_POST['action'] = 'isc-unused-images-ignore';
		$_REQUEST['action'] = 'isc-unused-images-ignore';
		$_POST['image_id'] = $this->attachment_id;
		$_REQUEST['image_id'] = $this->attachment_id;

		// Capture output
		ob_start();
		$this->unused_images->ajax_ignore_image();
		$output = ob_get_clean();

		// Verify the error response
		$response = json_decode( $output, true );
		$this->assertIsArray( $response, 'Response should be a JSON array' );
		$this->assertFalse( $response['success'], 'Response should indicate failure' );

		// Verify the image was not ignored
		$this->assertFalse( Unused_Images::is_ignored( $this->attachment_id ), 'Image should not be ignored without valid nonce' );
	}

	/**
	 * Test AJAX ignore fails with invalid nonce
	 *
	 * Tests: \ISC\Pro\Unused_Images::ajax_ignore_image()
	 */
	public function test_ajax_ignore_fails_with_invalid_nonce() {
		// Set up AJAX request with invalid nonce
		$_POST['action'] = 'isc-unused-images-ignore';
		$_REQUEST['action'] = 'isc-unused-images-ignore';
		$_POST['nonce'] = 'invalid_nonce_value';
		$_REQUEST['nonce'] = 'invalid_nonce_value';
		$_POST['image_id'] = $this->attachment_id;
		$_REQUEST['image_id'] = $this->attachment_id;

		// Capture output
		ob_start();
		$this->unused_images->ajax_ignore_image();
		$output = ob_get_clean();

		// Verify the error response
		$response = json_decode( $output, true );
		$this->assertIsArray( $response, 'Response should be a JSON array' );
		$this->assertFalse( $response['success'], 'Response should indicate failure' );

		// Verify the image was not ignored
		$this->assertFalse( Unused_Images::is_ignored( $this->attachment_id ), 'Image should not be ignored with invalid nonce' );
	}

	/**
	 * Test AJAX ignore fails without manage_options capability
	 *
	 * Tests: \ISC\Pro\Unused_Images::ajax_ignore_image()
	 */
	public function test_ajax_ignore_fails_without_manage_options_capability() {
		// Create a subscriber user (without manage_options)
		$subscriber_id = $this->factory->user->create( [
			'role' => 'subscriber',
		] );
		wp_set_current_user( $subscriber_id );

		// Set up AJAX request
		$_POST['action'] = 'isc-unused-images-ignore';
		$_REQUEST['action'] = 'isc-unused-images-ignore';
		$_POST['nonce'] = wp_create_nonce( 'isc-admin-ajax-nonce' );
		$_REQUEST['nonce'] = $_POST['nonce'];
		$_POST['image_id'] = $this->attachment_id;
		$_REQUEST['image_id'] = $this->attachment_id;

		// Capture output
		ob_start();
		$this->unused_images->ajax_ignore_image();
		$output = ob_get_clean();

		// Verify the error response
		$response = json_decode( $output, true );
		$this->assertIsArray( $response, 'Response should be a JSON array' );
		$this->assertFalse( $response['success'], 'Response should indicate failure' );

		// Verify the image was not ignored
		$this->assertFalse( Unused_Images::is_ignored( $this->attachment_id ), 'Image should not be ignored without proper capability' );
	}

	/**
	 * Test AJAX ignore fails with missing image_id
	 *
	 * Tests: \ISC\Pro\Unused_Images::ajax_ignore_image()
	 */
	public function test_ajax_ignore_fails_with_missing_image_id() {
		// Set up AJAX request without image_id
		$_POST['action'] = 'isc-unused-images-ignore';
		$_REQUEST['action'] = 'isc-unused-images-ignore';
		$_POST['nonce'] = wp_create_nonce( 'isc-admin-ajax-nonce' );
		$_REQUEST['nonce'] = $_POST['nonce'];

		// Capture output
		ob_start();
		$this->unused_images->ajax_ignore_image();
		$output = ob_get_clean();

		// Verify the error response
		$response = json_decode( $output, true );
		$this->assertIsArray( $response, 'Response should be a JSON array' );
		$this->assertFalse( $response['success'], 'Response should indicate failure' );
	}

	/**
	 * Test AJAX ignore fails with invalid image_id
	 *
	 * Tests: \ISC\Pro\Unused_Images::ajax_ignore_image()
	 */
	public function test_ajax_ignore_fails_with_invalid_image_id() {
		// Set up AJAX request with non-numeric image_id
		$_POST['action'] = 'isc-unused-images-ignore';
		$_REQUEST['action'] = 'isc-unused-images-ignore';
		$_POST['nonce'] = wp_create_nonce( 'isc-admin-ajax-nonce' );
		$_REQUEST['nonce'] = $_POST['nonce'];
		$_POST['image_id'] = 'not_a_number';
		$_REQUEST['image_id'] = 'not_a_number';

		// Capture output
		ob_start();
		$this->unused_images->ajax_ignore_image();
		$output = ob_get_clean();

		// Verify the error response
		$response = json_decode( $output, true );
		$this->assertIsArray( $response, 'Response should be a JSON array' );
		$this->assertFalse( $response['success'], 'Response should indicate failure' );
	}

	/**
	 * Test AJAX ignore fails with zero image_id
	 *
	 * Tests: \ISC\Pro\Unused_Images::ajax_ignore_image()
	 */
	public function test_ajax_ignore_fails_with_zero_image_id() {
		// Set up AJAX request with zero image_id
		$_POST['action'] = 'isc-unused-images-ignore';
		$_REQUEST['action'] = 'isc-unused-images-ignore';
		$_POST['nonce'] = wp_create_nonce( 'isc-admin-ajax-nonce' );
		$_REQUEST['nonce'] = $_POST['nonce'];
		$_POST['image_id'] = 0;
		$_REQUEST['image_id'] = 0;

		// Capture output
		ob_start();
		$this->unused_images->ajax_ignore_image();
		$output = ob_get_clean();

		// Verify the error response
		$response = json_decode( $output, true );
		$this->assertIsArray( $response, 'Response should be a JSON array' );
		$this->assertFalse( $response['success'], 'Response should indicate failure' );
	}

	/**
	 * Test AJAX ignore clears transient cache
	 *
	 * Tests: \ISC\Pro\Unused_Images::ajax_ignore_image()
	 */
	public function test_ajax_ignore_clears_transient_cache() {
		// Set a transient value
		set_transient( 'isc_has_ignored_images', false, HOUR_IN_SECONDS );
		$this->assertNotFalse( get_transient( 'isc_has_ignored_images' ), 'Transient should exist before AJAX call' );

		// Set up AJAX request
		$_POST['action'] = 'isc-unused-images-ignore';
		$_REQUEST['action'] = 'isc-unused-images-ignore';
		$_POST['nonce'] = wp_create_nonce( 'isc-admin-ajax-nonce' );
		$_REQUEST['nonce'] = $_POST['nonce'];
		$_POST['image_id'] = $this->attachment_id;
		$_REQUEST['image_id'] = $this->attachment_id;

		// Capture output
		ob_start();
		$this->unused_images->ajax_ignore_image();
		ob_get_clean();

		// Verify transient was cleared
		$transient = get_transient( 'isc_has_ignored_images' );
		$this->assertFalse( $transient, 'Transient should be deleted after AJAX action' );
	}

	/**
	 * Test multiple AJAX ignore requests
	 *
	 * Tests: \ISC\Pro\Unused_Images::ajax_ignore_image()
	 * Tests: \ISC\Pro\Unused_Images::ajax_unignore_image()
	 */
	public function test_multiple_ajax_ignore_requests() {
		$attachment_id_2 = $this->factory->attachment->create( [
			'post_title'     => 'Test Image 2',
			'post_mime_type' => 'image/jpeg',
		] );

		// First ignore request
		$_POST['action'] = 'isc-unused-images-ignore';
		$_REQUEST['action'] = 'isc-unused-images-ignore';
		$_POST['nonce'] = wp_create_nonce( 'isc-admin-ajax-nonce' );
		$_REQUEST['nonce'] = $_POST['nonce'];
		$_POST['image_id'] = $this->attachment_id;
		$_REQUEST['image_id'] = $this->attachment_id;

		ob_start();
		$this->unused_images->ajax_ignore_image();
		ob_get_clean();

		$this->assertTrue( Unused_Images::is_ignored( $this->attachment_id ), 'First image should be ignored' );

		// Second ignore request for different image
		$_POST['image_id'] = $attachment_id_2;
		$_REQUEST['image_id'] = $attachment_id_2;
		$_POST['nonce'] = wp_create_nonce( 'isc-admin-ajax-nonce' );
		$_REQUEST['nonce'] = $_POST['nonce'];

		ob_start();
		$this->unused_images->ajax_ignore_image();
		ob_get_clean();

		$this->assertTrue( Unused_Images::is_ignored( $attachment_id_2 ), 'Second image should be ignored' );

		// Unignore the first image
		$_POST['action'] = 'isc-unused-images-unignore';
		$_REQUEST['action'] = 'isc-unused-images-unignore';
		$_POST['image_id'] = $this->attachment_id;
		$_REQUEST['image_id'] = $this->attachment_id;
		$_POST['nonce'] = wp_create_nonce( 'isc-admin-ajax-nonce' );
		$_REQUEST['nonce'] = $_POST['nonce'];

		ob_start();
		$this->unused_images->ajax_unignore_image();
		ob_get_clean();

		$this->assertFalse( Unused_Images::is_ignored( $this->attachment_id ), 'First image should not be ignored' );
		$this->assertTrue( Unused_Images::is_ignored( $attachment_id_2 ), 'Second image should still be ignored' );
	}
}
