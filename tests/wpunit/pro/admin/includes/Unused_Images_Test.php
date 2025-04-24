<?php

namespace ISC\Tests\WPUnit\Pro\Admin\Includes;

use ISC\Tests\WPUnit\WPTestCase;
use ISC\Pro\Unused_Images;

require_once dirname( __FILE__, 6 ) . '/pro/includes/unused-images/admin/unused-images.php';

class Unused_Images_Test extends WPTestCase {

	/**
	 * Image IDs
	 * @var array
	 */
	protected $image_ids = [];

	/**
	 * Post IDs
	 * @var array
	 */
	protected $post_ids = [];

	public function setUp(): void {
		parent::setUp();

		$this->post_ids['one'] = $this->factory->post->create( [
			'post_title'   => 'Post with Image One',
			'post_type'    => 'post',
			'guid'         => 'https://example.com/post-with-image-one',
			'post_content' => 'There is some text around the image <img src="https://example.com/image-one.jpg"/> which is hopefully not removed.',
		] );

		// image 1 was uploaded to post 1, hence the post_parent is set
		$this->image_ids['one'] = $this->factory->post->create( [
			'post_title' => 'Image One',
			'post_type'  => 'attachment',
			'guid'       => 'https://example.com/image-one.jpg',
			'post_parent' => $this->post_ids['one'],
		] );

		// image 2 is used as a featured image
		$image_two_id = $this->factory->post->create( [
			'post_title' => 'Image Two',
			'post_type'  => 'attachment',
			'guid'       => 'https://example.com/image-two.png',
		] );

		$post_two_id = $this->factory->post->create( [
			'post_title'   => 'Post with Image Two',
			'post_type'    => 'post',
			'guid'         => 'https://example.com/post-with-image-two',
			'post_content' => 'Some arbitrary content.',
		] );

		update_post_meta( $post_two_id, '_thumbnail_id', $image_two_id );

		// image 3 is used in an option
		$image_three_id = $this->factory->post->create( [
			'post_title' => 'Image Three',
			'post_type'  => 'attachment',
			'guid'       => 'https://example.com/image-three.png',
		] );

		update_option( 'some_temporary_option', [ 'image_url' => 'https://example.com/image-three.png' ] );

		// image 4 is used in a post meta
		$image_four_id = $this->factory->post->create( [
			'post_title' => 'Image Four',
			'post_type'  => 'attachment',
			'guid'       => 'https://example.com/image-four.png',
		] );

		$post_four_id = $this->factory->post->create( [
			'post_title'   => 'Post with Image Four',
			'post_type'    => 'post',
			'guid'         => 'https://example.com/post-with-image-four',
			'post_content' => 'Some arbitrary content.',
		] );

		update_post_meta( $post_four_id, 'some_temporary_meta', 'https://example.com/image-four.png' );

		// the post is a revision
		$post_five_id = $this->factory->post->create( [
			                                              'post_title'   => 'Post with Image Five',
			                                              'post_type'    => 'revision',
			                                              'guid'         => 'https://example.com/post-with-image-five',
			                                              'post_content' => 'There is some text around the image <img src="https://example.com/image-five.jpg"/> which is hopefully not removed.',
		                                              ] );

		update_post_meta( $post_five_id, 'some_temporary_meta', 'https://example.com/image-five.png' );

		// image 5 is not used anywhere
		$this->factory->post->create( [
			'post_title' => 'Image Five',
			'post_type'  => 'attachment',
			'guid'       => 'https://example.com/image-five.png',
		] );
	}

	/**
	 * Clean up after each test.
	 */
	protected function tearDown(): void {
		// Clean up options modified in setUp
		delete_option( 'some_temporary_option' );

		// Call parent tearDown to handle database transaction rollback etc.
		parent::tearDown();
	}

	/**
	 * Test the search_filepath_in_post_content() function to see if it returns the only image within post content.
	 */
	public function test_file_path_in_content() {
		$unused_images = new Unused_Images();
		$result        = $unused_images->search_filepath_in_post_content( 'image-one' );

		// returns one result
		$this->assertCount( 1, $result );

		// check the actual returned post
		$actual_object = $result[0];
		$this->assertEquals( 'Post with Image One', $actual_object->post_title );
		$this->assertEquals( 'post', $actual_object->post_type );
	}

	/**
	 * Test the search_filepath_in_postmeta() function to see if it returns the only image within post meta.
	 */
	public function test_file_path_in_postmeta() {
		$unused_images = new Unused_Images();
		$result        = $unused_images->search_filepath_in_postmeta( 'image-four', 4 );

		// returns one result
		$this->assertCount( 1, $result );

		// check the actual returned post
		$actual_object = $result[0];
		$this->assertEquals( 'some_temporary_meta', $actual_object->meta_key );
	}

	/**
	 * Test the search_filepath_in_options() function to see if it returns the only image within options.
	 */
	public function test_file_path_in_options() {
		$unused_images = new Unused_Images();
		$result        = $unused_images->search_filepath_in_options( 'image-three' );

		// returns one result
		$this->assertCount( 1, $result );

		// check the actual returned post
		$actual_object = $result[0];
		$this->assertEquals( 'some_temporary_option', $actual_object->option_name );
	}

	/**
	 * Test the get_uploaded_to_post() function to see if it returns the correct post Object.
	 */
	public function test_uploaded_to_post() {
		$result = Unused_Images::get_uploaded_to_post( $this->image_ids['one'] );

		// check the actual returned post
		$this->assertEquals( 'Post with Image One', $result->post_title );
	}

	/**
	 * Test if search_filepath_in_post_content() ignores post revisions
	 */
	public function test_ignore_post_revision() {
		$unused_images = new Unused_Images();
		$result        = $unused_images->search_filepath_in_post_content( 'image-five' );

		// returns no result
		$this->assertCount( 0, $result );
	}

	/**
	 * Test the search_filepath_in_postmeta() ignores post meta belonging to a "revision" post type.
	 */
	public function test_ignore_post_meta_in_revision() {
		$unused_images = new Unused_Images();
		$result        = $unused_images->search_filepath_in_postmeta( 'image-five', 5 );

		// returns one result
		$this->assertCount( 0, $result );
	}
}