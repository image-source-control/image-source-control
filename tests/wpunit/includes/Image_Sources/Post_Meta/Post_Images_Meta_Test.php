<?php

namespace ISC\Tests\WPUnit\Includes\Image_Sources\Post_Meta;

use ISC\Image_Sources\Post_Meta\Post_Images_Meta;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test ISC\Image_Sources\Post_Meta\Post_Images_Meta
 */
class Post_Images_Meta_Test extends WPTestCase {

	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test the update() method sets the post meta value.
	 */
	public function test_update_sets_post_meta_value() {
		$post_id = self::factory()->post->create();

		// Check that the meta key is not set
		$this->assertEmpty( Post_Images_Meta::get( $post_id ) );

		$data = [ 123, 234 ];
		Post_Images_Meta::update( $post_id, $data );

		$this->assertEquals( $data, Post_Images_Meta::get( $post_id ) );
	}


	/**
	 * Test update_post_images_meta adds the thumbnail to the image_ids array
	 * and updates the post meta 'isc_post_images'.
	 */
	public function test_update_post_images_meta_adds_thumbnail() {
		// Create a new post
		$post_id = self::factory()->post->create();

		// Create two attachments (images)
		$attachment_id1 = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ), $post_id );
		$attachment_id2 = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ), $post_id );

		// Set one of the attachments as the post thumbnail
		set_post_thumbnail( $post_id, $attachment_id1 );

		// Simulate the $image_ids array as it would be before calling update_post_images_meta
		$image_ids = [
			$attachment_id2 => [
				'src'       => wp_get_attachment_url( $attachment_id2 ),
				'thumbnail' => false,
			],
		];

		// Call the method under test
		Post_Images_Meta::update_images_in_posts( $post_id, $image_ids );

		// Verify that the post meta 'isc_post_images' is updated correctly
		$isc_post_images = Post_Images_Meta::get( $post_id );

		// Expected $image_ids array after method execution
		$expected_image_ids                    = $image_ids;
		$expected_image_ids[ $attachment_id1 ] = [
			'src'       => wp_get_attachment_url( $attachment_id1 ),
			'thumbnail' => true,
		];

		// Verify the meta matches the expected array
		$this->assertEquals( $expected_image_ids, $isc_post_images );

		wp_delete_attachment( $attachment_id1 );
		wp_delete_attachment( $attachment_id2 );
	}

	/**
	 * Test update_post_images_meta applies the 'isc_images_in_posts' filter.
	 */
	public function test_update_post_images_meta_applies_filter() {
		// Create a new post
		$post_id = self::factory()->post->create();

		// Create an attachment (image)
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ), $post_id );

		// Simulate the $image_ids array
		$image_ids = [
			$attachment_id => [
				'src'       => wp_get_attachment_url( $attachment_id ),
				'thumbnail' => false,
			],
		];

		// Add a filter to modify $image_ids
		add_filter( 'isc_images_in_posts', function( $image_ids, $post_id ) {
			// Modify the $image_ids array
			$image_ids['custom_image'] = [
				'src'       => 'http://example.com/custom-image.jpg',
				'thumbnail' => false,
			];

			return $image_ids;
		}, 10, 2 );

		// Call the method under test
		Post_Images_Meta::update_images_in_posts( $post_id, $image_ids );

		// Remove the filter to avoid affecting other tests
		remove_all_filters( 'isc_images_in_posts' );

		// Verify that the post meta 'isc_post_images' includes the custom image
		$isc_post_images = Post_Images_Meta::get( $post_id );

		// Expected $image_ids array after method execution
		$expected_image_ids                 = $image_ids;
		$expected_image_ids['custom_image'] = [
			'src'       => 'http://example.com/custom-image.jpg',
			'thumbnail' => false,
		];

		// Verify the meta matches the expected array
		$this->assertEquals( $expected_image_ids, $isc_post_images );

		wp_delete_attachment( $attachment_id );
	}

	/**
	 * Test update_post_images_meta handles empty $image_ids correctly.
	 */
	public function test_update_post_images_meta_handles_empty_image_ids() {
		// Create a new post
		$post_id = self::factory()->post->create();

		// Set a post thumbnail
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ), $post_id );
		set_post_thumbnail( $post_id, $attachment_id );

		// Empty $image_ids array
		$image_ids = [];

		// Call the method under test
		Post_Images_Meta::update_images_in_posts( $post_id, $image_ids );

		// Verify that the post meta 'isc_post_images' includes the thumbnail
		$isc_post_images = Post_Images_Meta::get( $post_id );

		// Expected $image_ids array after method execution
		$expected_image_ids = [
			$attachment_id => [
				'src'       => wp_get_attachment_url( $attachment_id ),
				'thumbnail' => true,
			],
		];

		// Verify the meta matches the expected array
		$this->assertEquals( $expected_image_ids, $isc_post_images );

		wp_delete_attachment( $attachment_id );
	}

	/**
	 * Test update_post_images_meta triggers the 'isc_update_post_meta' action.
	 */
	public function test_update_post_images_meta_triggers_action() {
		// Create a new post
		$post_id = self::factory()->post->create();

		// Create an attachment (image)
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ), $post_id );

		// Simulate the $image_ids array
		$image_ids = [
			$attachment_id => [
				'src'       => wp_get_attachment_url( $attachment_id ),
				'thumbnail' => false,
			],
		];

		// Set up a flag to check if the action is triggered
		$action_triggered = false;

		// Add an action to set the flag
		add_action( 'isc_update_post_meta', function( $updated_post_id, $key, $value ) use ( &$action_triggered, $post_id ) {
			if ( $updated_post_id === $post_id && $key === 'isc_post_images' ) {
				$action_triggered = true;
			}
		}, 10, 3 );

		// Call the method under test
		Post_Images_Meta::update_images_in_posts( $post_id, $image_ids );

		// Remove the action to avoid affecting other tests
		remove_all_actions( 'isc_update_post_meta' );

		// Verify that the action was triggered
		$this->assertTrue( $action_triggered, 'The action isc_update_post_meta should have been triggered.' );

		wp_delete_attachment( $attachment_id );
	}

	/**
	 * Test update_post_images_meta overrides existing thumbnail entry.
	 */
	public function test_update_post_images_meta_overrides_existing_thumbnail_entry() {
		$post_id = self::factory()->post->create();

		// Create an attachment and set it as both regular image and thumbnail
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ), $post_id );
		set_post_thumbnail( $post_id, $attachment_id );

		// Predefine the image with 'thumbnail' => false
		$image_ids = [
			$attachment_id => [
				'src'       => wp_get_attachment_url( $attachment_id ),
				'thumbnail' => false,
			],
		];

		// Call the method under test
		Post_Images_Meta::update_images_in_posts( $post_id, $image_ids );

		// Fetch the saved meta
		$isc_post_images = Post_Images_Meta::get( $post_id );

		// Check that thumbnail flag was overridden
		$this->assertTrue( $isc_post_images[ $attachment_id ]['thumbnail'] );

		wp_delete_attachment( $attachment_id );
	}

	/**
	 * Test the delete() method
	 *
	 * @return void
	 */
	public function test_delete_removes_post_images_meta() {
		$post_id = self::factory()->post->create();
		Post_Images_Meta::update( $post_id, [ 'some' => 'value' ] );

		// Check if the meta value exists before deletion
		$this->assertNotEmpty( get_post_meta( $post_id, 'isc_post_images', true ) );

		Post_Images_Meta::delete( $post_id );

		$this->assertEmpty( Post_Images_Meta::get( $post_id ) );
	}

	/**
	 * Test the delete_all() method
	 *
	 * @return void
	 */
	public function test_delete_all_removes_all_isc_post_images_entries() {
		$post_id_1 = self::factory()->post->create();
		$post_id_2 = self::factory()->post->create();

		Post_Images_Meta::update( $post_id_1, [ 'id' => 123 ] );
		Post_Images_Meta::update( $post_id_2, [ 'id' => 234 ] );

		// Check if the meta values exist before deletion
		$this->assertNotEmpty( Post_Images_Meta::get( $post_id_1 ) );
		$this->assertNotEmpty( Post_Images_Meta::get( $post_id_2 ) );

		$deleted = Post_Images_Meta::delete_all();

		$this->assertTrue( $deleted );
		$this->assertEmpty( Post_Images_Meta::get( $post_id_1 ), 'Post 1 meta should be empty' );
		$this->assertEmpty( Post_Images_Meta::get( $post_id_2 ), 'Post 2 meta should be empty' );
	}
}
