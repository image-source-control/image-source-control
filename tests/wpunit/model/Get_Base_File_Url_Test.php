<?php

namespace ISC\Tests\WPUnit;

use \ISC_Model;

/**
 * Test if ISC_Model::get_base_file_name() works as expected.
 */
class Get_Base_File_Url_Test extends \Codeception\TestCase\WPTestCase {

	public function setUp(): void {
		parent::setUp();

		// image 1 has its path in `guid` and a different one in the `_wp_attached_file` post meta
		$this->image_one_id = $this->factory->post->create( [
			'post_title' => 'Image One',
			'post_type'  => 'attachment',
			'guid'       => 'https://example.com/image-one.png',
		] );

		update_post_meta( $this->image_one_id, '_wp_attached_file', 'https://example.com/picture-one.png' );

		// image two has its path only in `guid`
		$this->image_two_id = $this->factory->post->create( [
			'post_title' => 'Image Two',
			'post_type'  => 'attachment',
			'guid'       => 'https://example.com/image-two.png',
		] );

		// image three has its path only in `_wp_attached_file`
		$this->image_three_id = $this->factory->post->create( [
			'post_title' => 'Image Three',
			'post_type'  => 'attachment',
		] );

		update_post_meta( $this->image_three_id, '_wp_attached_file', 'https://example.com/image-three.png' );
	}

	/**
	 * Test if get_base_file_name() returns the base file name when guid and _wp_attached_file are different
	 * Note: returns the value for _wp_attached_file
	 */
	public function test_guid_and_wp_attached_file() {
		$model  = new ISC_Model();
		$actual = $model->get_base_file_url( $this->image_one_id );
		$this->assertEquals( 'https://example.com/picture-one.png', $actual );
	}

	/**
	 * Test if get_base_file_name() returns the base file name when guid is set
	 */
	public function test_guid_only() {
		$model  = new ISC_Model();
		$actual = $model->get_base_file_url( $this->image_two_id );
		$this->assertEquals( 'https://example.com/image-two.png', $actual );
	}

	/**
	 * Test if get_base_file_name() returns the base file name when _wp_attached_file is set
	 */
	public function test_wp_attached_file_only() {
		$model  = new ISC_Model();
		$actual = $model->get_base_file_url( $this->image_three_id );
		$this->assertEquals( 'https://example.com/image-three.png', $actual );
	}
}
