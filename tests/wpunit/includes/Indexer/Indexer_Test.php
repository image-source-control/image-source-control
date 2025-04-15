<?php

namespace ISC\Tests\WPUnit\Includes\Indexer;

use ISC\indexer;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing the ISC\Indexer class
 */
class Indexer_Test extends WPTestCase {

	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test can_index_the_page returns true under normal conditions.
	 */
	public function test_can_index_the_page_returns_true_under_normal_conditions() {
		global $post;
		$post = self::factory()->post->create_and_get();

		$result = indexer::can_index_the_page();
		$this->assertTrue( $result, 'Indexer should index under normal conditions.' );
	}

	/**
	 * Test can_index_the_page returns false when doing 'get_the_excerpt' filter.
	 */
	public function test_can_index_the_page_returns_false_when_doing_excerpt() {
		// Simulate the 'get_the_excerpt' filter is being applied
		add_filter( 'get_the_excerpt', function( $excerpt ) {
			$result = indexer::can_index_the_page();
			$this->assertFalse( $result, 'Indexer should not index during excerpt generation.' );

			return $excerpt;
		} );

		// Trigger the filter
		apply_filters( 'get_the_excerpt', '' );
	}

	/**
	 * Test can_index_the_page returns false when global $post is missing.
	 */
	public function test_can_index_the_page_returns_false_when_no_post_id() {
		global $post;
		$backup_post = $post;
		$post        = null;

		$result = indexer::can_index_the_page();
		$this->assertFalse( $result, 'Indexer should not index when $post is missing.' );

		$post = $backup_post; // Restore $post
	}

	/**
	 * Test get_attachments_for_index returns empty string when no meta is set.
	 */
	public function test_get_attachments_for_index_returns_empty_string_when_no_meta() {
		$post_id = self::factory()->post->create();

		delete_post_meta( $post_id, 'isc_post_images' );

		$attachments = indexer::get_attachments_for_index( $post_id );
		$this->assertSame( '', $attachments, 'Should return empty string when no isc_post_images meta is set.' );
	}

	/**
	 * Test get_attachments_for_index returns array when meta exists.
	 */
	public function test_get_attachments_for_index_returns_array_when_meta_exists() {
		$post_id        = self::factory()->post->create();
		$attachment_ids = [ 1, 2, 3 ];

		update_post_meta( $post_id, 'isc_post_images', $attachment_ids );

		$attachments = indexer::get_attachments_for_index( $post_id );
		$this->assertSame( $attachment_ids, $attachments, 'Should return attachment IDs when isc_post_images meta is set.' );
	}

	/**
	 * Test if reindexing in get_attachments_for_index can be prevented by setting
	 * the isc_add_sources_to_content_ignore_post_images_index filter to true
	 * while the meta does not exist.
	 */
	public function test_get_attachments_for_index_returns_empty_when_filter_active_and_no_meta() {
		$post_id = self::factory()->post->create();

		// Ensure no meta exists
		delete_post_meta( $post_id, 'isc_post_images' );

		// Activate the filter
		add_filter( 'isc_add_sources_to_content_ignore_post_images_index', '__return_true' );

		$attachments = indexer::get_attachments_for_index( $post_id );

		// Remove the filter immediately after use within the test
		remove_filter( 'isc_add_sources_to_content_ignore_post_images_index', '__return_true' );

		$this->assertSame( '', $attachments, 'Should return empty string when filter is active, even if meta does not exist.' );
	}

	/**
	 * Test if reindexing in get_attachments_for_index can be prevented by setting
	 * the isc_add_sources_to_content_ignore_post_images_index filter to true
	 * while the meta exists.
     */
	public function test_get_attachments_for_index_returns_empty_when_filter_active_and_meta_exists() {
		$post_id        = self::factory()->post->create();
		$attachment_ids = [ 1, 2, 3 ];

		// Set the meta
		update_post_meta( $post_id, 'isc_post_images', $attachment_ids );

		// Activate the filter
		add_filter( 'isc_add_sources_to_content_ignore_post_images_index', '__return_true' );

		$attachments = indexer::get_attachments_for_index( $post_id );

		// Remove the filter immediately after use within the test
		remove_filter( 'isc_add_sources_to_content_ignore_post_images_index', '__return_true' );

		$this->assertSame( '', $attachments, 'Should return empty string when filter is active, even if meta exists.' );

		// Optional: Verify the meta still exists to be sure it wasn't accidentally deleted
		$meta_after = get_post_meta( $post_id, 'isc_post_images', true );
		$this->assertSame( $attachment_ids, $meta_after, 'Meta should still exist after the call when filter was active.' );
	}

	/**
	 * Test update_indexes processes content correctly when conditions are met.
	 */
	public function test_update_indexes_processes_content_when_conditions_met() {
		global $post;
		$post = self::factory()->post->create_and_get();

		// Ensure no isc_post_images meta exists
		delete_post_meta( $post->ID, 'isc_post_images' );

		// Create attachments
		$attachment_id1 = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ), $post->ID );
		$attachment_id2 = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ), $post->ID );

		// Simulate content with images
		$content = '<img src="' . wp_get_attachment_url( $attachment_id1 ) . '" /><img src="' . wp_get_attachment_url( $attachment_id2 ) . '" />';

		// Call the method under test
		indexer::update_indexes( $content );

		// Assert that the meta is updated correctly
		$isc_post_images = get_post_meta( $post->ID, 'isc_post_images', true );

		$this->assertArrayHasKey( $attachment_id1, $isc_post_images, 'Post meta isc_post_images should contain attachment ID ' . $attachment_id1 );
		$this->assertArrayHasKey( $attachment_id2, $isc_post_images, 'Post meta isc_post_images should contain attachment IDs' . $attachment_id1 );

		// Also check if the image posts meta is updated
		$isc_image_posts1 = get_post_meta( $attachment_id1, 'isc_image_posts', true );
		$isc_image_posts2 = get_post_meta( $attachment_id2, 'isc_image_posts', true );

		$this->assertContains( $post->ID, $isc_image_posts1, 'Attachment 1 should have isc_image_posts meta containing the post ID.' );
		$this->assertContains( $post->ID, $isc_image_posts2, 'Attachment 2 should have isc_image_posts meta containing the post ID.' );

		wp_delete_attachment( $attachment_id1 );
		wp_delete_attachment( $attachment_id2 );
	}

	/**
	 * Test update_indexes skips when attachments are already set.
	 */
	public function test_update_indexes_skips_when_attachments_already_set() {
		global $post;
		$post = self::factory()->post->create_and_get();

		update_post_meta( $post->ID, 'isc_post_images', [ 1, 2, 3 ] );

		indexer::update_indexes( 'Some content' );

		// Since attachments are already set, no further action should be taken
		// You may add assertions or mocks to ensure methods are not called
	}
}