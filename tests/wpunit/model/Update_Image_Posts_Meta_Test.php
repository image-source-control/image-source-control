<?php

namespace ISC\Tests\WPUnit\Model;

use lucatume\WPBrowser\TestCase\WPTestCase;
use ISC_Model;

class Update_Image_Posts_Meta_Test extends WPTestCase {

	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test update_image_posts_meta adds post IDs to the isc_image_posts meta for each image.
	 */
	public function test_update_image_posts_meta_adds_post_ids() {
		// Create a new post
		$post_id = self::factory()->post->create();

		// Create two attachments (images)
		$attachment_id1 = self::factory()->attachment->create( [
			                                                       'post_parent' => $post_id,
			                                                       'post_mime_type' => 'image/jpeg',
			                                                       'post_type' => 'attachment',
			                                                       'guid' => 'http://example.com/test-image1.jpg'
		                                                       ] );
		$attachment_id2 = self::factory()->attachment->create( [
			                                                       'post_parent' => $post_id,
			                                                       'post_mime_type' => 'image/jpeg',
			                                                       'post_type' => 'attachment',
			                                                       'guid' => 'http://example.com/test-image2.jpg'
		                                                       ] );

		// Simulate the $image_ids array
		$image_ids = [
			$attachment_id1 => wp_get_attachment_url( $attachment_id1 ),
			$attachment_id2 => wp_get_attachment_url( $attachment_id2 ),
		];

		// Call the method under test
		ISC_Model::update_image_posts_meta( $post_id, $image_ids );

		// Verify that the meta 'isc_image_posts' is updated for each attachment
		foreach ( $image_ids as $attachment_id => $url ) {
			$isc_image_posts = get_post_meta( $attachment_id, 'isc_image_posts', true );
			$this->assertContains( $post_id, $isc_image_posts, "Attachment $attachment_id should have isc_image_posts meta containing the post ID." );
		}
	}

	/**
	 * Test update_image_posts_meta removes post IDs from isc_image_posts meta for removed images.
	 */
	public function test_update_image_posts_meta_removes_post_ids() {
		// Create a new post
		$post_id = self::factory()->post->create();

		// Create two attachments (images)
		$attachment_id1 = self::factory()->attachment->create( [
			                                                       'post_parent' => $post_id,
			                                                       'post_mime_type' => 'image/jpeg',
			                                                       'post_type' => 'attachment',
			                                                       'guid' => 'http://example.com/test-image1.jpg'
		                                                       ] );
		$attachment_id2 = self::factory()->attachment->create( [
			                                                       'post_parent' => $post_id,
			                                                       'post_mime_type' => 'image/jpeg',
			                                                       'post_type' => 'attachment',
			                                                       'guid' => 'http://example.com/test-image2.jpg'
		                                                       ] );

		// Simulate the $image_ids array and update the meta initially
		$image_ids = [
			$attachment_id1 => wp_get_attachment_url( $attachment_id1 ),
			$attachment_id2 => wp_get_attachment_url( $attachment_id2 ),
		];

		// the isc_post_images is later to check which images were removed, so we need to set it here
		update_post_meta( $post_id, 'isc_post_images', $image_ids );
		ISC_Model::update_image_posts_meta( $post_id, $image_ids );

		// Now remove one image and update the meta again
		unset( $image_ids[ $attachment_id1 ] );
		ISC_Model::update_image_posts_meta( $post_id, $image_ids );

		// Verify that the post ID is removed from isc_image_posts meta of the removed attachment
		$isc_image_posts = get_post_meta( $attachment_id1, 'isc_image_posts', true );
		$this->assertEmpty( $isc_image_posts, "Attachment $attachment_id1 should no longer have isc_image_posts meta containing the post ID." );
	}

	/**
	 * Test update_image_posts_meta applies the 'isc_images_in_posts_simple' filter.
	 */
	public function test_update_image_posts_meta_applies_filter() {
		// Create a new post
		$post_id = self::factory()->post->create();

		// Create an attachment (image)
		$attachment_id = self::factory()->attachment->create( [
			                                                      'post_parent' => $post_id,
			                                                      'post_mime_type' => 'image/jpeg',
			                                                      'post_type' => 'attachment',
			                                                      'guid' => 'http://example.com/test-image1.jpg'
		                                                      ] );

		// Simulate the $image_ids array
		$image_ids = [
			$attachment_id => wp_get_attachment_url( $attachment_id ),
		];

		// Add a filter to modify $image_ids
		add_filter( 'isc_images_in_posts_simple', function( $image_ids, $post_id ) {
			// Modify the $image_ids array
			$image_ids['custom_image'] = 'http://example.com/custom-image.jpg';

			return $image_ids;
		}, 10, 2 );

		// Call the method under test
		ISC_Model::update_image_posts_meta( $post_id, $image_ids );

		// Remove the filter to avoid affecting other tests
		remove_all_filters( 'isc_images_in_posts_simple' );

		// Verify that the post meta 'isc_image_posts' includes the custom image
		$isc_image_posts = get_post_meta( $attachment_id, 'isc_image_posts', true );
		$this->assertContains( $post_id, $isc_image_posts, "The isc_image_posts meta should contain the post ID for the modified image." );
	}

	/**
	 * Test update_image_posts_meta handles empty $image_ids correctly.
	 */
	public function test_update_image_posts_meta_handles_empty_image_ids() {
		// Create a new post
		$post_id = self::factory()->post->create();

		// Create an attachment (image)
		$attachment_id = self::factory()->attachment->create( [
			                                                      'post_parent' => $post_id,
			                                                      'post_mime_type' => 'image/jpeg',
			                                                      'post_type' => 'attachment',
			                                                      'guid' => 'http://example.com/test-image1.jpg'
		                                                      ] );

		// Initially add the image to the meta
		$image_ids = [
			$attachment_id => wp_get_attachment_url( $attachment_id ),
		];
		// the isc_post_images is later to check which images were removed, so we need to set it here
		update_post_meta( $post_id, 'isc_post_images', $image_ids );
		ISC_Model::update_image_posts_meta( $post_id, $image_ids );

		// Now update with an empty $image_ids array
		$image_ids = [];
		ISC_Model::update_image_posts_meta( $post_id, $image_ids );

		// Verify that the post ID is removed from isc_image_posts meta of the attachment
		$isc_image_posts = get_post_meta( $attachment_id, 'isc_image_posts', true );
		$this->assertNotContains( $post_id, $isc_image_posts, "Attachment $attachment_id should no longer have isc_image_posts meta containing the post ID after updating with empty image_ids." );
	}
}
