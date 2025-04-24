<?php

namespace ISC\Tests\WPUnit\Includes;

use ISC\Image_Sources\Post_Meta\Image_Posts_Meta;
use ISC\Image_Sources\Post_Meta\Post_Images_Meta;
use \ISC\Tests\WPUnit\WPTestCase;
use ISC\indexer;
use ISC_Model; // Still needed for filter_image_ids

/**
 * Testing the ISC\Indexer::update_indexes method
 */
class Indexer_Update_Indexes_Test extends WPTestCase {

	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		global $post;

		// Clean up meta for the current global post if it exists
		if ( isset( $post->ID ) ) {
			delete_post_meta( $post->ID, Post_Images_Meta::META_KEY );
			delete_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY );
		}

		// Find and clean up meta for all attachments
		$attachments = get_posts( [
			                          'post_type'      => 'attachment',
			                          'posts_per_page' => -1,
			                          'post_status'    => 'any',
			                          'fields'         => 'ids',
		                          ] );

		foreach ( $attachments as $att_id ) {
			delete_post_meta( $att_id, Post_Images_Meta::META_KEY );
			wp_delete_attachment( $att_id, true );
		}

		// Find and clean up meta for all regular posts
		$posts = get_posts( [
			                    'post_type'      => 'post',
			                    'posts_per_page' => -1,
			                    'post_status'    => 'any',
			                    'fields'         => 'ids',
		                    ] );

		foreach ( $posts as $post_id ) {
			delete_post_meta( $post_id, Post_Images_Meta::META_KEY );
			delete_post_meta( $post_id, indexer::BEFORE_UPDATE_META_KEY );
		}

		parent::tearDown();
	}

	/**
	 * Helper to create an attachment and return its ID and URL.
	 * @param int $parent_post_id
	 * @param string $filename
	 * @return array ['id' => int, 'url' => string]
	 */
	private function create_test_attachment( int $parent_post_id = 0, string $filename = 'test-image1.jpg' ): array {
		$filepath = codecept_data_dir( $filename );
		$id       = self::factory()->attachment->create_upload_object( $filepath, $parent_post_id );
		$url      = wp_get_attachment_url( $id );
		return [ 'id' => $id, 'url' => $url ];
	}

	/**
	 * Test update_indexes does not proceed when can_index_the_page returns false.
	 */
	public function test_update_indexes_does_not_run_when_cannot_index_page() {
		global $post, $wp_current_filter;
		$post = self::factory()->post->create_and_get();

		// Backup the current filter stack
		$wp_current_filter_backup = $wp_current_filter ?? []; // Ensure it's an array

		// Simulate that 'get_the_excerpt' filter is being applied
		$wp_current_filter[] = 'get_the_excerpt';

		// Attempt to update indexes
		indexer::update_indexes( 'Some content' );

		// Restore the original filter stack
		$wp_current_filter = $wp_current_filter_backup;

		// Assert that no meta is updated and temp meta is not created/left
		$this->assertEmpty( get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true ), 'Meta should not be updated when cannot index page.' );
		$this->assertEmpty( get_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, true ), 'Temporary meta should not be present when cannot index page.' );
	}

	/**
	 * Test update_indexes skips and cleans up when content contains [isc_list_all] shortcode.
	 */
	public function test_update_indexes_skips_when_content_has_isc_list_all_shortcode() {
		global $post;
		$post = self::factory()->post->create_and_get();
		// Simulate temporary meta exists from a previous save
		update_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, [ 1 => 'data' ] );

		$content = 'Some content with [isc_list_all] shortcode.';

		indexer::update_indexes( $content );

		// Assert that the main meta is set to empty array and temp meta is cleaned up
		$this->assertSame( [], get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true ), 'Indexer should set meta to empty array for isc_list_all shortcode.' );
		$this->assertEmpty( get_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, true ), 'Temporary meta should be cleaned up for isc_list_all shortcode.' );
	}

	/**
	 * Test update_indexes skips and cleans up when attachments meta already exists (and not index bot).
	 */
	public function test_update_indexes_skips_when_attachments_already_set_and_not_bot() {
		global $post;
		$post = self::factory()->post->create_and_get();

		// Simulate main meta exists
		update_post_meta( $post->ID, Post_Images_Meta::META_KEY, [ 1 => 'data' ] );
		// Simulate temporary meta exists from a previous save
		update_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, [ 2 => 'old_data' ] );

		// Ensure is_index_bot returns false (default behavior usually)
		// add_filter( 'isc_is_index_bot', '__return_false', 99 ); // If needed

		indexer::update_indexes( 'Some content' );

		// remove_filter( 'isc_is_index_bot', '__return_false', 99 ); // If filter was added

		// Assert main meta is unchanged and temp meta is cleaned up
		$this->assertSame( [ 1 => 'data' ], get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true ), 'Main meta should remain unchanged when skipping.' );
		$this->assertEmpty( get_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, true ), 'Temporary meta should be cleaned up when skipping.' );
	}

	/**
	 * Test update_indexes processes content correctly on FIRST index run (no pre-update state).
	 */
	public function test_update_indexes_processes_content_on_first_run() {
		global $post;
		$post = self::factory()->post->create_and_get();

		// Create attachments
		$att1 = $this->create_test_attachment( $post->ID, 'test-image1.jpg' );
		$att2 = $this->create_test_attachment( $post->ID, 'test-image2.jpg' );

		// Simulate content with images
		$content = '<img src="' . $att1['url'] . '" class="wp-image-' . $att1['id'] . '" /><img src="' . $att2['url'] . '" class="wp-image-' . $att2['id'] . '" />';

		// Call the method under test
		indexer::update_indexes( $content );

		// Assert main meta is updated correctly
		$isc_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertIsArray( $isc_post_images );
		$this->assertArrayHasKey( $att1['id'], $isc_post_images, 'Post meta should contain attachment ID ' . $att1['id'] );
		$this->assertArrayHasKey( $att2['id'], $isc_post_images, 'Post meta should contain attachment ID ' . $att2['id'] );

		// Assert image posts meta is updated
		$isc_image_posts1 = get_post_meta( $att1['id'], Image_Posts_Meta::META_KEY, true );
		$isc_image_posts2 = get_post_meta( $att2['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts1 );
		$this->assertIsArray( $isc_image_posts2 );
		$this->assertContains( $post->ID, $isc_image_posts1, 'Attachment 1 should have post ID in its meta.' );
		$this->assertContains( $post->ID, $isc_image_posts2, 'Attachment 2 should have post ID in its meta.' );

		// Assert temp meta was not created or was cleaned up
		$this->assertEmpty( get_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, true ), 'Temporary meta should not be present after first run.' );
	}

	/**
	 * Test update_indexes removes association when an image is removed from content.
	 */
	public function test_update_indexes_removes_association_when_image_removed() {
		global $post;
		$post = self::factory()->post->create_and_get();

		// Create attachments
		$att1 = $this->create_test_attachment( $post->ID, 'test-image1.jpg' ); // Stays
		$att2 = $this->create_test_attachment( $post->ID, 'test-image2.jpg' ); // Removed

		// Simulate state AFTER save (prepare_for_reindex ran)
		$old_state = [
			$att1['id'] => [ 'src' => $att1['url'] ],
			$att2['id'] => [ 'src' => $att2['url'] ],
		];
		update_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, $old_state );
		delete_post_meta( $post->ID, Post_Images_Meta::META_KEY ); // Ensure main index is missing

		// Simulate image->post meta exists for both images, including some dummy IDs (999, 888)
		update_post_meta( $att1['id'], Image_Posts_Meta::META_KEY, [ $post->ID, 999 ] );
		update_post_meta( $att2['id'], Image_Posts_Meta::META_KEY, [ 888, $post->ID ] );

		// Simulate NEW rendered content (att2 removed)
		$content = '<img src="' . $att1['url'] . '" class="wp-image-' . $att1['id'] . '" />';

		// Call the method under test
		indexer::update_indexes( $content );

		// Assert main meta is updated correctly (only att1)
		$isc_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertIsArray( $isc_post_images );
		$this->assertArrayHasKey( $att1['id'], $isc_post_images );
		$this->assertArrayNotHasKey( $att2['id'], $isc_post_images );

		// Assert image posts meta is updated (att1 still has postID, att2 does NOT)
		$isc_image_posts1 = get_post_meta( $att1['id'], Image_Posts_Meta::META_KEY, true );
		$isc_image_posts2 = get_post_meta( $att2['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts1 );
		$this->assertIsArray( $isc_image_posts2 );
		$this->assertContains( $post->ID, $isc_image_posts1, 'Attachment 1 should still have post ID.' );
		$this->assertNotContains( $post->ID, $isc_image_posts2, 'Attachment 2 should NOT have post ID anymore.' );
		// Check other IDs were preserved
		$this->assertContains( 999, $isc_image_posts1 );
		$this->assertContains( 888, $isc_image_posts2 );

		// Assert temp meta was cleaned up
		$this->assertEmpty( get_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, true ), 'Temporary meta should be cleaned up.' );
	}

	/**
	 * Test update_indexes adds association when an image is added to content.
	 */
	public function test_update_indexes_adds_association_when_image_added() {
		global $post;
		$post = self::factory()->post->create_and_get();

		// Create attachments
		$att1 = $this->create_test_attachment( $post->ID, 'test-image1.jpg' ); // Was present
		$att2 = $this->create_test_attachment( $post->ID, 'test-image2.jpg' ); // Is added

		// Simulate state AFTER save (prepare_for_reindex ran) - only att1 was present
		$old_state = [
			$att1['id'] => [ 'src' => $att1['url'] ],
		];
		update_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, $old_state );
		delete_post_meta( $post->ID, Post_Images_Meta::META_KEY ); // Ensure main index is missing

		// Simulate image->post meta exists for att1, but not (yet) for att2 regarding this post
		update_post_meta( $att1['id'], Image_Posts_Meta::META_KEY, [ $post->ID ] );
		update_post_meta( $att2['id'], Image_Posts_Meta::META_KEY, [ 777 ] ); // Meta exists, but not for this post

		// Simulate NEW rendered content (both att1 and att2)
		$content = '<img src="' . $att1['url'] . '" class="wp-image-' . $att1['id'] . '" /><img src="' . $att2['url'] . '" class="wp-image-' . $att2['id'] . '" />';

		// Call the method under test
		indexer::update_indexes( $content );

		// Assert main meta is updated correctly (both att1 and att2)
		$isc_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertIsArray( $isc_post_images );
		$this->assertArrayHasKey( $att1['id'], $isc_post_images );
		$this->assertArrayHasKey( $att2['id'], $isc_post_images );

		// Assert image posts meta is updated (att1 still has postID, att2 now ALSO has postID)
		$isc_image_posts1 = get_post_meta( $att1['id'], Image_Posts_Meta::META_KEY, true );
		$isc_image_posts2 = get_post_meta( $att2['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts1 );
		$this->assertIsArray( $isc_image_posts2 );
		$this->assertContains( $post->ID, $isc_image_posts1, 'Attachment 1 should still have post ID.' );
		$this->assertContains( $post->ID, $isc_image_posts2, 'Attachment 2 should NOW have post ID.' );
		// Check other ID was preserved
		$this->assertContains( 777, $isc_image_posts2 );

		// Assert temp meta was cleaned up
		$this->assertEmpty( get_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, true ), 'Temporary meta should be cleaned up.' );
	}

	/**
	 * Test update_indexes handles case where content does not change between save and render.
	 */
	public function test_update_indexes_handles_no_change() {
		global $post;
		$post = self::factory()->post->create_and_get();

		// Create attachments
		$att1 = $this->create_test_attachment( $post->ID, 'test-image1.jpg' );

		// Simulate state AFTER save (prepare_for_reindex ran)
		$old_state = [
			$att1['id'] => [ 'src' => $att1['url'] ],
		];
		update_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, $old_state );
		delete_post_meta( $post->ID, Post_Images_Meta::META_KEY ); // Ensure main index is missing

		// Simulate image->post meta exists
		update_post_meta( $att1['id'], Image_Posts_Meta::META_KEY, [ $post->ID ] );

		// Simulate NEW rendered content (same as old state)
		$content = '<img src="' . $att1['url'] . '" class="wp-image-' . $att1['id'] . '" />';

		// Call the method under test
		indexer::update_indexes( $content );

		// Assert main meta is updated correctly
		$isc_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertIsArray( $isc_post_images );
		$this->assertArrayHasKey( $att1['id'], $isc_post_images );
		$this->assertCount( 1, $isc_post_images ); // Ensure no extras

		// Assert image posts meta is unchanged
		$isc_image_posts1 = get_post_meta( $att1['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts1 );
		$this->assertContains( $post->ID, $isc_image_posts1 );
		$this->assertCount( 1, $isc_image_posts1 ); // Ensure no extras

		// Assert temp meta was cleaned up
		$this->assertEmpty( get_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, true ), 'Temporary meta should be cleaned up.' );
	}

	/**
	 * Test update_indexes handles content becoming empty.
	 */
	public function test_update_indexes_handles_content_becoming_empty() {
		global $post;
		$post = self::factory()->post->create_and_get();

		// Create attachments
		$att1 = $this->create_test_attachment( $post->ID, 'test-image1.jpg' );

		// Simulate state AFTER save (prepare_for_reindex ran)
		$old_state = [
			$att1['id'] => [ 'src' => $att1['url'] ],
		];
		update_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, $old_state );
		delete_post_meta( $post->ID, Post_Images_Meta::META_KEY ); // Ensure main index is missing

		// Simulate image->post meta exists
		update_post_meta( $att1['id'], Image_Posts_Meta::META_KEY, [ $post->ID ] );

		// Simulate NEW rendered content (empty)
		$content = '';

		// Call the method under test
		indexer::update_indexes( $content );

		// Assert main meta is updated correctly (empty array)
		$isc_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertSame( [], $isc_post_images );

		// Assert image posts meta is updated (post ID removed)
		$isc_image_posts1 = get_post_meta( $att1['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts1 );
		$this->assertNotContains( $post->ID, $isc_image_posts1 );

		// Assert temp meta was cleaned up
		$this->assertEmpty( get_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, true ), 'Temporary meta should be cleaned up.' );
	}

	/**
	 * Test update_indexes handles content starting empty and getting images.
	 */
	public function test_update_indexes_handles_content_starting_empty() {
		global $post;
		$post = self::factory()->post->create_and_get();

		// Create attachments
		$att1 = $this->create_test_attachment( $post->ID, 'test-image1.jpg' );

		// Simulate state AFTER save (prepare_for_reindex ran) - was empty before
		$old_state = [];
		update_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, $old_state );
		delete_post_meta( $post->ID, Post_Images_Meta::META_KEY ); // Ensure main index is missing

		// Simulate image->post meta does NOT exist for this post yet
		update_post_meta( $att1['id'], Image_Posts_Meta::META_KEY, [ 999 ] );

		// Simulate NEW rendered content (has image)
		$content = '<img src="' . $att1['url'] . '" class="wp-image-' . $att1['id'] . '" />';

		// Call the method under test
		indexer::update_indexes( $content );

		// Assert main meta is updated correctly
		$isc_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertIsArray( $isc_post_images );
		$this->assertArrayHasKey( $att1['id'], $isc_post_images );

		// Assert image posts meta is updated (post ID added)
		$isc_image_posts1 = get_post_meta( $att1['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts1 );
		$this->assertContains( $post->ID, $isc_image_posts1 );
		$this->assertContains( 999, $isc_image_posts1 ); // Check other ID preserved

		// Assert temp meta was cleaned up
		$this->assertEmpty( get_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, true ), 'Temporary meta should be cleaned up.' );
	}

	/**
	 * Test update_indexes forces re-index when index bot is running, even if main meta exists.
	 */
	public function test_update_indexes_runs_when_bot_is_running_and_meta_exists() {
		global $post;
		$post = self::factory()->post->create_and_get();

		// Create attachments
		$att1 = $this->create_test_attachment( $post->ID, 'test-image1.jpg' );

		// Simulate main meta exists
		update_post_meta( $post->ID, Post_Images_Meta::META_KEY, [ 999 => 'old_data' ] );
		// Simulate temporary meta exists from a previous save
		update_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, [ 999 => 'old_data' ] );
		// Simulate image meta exists for old image
		update_post_meta( 999, Image_Posts_Meta::META_KEY, [ $post->ID ] );

		// Simulate NEW rendered content
		$content = '<img src="' . $att1['url'] . '" class="wp-image-' . $att1['id'] . '" />';

		// Force is_index_bot to return true
		add_filter( 'isc_is_index_bot', '__return_true', 99 );

		indexer::update_indexes( $content );

		remove_filter( 'isc_is_index_bot', '__return_true', 99 );

		// Assert main meta was updated (overwritten)
		$isc_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertIsArray( $isc_post_images );
		$this->assertArrayHasKey( $att1['id'], $isc_post_images );
		$this->assertArrayNotHasKey( 999, $isc_post_images );

		// Assert image posts meta was updated (999 removed, att1 added)
		$isc_image_posts_old = get_post_meta( 999, Image_Posts_Meta::META_KEY, true );
		$isc_image_posts_new = get_post_meta( $att1['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts_old );
		$this->assertIsArray( $isc_image_posts_new );
		$this->assertNotContains( $post->ID, $isc_image_posts_old, 'Old image should NOT have post ID anymore.' );
		$this->assertContains( $post->ID, $isc_image_posts_new, 'New image should have post ID.' );


		// Assert temp meta was cleaned up
		$this->assertEmpty( get_post_meta( $post->ID, indexer::BEFORE_UPDATE_META_KEY, true ), 'Temporary meta should be cleaned up.' );
	}

	/**
	 * Test that the 'isc_images_in_posts_simple' filter is applied within Indexer::update_indexes.
	 */
	public function test_update_indexes_applies_isc_images_in_posts_simple_filter() {
		global $post;
		$post = self::factory()->post->create_and_get();

		// Create an image that will be in the content
		$img_in_content = $this->create_test_attachment( $post->ID, 'test-image1.jpg' );
		// Create an image that will be added ONLY by the filter
		$img_added_by_filter = $this->create_test_attachment( $post->ID, 'test-image2.jpg' );

		// Define the content
		$content = 'Some content <img src="' . $img_in_content['url'] . '" class="wp-image-' . $img_in_content['id'] . '" />';

		// Add the filter to modify the image list
		add_filter( 'isc_images_in_posts_simple', function( $image_ids, $filter_post_id ) use ( $post, $img_added_by_filter ) {
			// Only modify for our specific test post
			if ( $filter_post_id === $post->ID ) {
				// Add the extra image ID
				$image_ids[ $img_added_by_filter['id'] ] = $img_added_by_filter['url'];
			}

			return $image_ids;
		},          10, 2 );

		// Call the method under test
		indexer::update_indexes( $content );

		// Remove the filter to avoid affecting other tests
		remove_all_filters( 'isc_images_in_posts_simple' );

		// 1. Verify the post's 'isc_post_images' meta contains the image added by the filter
		$isc_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertIsArray( $isc_post_images, 'isc_post_images should be an array.' );
		$this->assertArrayHasKey( $img_added_by_filter['id'], $isc_post_images, 'isc_post_images should contain the ID added by the filter.' );
		// Also check the original image is still there
		$this->assertArrayHasKey( $img_in_content['id'], $isc_post_images, 'isc_post_images should still contain the original content image ID.' );

		// 2. Verify the 'isc_image_posts' meta of the image ADDED by the filter now contains the post ID
		$isc_image_posts_added = get_post_meta( $img_added_by_filter['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts_added, 'isc_image_posts on the added image should be an array.' );
		$this->assertContains( $post->ID, $isc_image_posts_added, "The isc_image_posts meta for the image added by the filter should contain the post ID." );

		// 3. Verify the 'isc_image_posts' meta of the original content image still contains the post ID
		$isc_image_posts_original = get_post_meta( $img_in_content['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts_original, 'isc_image_posts on the original image should be an array.' );
		$this->assertContains( $post->ID, $isc_image_posts_original, "The isc_image_posts meta for the original content image should still contain the post ID." );
	}

	/**
	 * Test that isc_image_posts is updated when a post is deleted.
	 */
	public function test_image_post_associations_are_removed_when_post_is_deleted() {
		global $post;
		$post = self::factory()->post->create_and_get();

		// Create and attach an image
		$attachment_id = self::factory()->attachment->create_upload_object(
			codecept_data_dir( 'test-image1.jpg' ), $post->ID
		);

		// Simulate post content with the image
		$content = '<img src="' . wp_get_attachment_url( $attachment_id ) . '" />';
		indexer::update_indexes( $content );

		// Confirm the attachment is associated with the post
		$image_posts_meta = Image_Posts_Meta::get( $attachment_id );
		$this->assertContains( $post->ID, $image_posts_meta, 'Image should be associated with post before deletion.' );

		// Delete the post
		wp_delete_post( $post->ID, true ); // true = force delete (bypass trash), triggers the deleted_post action hook

		// Re-fetch the meta
		$image_posts_meta_after = Image_Posts_Meta::get( $attachment_id );

		// Assert the association is removed
		$this->assertIsArray( $image_posts_meta_after );
		$this->assertNotContains( $post->ID, $image_posts_meta_after, 'Image association should be removed after post deletion.' );
	}
}