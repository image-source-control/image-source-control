<?php

namespace ISC\Tests\WPUnit\Includes\Image_Sources\Post_Meta;

use ISC\Image_Sources\Post_Meta\Image_Posts_Meta;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test ISC\Image_Sources\Post_Meta\Image_Posts_Meta
 */
class Image_Posts_Meta_Test extends WPTestCase {

	/**
	 * Test that get() returns the correct meta value for an image.
	 */
	public function test_get_returns_expected_meta_value() {
		$image_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		// check that the meta key is not set
		$this->assertEmpty( Image_Posts_Meta::get( $image_id ) );

		Image_Posts_Meta::update( $image_id, [ 1, 2 ] );

		$result = Image_Posts_Meta::get( $image_id );

		$this->assertEquals( [ 1, 2 ], $result );
	}

	/**
	 * Test that delete() removes the meta key from an image.
	 */
	public function test_delete_removes_meta() {
		$image_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		update_post_meta( $image_id, 'isc_image_posts', [ 1 ] );

		$this->assertNotEmpty( Image_Posts_Meta::get( $image_id ) );

		Image_Posts_Meta::delete( $image_id );

		$this->assertEmpty( Image_Posts_Meta::get( $image_id ) );
	}

	/**
	 * Test that delete_all() removes the meta key from all attachments.
	 */
	public function test_delete_all_removes_all_meta() {
		$image_id_1 = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$image_id_2 = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );

		Image_Posts_Meta::update( $image_id_1, [ 1 ] );
		Image_Posts_Meta::update( $image_id_2, [ 2 ] );

		$this->assertNotEmpty( Image_Posts_Meta::get( $image_id_1 ) );
		$this->assertNotEmpty( Image_Posts_Meta::get( $image_id_2 ) );

		$deleted = Image_Posts_Meta::delete_all();

		$this->assertTrue( $deleted );
		$this->assertEmpty( Image_Posts_Meta::get( $image_id_1 ) );
		$this->assertEmpty( Image_Posts_Meta::get( $image_id_2 ) );
	}

	/**
	 * Test that update_image_posts_meta_with_limit() applies the limit filter.
	 */
	public function test_update_image_posts_meta_with_limit_respects_filter() {
		$image_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		add_filter( 'isc_image_posts_meta_limit', fn() => 2 );

		$post_ids = [ 1, 2, 3, 4 ];
		Image_Posts_Meta::update_image_posts_meta_with_limit( $image_id, $post_ids );

		remove_all_filters( 'isc_image_posts_meta_limit' );

		$meta = Image_Posts_Meta::get( $image_id );
		$this->assertCount( 2, $meta );
		$this->assertEquals( [ 1, 2 ], $meta );
	}

	/**
	 * Test that add_image_post_association() adds a post ID to image meta.
	 */
	public function test_add_image_post_association_adds_post_id() {
		$image_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id  = self::factory()->post->create();

		Image_Posts_Meta::add_image_post_association( $image_id, $post_id );

		$this->assertContains( $post_id, Image_Posts_Meta::get( $image_id ) );
	}

	/**
	 * Test that add_image_post_association() avoids adding duplicates.
	 */
	public function test_add_image_post_association_ignores_duplicate() {
		$image_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id  = self::factory()->post->create();

		Image_Posts_Meta::update( $image_id, [ $post_id ] );
		Image_Posts_Meta::add_image_post_association( $image_id, $post_id );

		$meta = Image_Posts_Meta::get( $image_id );
		$this->assertEquals( [ $post_id ], $meta );
	}

	/**
	 * Test that remove_image_post_association() removes the post ID from image meta.
	 */
	public function test_remove_image_post_association_removes_post_id() {
		$image_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id  = self::factory()->post->create();

		Image_Posts_Meta::update( $image_id, [ $post_id ] );
		// Check that the post ID is present
		$this->assertContains( $post_id, Image_Posts_Meta::get( $image_id ) );

		Image_Posts_Meta::remove_image_post_association( $image_id, $post_id );

		$this->assertNotContains( $post_id, Image_Posts_Meta::get( $image_id ) );
	}

	/**
	 * Test that remove_image_post_association() is a no-op if the post ID isn't present.
	 */
	public function test_remove_image_post_association_does_nothing_for_non_existing() {
		$image_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id  = self::factory()->post->create();

		// Check that the meta key is not set
		$this->assertEmpty( Image_Posts_Meta::get( $image_id ) );

		Image_Posts_Meta::update( $image_id, [ 123 ] ); // different ID

		Image_Posts_Meta::remove_image_post_association( $image_id, $post_id );

		$this->assertEquals( [ 123 ], Image_Posts_Meta::get( $image_id ) );
	}
}
