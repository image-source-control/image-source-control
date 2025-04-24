<?php

namespace ISC\Tests\WPUnit\Includes\Indexer;

use ISC\Indexer;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing ISC\Indexer:can_save_image_information
 */
class Can_Save_Image_Information_Test extends WPTestCase {

	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		// Unregister the custom post type registered in the test method
		unregister_post_type( 'private_post_type' );

		parent::tearDown();
	}

	/**
	 * Test can_save_image_information returns false for non-public post types.
	 */
	public function test_can_save_image_information_returns_false_for_non_public_post_type() {
		// Register the custom post type - this needs cleanup in tearDown
		register_post_type( 'private_post_type', [ 'public' => false ] );

		$post_id = self::factory()->post->create( [ 'post_type' => 'private_post_type' ] );

		$result = Indexer::can_save_image_information( $post_id );
		$this->assertFalse( $result, 'Should not save image information for non-public post types.' );

		// Removed the manual backup and restore of $wp_post_types
	}

	/**
	 * Test can_save_image_information returns false for revisions.
	 */
	public function test_can_save_image_information_returns_false_for_revision() {
		$post_id     = self::factory()->post->create();
		$revision_id = wp_save_post_revision( $post_id );

		$result = Indexer::can_save_image_information( $revision_id );
		$this->assertFalse( $result, 'Should not save image information for revisions.' );
	}

	/**
	 * Test can_save_image_information returns true for public posts.
	 */
	public function test_can_save_image_information_returns_true_for_public_post() {
		$post_id = self::factory()->post->create();

		$result = Indexer::can_save_image_information( $post_id );
		$this->assertTrue( $result, 'Should save image information for public posts.' );
	}
}