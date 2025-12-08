<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Admin;

use ISC\Pro\Unused_Images\Admin\Unused_Images;
use ISC\Tests\WPUnit\WPTestCase;

class Unused_Images_Test extends WPTestCase {

	/**
	 * Image IDs
	 * @var array
	 */
	protected $image_ids = [];

	/**
	 * Post IDs
	 *
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
	}

	/**
	 * Clean up after each test.
	 */
	protected function tearDown(): void {
		// Call parent tearDown to handle database transaction rollback etc.
		parent::tearDown();
	}

	/**
	 * Test the get_uploaded_to_post() function to see if it returns the correct post Object.
	 */
	public function test_uploaded_to_post() {
		$result = Unused_Images::get_uploaded_to_post( $this->image_ids['one'] );

		// check the actual returned post
		$this->assertEquals( 'Post with Image One', $result->post_title );
	}
}