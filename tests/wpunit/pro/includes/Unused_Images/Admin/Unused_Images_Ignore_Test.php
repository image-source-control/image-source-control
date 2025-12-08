<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Admin;

use ISC\Pro\Unused_Images\Admin\Unused_Images;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing \ISC\Pro\Unused_Images ignore/unignore functionality
 */
class Unused_Images_Ignore_Test extends WPTestCase {

	/**
	 * Test attachment ID
	 *
	 * @var int
	 */
	protected $attachment_id;

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a test attachment
		$this->attachment_id = $this->factory->attachment->create( [
			'post_title'     => 'Test Image for Ignore',
			'post_mime_type' => 'image/jpeg',
		] );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		// Call parent tearDown first to handle database transaction rollback
		parent::tearDown();

		// Clean up transient cache
		delete_transient( 'isc_has_ignored_images' );
	}

	/**
	 * Test set_ignored_status() sets meta to '1' when true
	 *
	 * Tests: \ISC\Pro\Unused_Images::set_ignored_status()
	 */
	public function test_set_ignored_status_sets_meta_to_one_when_true() {
		Unused_Images::set_ignored_status( $this->attachment_id, true );

		$meta_value = get_post_meta( $this->attachment_id, 'isc_ignored_unused_image', true );
		$this->assertEquals( '1', $meta_value, 'Meta value should be "1" when ignored' );
	}

	/**
	 * Test set_ignored_status() deletes meta when false
	 *
	 * Tests: \ISC\Pro\Unused_Images::set_ignored_status()
	 */
	public function test_set_ignored_status_deletes_meta_when_false() {
		// First set it to ignored
		update_post_meta( $this->attachment_id, 'isc_ignored_unused_image', '1' );

		// Then unignore it
		Unused_Images::set_ignored_status( $this->attachment_id, false );

		$meta_value = get_post_meta( $this->attachment_id, 'isc_ignored_unused_image', true );
		$this->assertEmpty( $meta_value, 'Meta should be deleted when unignored' );
	}

	/**
	 * Test set_ignored_status() clears transient cache
	 *
	 * Tests: \ISC\Pro\Unused_Images::set_ignored_status()
	 */
	public function test_set_ignored_status_clears_transient_cache() {
		// Set a transient value
		set_transient( 'isc_has_ignored_images', true, HOUR_IN_SECONDS );
		$this->assertNotFalse( get_transient( 'isc_has_ignored_images' ), 'Transient should exist before test' );

		// Change ignored status
		Unused_Images::set_ignored_status( $this->attachment_id, true );

		$transient = get_transient( 'isc_has_ignored_images' );
		$this->assertFalse( $transient, 'Transient should be deleted after changing ignored status' );
	}

	/**
	 * Test is_ignored() returns false for non-ignored image
	 *
	 * Tests: \ISC\Pro\Unused_Images::is_ignored()
	 */
	public function test_is_ignored_returns_false_for_non_ignored_image() {
		$result = Unused_Images::is_ignored( $this->attachment_id );
		$this->assertFalse( $result, 'is_ignored() should return false when meta does not exist' );
	}

	/**
	 * Test is_ignored() returns true for ignored image
	 *
	 * Tests: \ISC\Pro\Unused_Images::is_ignored()
	 */
	public function test_is_ignored_returns_true_for_ignored_image() {
		update_post_meta( $this->attachment_id, 'isc_ignored_unused_image', '1' );

		$result = Unused_Images::is_ignored( $this->attachment_id );
		$this->assertTrue( $result, 'is_ignored() should return true when meta is "1"' );
	}

	/**
	 * Test is_ignored() returns false for invalid meta value
	 *
	 * Tests: \ISC\Pro\Unused_Images::is_ignored()
	 */
	public function test_is_ignored_returns_false_for_invalid_meta_value() {
		update_post_meta( $this->attachment_id, 'isc_ignored_unused_image', 'invalid_value' );

		$result = Unused_Images::is_ignored( $this->attachment_id );
		$this->assertFalse( $result, 'is_ignored() should return false for invalid meta values' );
	}

	/**
	 * Test has_ignored_images() returns false when none exist
	 *
	 * Tests: \ISC\Pro\Unused_Images::has_ignored_images()
	 */
	public function test_has_ignored_images_returns_false_when_none_exist() {
		$result = Unused_Images::has_ignored_images();
		$this->assertFalse( $result, 'has_ignored_images() should return false when no ignored images exist' );
	}

	/**
	 * Test has_ignored_images() returns true when images exist
	 *
	 * Tests: \ISC\Pro\Unused_Images::has_ignored_images()
	 */
	public function test_has_ignored_images_returns_true_when_images_exist() {
		update_post_meta( $this->attachment_id, 'isc_ignored_unused_image', '1' );

		$result = Unused_Images::has_ignored_images();
		$this->assertTrue( $result, 'has_ignored_images() should return true when ignored images exist' );
	}

	/**
	 * Test has_ignored_images() sets cache after query
	 *
	 * Tests: \ISC\Pro\Unused_Images::has_ignored_images()
	 */
	public function test_has_ignored_images_sets_cache_after_query() {
		// Ensure no transient exists
		delete_transient( 'isc_has_ignored_images' );

		// Add an ignored image
		update_post_meta( $this->attachment_id, 'isc_ignored_unused_image', '1' );

		// Call the method which should query and cache
		$result = Unused_Images::has_ignored_images();
		$this->assertTrue( $result, 'has_ignored_images() should return true' );

		// Check that transient was set
		$cached_value = get_transient( 'isc_has_ignored_images' );
		$this->assertNotFalse( $cached_value, 'Transient should be set after query' );
		$this->assertTrue( $cached_value, 'Cached value should be true' );
	}

	/**
	 * Test toggling ignored status multiple times
	 *
	 * Tests: \ISC\Pro\Unused_Images::set_ignored_status()
	 * Tests: \ISC\Pro\Unused_Images::is_ignored()
	 */
	public function test_toggle_ignored_status_multiple_times() {
		// Initially not ignored
		$this->assertFalse( Unused_Images::is_ignored( $this->attachment_id ), 'Should not be ignored initially' );

		// Ignore
		Unused_Images::set_ignored_status( $this->attachment_id, true );
		$this->assertTrue( Unused_Images::is_ignored( $this->attachment_id ), 'Should be ignored after first toggle' );

		// Unignore
		Unused_Images::set_ignored_status( $this->attachment_id, false );
		$this->assertFalse( Unused_Images::is_ignored( $this->attachment_id ), 'Should not be ignored after second toggle' );

		// Ignore again
		Unused_Images::set_ignored_status( $this->attachment_id, true );
		$this->assertTrue( Unused_Images::is_ignored( $this->attachment_id ), 'Should be ignored after third toggle' );
	}

	/**
	 * Test multiple ignored images
	 *
	 * Tests: \ISC\Pro\Unused_Images::set_ignored_status()
	 * Tests: \ISC\Pro\Unused_Images::is_ignored()
	 * Tests: \ISC\Pro\Unused_Images::has_ignored_images()
	 */
	public function test_multiple_ignored_images() {
		$attachment_id_2 = $this->factory->attachment->create( [
			'post_title'     => 'Test Image 2',
			'post_mime_type' => 'image/jpeg',
		] );
		$attachment_id_3 = $this->factory->attachment->create( [
			'post_title'     => 'Test Image 3',
			'post_mime_type' => 'image/jpeg',
		] );

		// Ignore multiple images
		Unused_Images::set_ignored_status( $this->attachment_id, true );
		Unused_Images::set_ignored_status( $attachment_id_2, true );
		Unused_Images::set_ignored_status( $attachment_id_3, true );

		// Check all are ignored
		$this->assertTrue( Unused_Images::is_ignored( $this->attachment_id ), 'First image should be ignored' );
		$this->assertTrue( Unused_Images::is_ignored( $attachment_id_2 ), 'Second image should be ignored' );
		$this->assertTrue( Unused_Images::is_ignored( $attachment_id_3 ), 'Third image should be ignored' );

		// Check has_ignored_images
		delete_transient( 'isc_has_ignored_images' ); // Clear cache to force fresh query
		$this->assertTrue( Unused_Images::has_ignored_images(), 'has_ignored_images() should return true with multiple ignored images' );

		// Unignore one
		Unused_Images::set_ignored_status( $attachment_id_2, false );
		$this->assertFalse( Unused_Images::is_ignored( $attachment_id_2 ), 'Second image should not be ignored after unignore' );
		$this->assertTrue( Unused_Images::is_ignored( $this->attachment_id ), 'First image should still be ignored' );
		$this->assertTrue( Unused_Images::is_ignored( $attachment_id_3 ), 'Third image should still be ignored' );
	}

	/**
	 * Test ignored status persists after usage changes
	 *
	 * Tests: \ISC\Pro\Unused_Images::set_ignored_status()
	 * Tests: \ISC\Pro\Unused_Images::is_ignored()
	 */
	public function test_ignored_status_persists_after_usage_changes() {
		// Ignore the image
		Unused_Images::set_ignored_status( $this->attachment_id, true );
		$this->assertTrue( Unused_Images::is_ignored( $this->attachment_id ), 'Image should be ignored' );

		// Simulate the image being used somewhere (by adding isc_image_posts meta)
		update_post_meta( $this->attachment_id, 'isc_image_posts', [ 123 ] );

		// Check that ignored flag remains
		$this->assertTrue( Unused_Images::is_ignored( $this->attachment_id ), 'Ignored flag should persist after usage changes' );

		// Simulate the image no longer being used
		delete_post_meta( $this->attachment_id, 'isc_image_posts' );

		// Check that ignored flag still remains
		$this->assertTrue( Unused_Images::is_ignored( $this->attachment_id ), 'Ignored flag should still persist after usage removal' );
	}
}
