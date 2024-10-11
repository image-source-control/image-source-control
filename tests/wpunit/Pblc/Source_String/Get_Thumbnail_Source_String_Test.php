<?php

namespace ISC\Tests\WPUnit\Pblc\Source_String;

use ISC\Tests\WPUnit\WPTestCase;
use \ISC_Public;

/**
 * Test if ISC_Public::get_thumbnail_source_string() renders the image source string correctly for featured images
 */
class Get_Thumbnail_Source_String_Test extends WPTestCase {

	private $iscPublic;

	/**
	 * Image ID
	 *
	 * @var int
	 */
	private $image_id;

	/**
	 * Post ID
	 *
	 * @var int
	 */
	private $post_id;

	protected function setUp(): void {
		$this->iscPublic = new ISC_Public();

		$this->post_id = $this->factory()->post->create( [
			                                                  'post_title' => 'Post One',
			                                                  'post_type'  => 'post',
			                                                  'guid'       => 'https://example.com/post-one',
		                                                  ] );

		$this->image_id = $this->factory()->post->create( [
			                                                  'post_title' => 'Image One',
			                                                  'post_type'  => 'attachment',
			                                                  'guid'       => 'https://example.com/image-one.jpg',
		                                                  ] );

		add_post_meta( $this->image_id, 'isc_image_source', 'Author A' );
		add_post_meta( $this->post_id, '_thumbnail_id', $this->image_id );
	}

	/**
	 * A post without a featured image should return an empty string
	 */
	public function test_post_without_featured_image() {
		$post_id = $this->factory()->post->create( [
                 'post_title' => 'Post without a featured image',
                 'post_type'  => 'post',
                 'guid'       => 'https://example.com/post',
             ] );

		$this->assertEquals( '', $this->iscPublic->get_thumbnail_source_string( $post_id ) );
	}

	/**
	 * A post with a featured images returns the image source string
	 */
	public function test_post_with_featured_image() {
		$this->assertEquals( '<p class="isc-source-text">Featured image:  Author A</p>', $this->iscPublic->get_thumbnail_source_string( $this->post_id ) );
	}

	/**
	 * If the standard source is set to be shown, show that instead of the image author
	 */
	public function test_standard_source() {
		// activate standard source for the image
		add_post_meta( $this->image_id, 'isc_image_source_own', 1, true );

		$this->assertEquals( '<p class="isc-source-text">Featured image:  © http://isc.local</p>', $this->iscPublic->get_thumbnail_source_string( $this->post_id ) );
	}

	/**
	 * If no source is set, return empty string
	 */
	public function test_without_source() {
		delete_post_meta( $this->image_id, 'isc_image_source' );
		$this->assertEquals( '', $this->iscPublic->get_thumbnail_source_string( $this->image_id ) );
	}

	/**
	 * Test if the filter isc_featured_image_source_pre_text works
	 */
	public function test_filter_isc_featured_image_source_pre_text() {
		add_filter( 'isc_featured_image_source_pre_text', function () {
			return 'Author:';
		} );

		$this->assertEquals( '<p class="isc-source-text">Author: Author A</p>', $this->iscPublic->get_thumbnail_source_string( $this->post_id ) );
	}
}