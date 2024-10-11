<?php

namespace ISC\Tests\WPUnit;

/**
 * Testing the ISC\Indexer class
 */

use ISC\Indexer;
use ISC_Model;

class Indexer_Test extends \Codeception\TestCase\WPTestCase {

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

		$result = Indexer::can_index_the_page();
		$this->assertTrue( $result, 'Indexer should index under normal conditions.' );
	}

	/**
	 * Test can_index_the_page returns false when doing 'get_the_excerpt' filter.
	 */
	public function test_can_index_the_page_returns_false_when_doing_excerpt() {
		// Simulate the 'get_the_excerpt' filter is being applied
		add_filter( 'get_the_excerpt', function( $excerpt ) {
			$result = Indexer::can_index_the_page();
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

		$result = Indexer::can_index_the_page();
		$this->assertFalse( $result, 'Indexer should not index when $post is missing.' );

		$post = $backup_post; // Restore $post
	}

	/**
	 * Test can_save_image_information returns false for non-public post types.
	 */
	public function test_can_save_image_information_returns_false_for_non_public_post_type() {
		global $wp_post_types;

		// Backup the global post types
		$backup_wp_post_types = $wp_post_types;

		// Register the custom post type
		register_post_type( 'private_post_type', [ 'public' => false ] );

		$post_id = self::factory()->post->create( [ 'post_type' => 'private_post_type' ] );

		$result = Indexer::can_save_image_information( $post_id );
		$this->assertFalse( $result, 'Should not save image information for non-public post types.' );

		// Restore the original post types
		$wp_post_types = $backup_wp_post_types;
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

	/**
	 * Test get_attachments_for_index returns empty string when no meta is set.
	 */
	public function test_get_attachments_for_index_returns_empty_string_when_no_meta() {
		$post_id = self::factory()->post->create();

		delete_post_meta( $post_id, 'isc_post_images' );

		$attachments = Indexer::get_attachments_for_index( $post_id );
		$this->assertSame( '', $attachments, 'Should return empty string when no isc_post_images meta is set.' );
	}

	/**
	 * Test get_attachments_for_index returns array when meta exists.
	 */
	public function test_get_attachments_for_index_returns_array_when_meta_exists() {
		$post_id        = self::factory()->post->create();
		$attachment_ids = [ 1, 2, 3 ];

		update_post_meta( $post_id, 'isc_post_images', $attachment_ids );

		$attachments = Indexer::get_attachments_for_index( $post_id );
		$this->assertSame( $attachment_ids, $attachments, 'Should return attachment IDs when isc_post_images meta is set.' );
	}

	/**
	 * Test update_indexes does not proceed when can_index_the_page returns false.
	 */
	public function test_update_indexes_does_not_run_when_cannot_index_page() {
		global $post, $wp_current_filter;
		$post = self::factory()->post->create_and_get();

		// Backup the current filter stack
		$wp_current_filter_backup = $wp_current_filter;

		// Simulate that 'get_the_excerpt' filter is being applied
		$wp_current_filter[] = 'get_the_excerpt';

		// Attempt to update indexes
		Indexer::update_indexes( 'Some content' );

		// Restore the original filter stack
		$wp_current_filter = $wp_current_filter_backup;

		// Assert that no meta is updated
		$this->assertEmpty( get_post_meta( $post->ID, 'isc_post_images', true ), 'Meta should not be updated when cannot index page.' );
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
		Indexer::update_indexes( $content );

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
	 * Test update_indexes skips when content contains isc_list_all shortcode.
	 */
	public function test_update_indexes_skips_when_content_has_isc_list_all_shortcode() {
		global $post;
		$post = self::factory()->post->create_and_get();

		$content = 'Some content with [isc_list_all] shortcode.';

		Indexer::update_indexes( $content );

		$this->assertEmpty( get_post_meta( $post->ID, 'isc_post_images', true ), 'Indexer should skip content with isc_list_all shortcode.' );
	}

	/**
	 * Test update_indexes skips when attachments are already set.
	 */
	public function test_update_indexes_skips_when_attachments_already_set() {
		global $post;
		$post = self::factory()->post->create_and_get();

		update_post_meta( $post->ID, 'isc_post_images', [ 1, 2, 3 ] );

		Indexer::update_indexes( 'Some content' );

		// Since attachments are already set, no further action should be taken
		// You may add assertions or mocks to ensure methods are not called
	}
}