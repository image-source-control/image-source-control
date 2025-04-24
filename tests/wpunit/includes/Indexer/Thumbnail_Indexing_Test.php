<?php

namespace ISC\Tests\WPUnit\Includes;

use ISC\Image_Sources\Post_Meta\Image_Posts_Meta;
use ISC\Image_Sources\Post_Meta\Post_Images_Meta;
use \ISC\Tests\WPUnit\WPTestCase;
use ISC\indexer;

/**
 * Testing thumbnail handling within ISC\Indexer::update_indexes
 */
class Thumbnail_Indexing_Test extends WPTestCase {

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
			delete_post_meta( $att_id, Image_Posts_Meta::META_KEY );
			// Ensure thumbnail relationship is cleared if post is deleted first
			delete_post_meta( $att_id, '_thumbnail_id');
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
			// Ensure thumbnail relationship is cleared
			delete_post_meta( $post_id, '_thumbnail_id');
		}

		parent::tearDown();
	}

	/**
	 * Helper to create an attachment and return its ID and URL.
	 */
	private function create_test_attachment( int $parent_post_id = 0, string $filename = 'test-image1.jpg' ): array {
		$filepath = codecept_data_dir( $filename );
		$id       = self::factory()->attachment->create_upload_object( $filepath, $parent_post_id );
		$url      = wp_get_attachment_url( $id );
		return [ 'id' => $id, 'url' => $url ];
	}

	/**
	 * Test: First index run, thumbnail exists, not in content.
	 */
	public function test_first_index_with_thumbnail_only() {
		global $post;
		$post = self::factory()->post->create_and_get();
		$thumb = $this->create_test_attachment( $post->ID, 'test-image1.jpg' );
		set_post_thumbnail( $post->ID, $thumb['id'] );

		$content = 'Some content without the thumbnail image.';

		// Call the method under test
		indexer::update_indexes( $content );

		// Assert post's index contains only thumbnail
		$isc_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );

		$this->assertIsArray( $isc_post_images );
		$this->assertArrayHasKey( $thumb['id'], $isc_post_images, 'Post index should contain thumbnail ID.' );
		$this->assertCount( 1, $isc_post_images, 'Post index should only contain the thumbnail.' );
		$this->assertTrue( $isc_post_images[ $thumb['id'] ]['thumbnail'] ?? false, 'Thumbnail flag should be true.' );

		// Assert thumbnail's index contains post
		$isc_image_posts = get_post_meta( $thumb['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts );
		$this->assertContains( $post->ID, $isc_image_posts, 'Thumbnail index should contain post ID.' );
	}

	/**
	 * Test: First index run, thumbnail exists AND is in content.
	 */
	public function test_first_index_with_thumbnail_in_content() {
		global $post;
		$post = self::factory()->post->create_and_get();
		$thumb = $this->create_test_attachment( $post->ID, 'test-image1.jpg' );
		set_post_thumbnail( $post->ID, $thumb['id'] );

		$content = 'Some content <img src="' . $thumb['url'] . '" class="wp-image-' . $thumb['id'] . '" /> with the image.';

		// Call the method under test
		indexer::update_indexes( $content );

		// Assert post's index contains thumbnail (with flag)
		$isc_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertIsArray( $isc_post_images );
		$this->assertArrayHasKey( $thumb['id'], $isc_post_images, 'Post index should contain thumbnail ID.' );
		$this->assertCount( 1, $isc_post_images, 'Post index should only contain the single image.' );
		$this->assertTrue( $isc_post_images[ $thumb['id'] ]['thumbnail'] ?? false, 'Thumbnail flag should be true even if in content.' );

		// Assert thumbnail's index contains post
		$isc_image_posts = get_post_meta( $thumb['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts );
		$this->assertContains( $post->ID, $isc_image_posts, 'Thumbnail index should contain post ID.' );
	}

	/**
	 * Test: Update adds a thumbnail to a post previously indexed without one.
	 */
	public function test_update_adds_thumbnail() {
		global $post;
		$post = self::factory()->post->create_and_get();
		$img1 = $this->create_test_attachment( $post->ID, 'test-image1.jpg' );

		// --- Initial Index Run (No Thumbnail) ---
		$initial_content = '<img src="' . $img1['url'] . '" class="wp-image-' . $img1['id'] . '" />';
		indexer::update_indexes( $initial_content );
		// Sanity check initial state
		$initial_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertArrayHasKey( $img1['id'], $initial_post_images );
		$this->assertFalse( $initial_post_images[ $img1['id'] ]['thumbnail'] ?? false );
		// --- End Initial Run ---

		// --- Prepare for Update ---
		indexer::prepare_for_reindex( $post->ID ); // Move index to _before_update
		$thumb = $this->create_test_attachment( $post->ID, 'test-image2.jpg' ); // New thumbnail
		set_post_thumbnail( $post->ID, $thumb['id'] );
		// --- End Prepare ---

		// --- Second Index Run (Thumbnail Added) ---
		// Content remains the same, only thumbnail changed via WP functions
		$updated_content = $initial_content;
		indexer::update_indexes( $updated_content );
		// --- End Second Run ---

		// Assert post's index now contains both images, thumbnail has flag
		$isc_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertIsArray( $isc_post_images );
		$this->assertArrayHasKey( $img1['id'], $isc_post_images, 'Post index should still contain content image ID.' );
		$this->assertArrayHasKey( $thumb['id'], $isc_post_images, 'Post index should now contain thumbnail ID.' );
		$this->assertCount( 2, $isc_post_images );
		$this->assertFalse( isset( $isc_post_images[ $img1['id'] ]['thumbnail'] ), 'Content image flag should be false/absent.' );
		$this->assertTrue( $isc_post_images[ $thumb['id'] ]['thumbnail'] ?? false, 'Thumbnail flag should be true.' );

		// Assert thumbnail's index contains post
		$isc_image_posts_thumb = get_post_meta( $thumb['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts_thumb );
		$this->assertContains( $post->ID, $isc_image_posts_thumb, 'New thumbnail index should contain post ID.' );

		// Assert original image's index still contains post
		$isc_image_posts_img1 = get_post_meta( $img1['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts_img1 );
		$this->assertContains( $post->ID, $isc_image_posts_img1, 'Original image index should still contain post ID.' );
	}

	/**
	 * Test: Update removes a thumbnail (post previously indexed with it).
	 * Note: isc_image_posts for the removed thumbnail might only update if content changes trigger sync.
	 */
	public function test_update_removes_thumbnail() {
		global $post;
		$post = self::factory()->post->create_and_get();
		$thumb = $this->create_test_attachment( $post->ID, 'test-image1.jpg' ); // Initial thumbnail
		$img1 = $this->create_test_attachment( $post->ID, 'test-image2.jpg' ); // Content image

		// --- Initial Index Run (With Thumbnail) ---
		set_post_thumbnail( $post->ID, $thumb['id'] );
		$initial_content = '<img src="' . $img1['url'] . '" class="wp-image-' . $img1['id'] . '" />';
		indexer::update_indexes( $initial_content );
		// Sanity check initial state
		$initial_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertArrayHasKey( $thumb['id'], $initial_post_images );
		$this->assertArrayHasKey( $img1['id'], $initial_post_images );
		$this->assertTrue( $initial_post_images[ $thumb['id'] ]['thumbnail'] ?? false );
		// --- End Initial Run ---

		// --- Prepare for Update ---
		indexer::prepare_for_reindex( $post->ID );
		delete_post_thumbnail( $post->ID ); // Remove thumbnail
		// --- End Prepare ---

		// --- Second Index Run (Thumbnail Removed) ---
		// Content remains the same
		$updated_content = $initial_content;
		indexer::update_indexes( $updated_content );
		// --- End Second Run ---

		// Assert post's index no longer contains thumbnail, only content image
		$isc_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertIsArray( $isc_post_images );
		$this->assertArrayNotHasKey( $thumb['id'], $isc_post_images, 'Post index should NOT contain removed thumbnail ID.' );
		$this->assertArrayHasKey( $img1['id'], $isc_post_images, 'Post index should still contain content image ID.' );
		$this->assertCount( 1, $isc_post_images );

		// Assert content image's index still contains post
		$isc_image_posts_img1 = get_post_meta( $img1['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts_img1 );
		$this->assertContains( $post->ID, $isc_image_posts_img1, 'Content image index should still contain post ID.' );

		// Assert removed thumbnail's index no longer contains the post ID.
		$isc_image_posts_thumb = get_post_meta( $thumb['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts_thumb ); // Should still be an array (potentially empty)
		$this->assertNotContains( $post->ID, $isc_image_posts_thumb, 'Removed thumbnail index should NOT contain post ID anymore.' );
	}

	/**
	 * Test: Update changes thumbnail from A to B.
	 */
	public function test_update_changes_thumbnail() {
		global $post;
		$post = self::factory()->post->create_and_get();
		$thumb_a = $this->create_test_attachment( $post->ID, 'test-image1.jpg' ); // Initial thumbnail
		$img1 = $this->create_test_attachment( $post->ID, 'test-image2.jpg' ); // Content image

		// --- Initial Index Run (With Thumbnail A) ---
		set_post_thumbnail( $post->ID, $thumb_a['id'] );
		$initial_content = '<img src="' . $img1['url'] . '" class="wp-image-' . $img1['id'] . '" />';
		indexer::update_indexes( $initial_content );
		// --- End Initial Run ---

		// --- Prepare for Update ---
		indexer::prepare_for_reindex( $post->ID );
		$thumb_b = $this->create_test_attachment( $post->ID, 'test-image3.jpg' ); // New thumbnail
		set_post_thumbnail( $post->ID, $thumb_b['id'] ); // Change thumbnail
		// --- End Prepare ---

		// --- Second Index Run (Thumbnail Changed) ---
		// Content remains the same
		$updated_content = $initial_content;
		indexer::update_indexes( $updated_content );
		// --- End Second Run ---

		// Assert post's index contains Thumb B (with flag) and Img1, but not Thumb A
		$isc_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertIsArray( $isc_post_images );
		$this->assertArrayNotHasKey( $thumb_a['id'], $isc_post_images, 'Post index should NOT contain old thumbnail ID.' );
		$this->assertArrayHasKey( $thumb_b['id'], $isc_post_images, 'Post index should contain new thumbnail ID.' );
		$this->assertArrayHasKey( $img1['id'], $isc_post_images, 'Post index should still contain content image ID.' );
		$this->assertCount( 2, $isc_post_images );
		$this->assertTrue( $isc_post_images[ $thumb_b['id'] ]['thumbnail'] ?? false, 'New thumbnail flag should be true.' );

		// Assert new thumbnail's index contains post
		$isc_image_posts_thumb_b = get_post_meta( $thumb_b['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts_thumb_b );
		$this->assertContains( $post->ID, $isc_image_posts_thumb_b, 'New thumbnail index should contain post ID.' );

		// Assert content image's index still contains post
		$isc_image_posts_img1 = get_post_meta( $img1['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts_img1 );
		$this->assertContains( $post->ID, $isc_image_posts_img1, 'Content image index should still contain post ID.' );

		// Assert removed thumbnail's index no longer contains the post ID.
		$isc_image_posts_thumb = get_post_meta( $thumb_a['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts_thumb );
		$this->assertNotContains( $post->ID, $isc_image_posts_thumb, 'Removed thumbnail index should NOT contain post ID anymore.' );
	}

	/**
	 * Test: Update where an image already in content becomes the thumbnail.
	 */
	public function test_update_content_image_becomes_thumbnail() {
		global $post;
		$post = self::factory()->post->create_and_get();
		$img1 = $this->create_test_attachment( $post->ID, 'test-image1.jpg' ); // Initially in content

		// --- Initial Index Run (No Thumbnail) ---
		$initial_content = '<img src="' . $img1['url'] . '" class="wp-image-' . $img1['id'] . '" />';
		indexer::update_indexes( $initial_content );
		// Sanity check initial state
		$initial_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertArrayHasKey( $img1['id'], $initial_post_images );
		$this->assertFalse( $initial_post_images[ $img1['id'] ]['thumbnail'] ?? false );
		// --- End Initial Run ---

		// --- Prepare for Update ---
		indexer::prepare_for_reindex( $post->ID );
		set_post_thumbnail( $post->ID, $img1['id'] ); // Set content image as thumbnail
		// --- End Prepare ---

		// --- Second Index Run ---
		$updated_content = $initial_content;
		indexer::update_indexes( $updated_content );
		// --- End Second Run ---

		// Assert post's index contains the image, now with thumbnail flag
		$isc_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertIsArray( $isc_post_images );
		$this->assertArrayHasKey( $img1['id'], $isc_post_images, 'Post index should still contain image ID.' );
		$this->assertCount( 1, $isc_post_images );
		$this->assertTrue( $isc_post_images[ $img1['id'] ]['thumbnail'] ?? false, 'Thumbnail flag should now be true.' );

		// Assert image's index still contains post
		$isc_image_posts_img1 = get_post_meta( $img1['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts_img1 );
		$this->assertContains( $post->ID, $isc_image_posts_img1, 'Image index should still contain post ID.' );
	}

	/**
	 * Test: Update where thumbnail is removed but image remains in content.
	 */
	public function test_update_thumbnail_removed_but_still_in_content() {
		global $post;
		$post = self::factory()->post->create_and_get();
		$img1 = $this->create_test_attachment( $post->ID, 'test-image1.jpg' ); // Initially thumbnail and in content

		// --- Initial Index Run ---
		set_post_thumbnail( $post->ID, $img1['id'] );
		$initial_content = '<img src="' . $img1['url'] . '" class="wp-image-' . $img1['id'] . '" />';
		indexer::update_indexes( $initial_content );
		// Sanity check initial state
		$initial_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertArrayHasKey( $img1['id'], $initial_post_images );
		$this->assertTrue( $initial_post_images[ $img1['id'] ]['thumbnail'] ?? false );
		// --- End Initial Run ---

		// --- Prepare for Update ---
		indexer::prepare_for_reindex( $post->ID );
		delete_post_thumbnail( $post->ID ); // Remove thumbnail
		// --- End Prepare ---

		// --- Second Index Run ---
		$updated_content = $initial_content; // Content still has the image
		indexer::update_indexes( $updated_content );
		// --- End Second Run ---

		// Assert post's index contains the image, but without thumbnail flag
		$isc_post_images = get_post_meta( $post->ID, Post_Images_Meta::META_KEY, true );
		$this->assertIsArray( $isc_post_images );
		$this->assertArrayHasKey( $img1['id'], $isc_post_images, 'Post index should still contain image ID.' );
		$this->assertCount( 1, $isc_post_images );
		$this->assertFalse( isset( $isc_post_images[ $img1['id'] ]['thumbnail'] ), 'Thumbnail flag should now be false/absent.' ); // Check it's removed

		// Assert image's index still contains post
		$isc_image_posts_img1 = get_post_meta( $img1['id'], Image_Posts_Meta::META_KEY, true );
		$this->assertIsArray( $isc_image_posts_img1 );
		$this->assertContains( $post->ID, $isc_image_posts_img1, 'Image index should still contain post ID.' );
	}

}