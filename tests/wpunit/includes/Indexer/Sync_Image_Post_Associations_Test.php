<?php

namespace ISC\Tests\WPUnit\Includes\Indexer;

use ISC\Indexer;
use ISC\Image_Sources\Post_Meta\Image_Posts_Meta;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Tests for Indexer::sync_image_post_associations().
 */
class Sync_Image_Post_Associations_Test extends WPTestCase {

	protected int $post_id;
	protected int $img1;
	protected int $img2;
	protected int $img3;

	public function setUp(): void {
		parent::setUp();
		$this->post_id = self::factory()->post->create();

		$this->img1 = self::factory()->attachment->create_upload_object( codecept_data_dir( '/test-image1.jpg' ), $this->post_id );
		$this->img2 = self::factory()->attachment->create_upload_object( codecept_data_dir( '/test-image2.jpg' ), $this->post_id );
		$this->img3 = self::factory()->attachment->create_upload_object( codecept_data_dir( '/test-image3.jpg' ), $this->post_id );
	}

	/**
	 * Should add image IDs that are in the new map but not in the old.
	 */
	public function test_adds_new_image_associations() {
		$old = [];
		$new = [
			$this->img1 => ['src' => '...'],
			$this->img2 => ['src' => '...'],
		];

		Indexer::sync_image_post_associations( $this->post_id, $old, $new );

		$this->assertContains( $this->post_id, Image_Posts_Meta::get( $this->img1 ) );
		$this->assertContains( $this->post_id, Image_Posts_Meta::get( $this->img2 ) );
	}

	/**
	 * Should remove image IDs that were in the old map but not in the new.
	 */
	public function test_removes_stale_image_associations() {
		Image_Posts_Meta::add_image_post_association( $this->img1, $this->post_id );
		Image_Posts_Meta::add_image_post_association( $this->img2, $this->post_id );

		$old = [
			$this->img1 => ['src' => '...'],
			$this->img2 => ['src' => '...'],
		];
		$new = []; // simulate all removed

		Indexer::sync_image_post_associations( $this->post_id, $old, $new );

		$this->assertNotContains( $this->post_id, Image_Posts_Meta::get( $this->img1 ) );
		$this->assertNotContains( $this->post_id, Image_Posts_Meta::get( $this->img2 ) );
	}

	/**
	 * Should add and remove correctly when maps differ.
	 */
	public function test_handles_combination_of_add_and_remove() {
		Image_Posts_Meta::add_image_post_association( $this->img1, $this->post_id );

		$old = [
			$this->img1 => ['src' => '...'],
		];
		$new = [
			$this->img2 => ['src' => '...'],
		];

		Indexer::sync_image_post_associations( $this->post_id, $old, $new );

		$this->assertNotContains( $this->post_id, Image_Posts_Meta::get( $this->img1 ) );
		$this->assertContains( $this->post_id, Image_Posts_Meta::get( $this->img2 ) );
	}

	/**
	 * Should ignore invalid image IDs and only update valid ones.
	 */
	public function test_ignores_invalid_ids() {
		$valid_img = $this->img1;

		$old = [
			0         => ['src' => '...'],
			'banana'  => ['src' => '...'],
			$valid_img => ['src' => '...'],
		];

		$new = [
			'apple'   => ['src' => '...'],
			$this->img2 => ['src' => '...'], // This one should be added
		];

		// Pre-associate the valid image (img1) so we can check if it gets removed
		Image_Posts_Meta::add_image_post_association( $valid_img, $this->post_id );
		// Check that img1 (valid, but removed) has been associated and img2 has not
		$this->assertContains( $this->post_id, Image_Posts_Meta::get( $valid_img ) );
		$this->assertEmpty( Image_Posts_Meta::get( $this->img2 ) );

		Indexer::sync_image_post_associations( $this->post_id, $old, $new );

		// Check that img1 (valid, but removed) has been disassociated
		$this->assertNotContains( $this->post_id, Image_Posts_Meta::get( $valid_img ) );

		// Check that img2 has been associated
		$this->assertContains( $this->post_id, Image_Posts_Meta::get( $this->img2 ) );

		// Ensure invalid IDs did not produce meta (image post meta is keyed by attachment IDs)
		$this->assertEmpty( get_post_meta( 0, 'isc_image_posts', true ) );
		$this->assertEmpty( get_post_meta( 'banana', 'isc_image_posts', true ) );
		$this->assertEmpty( get_post_meta( 'apple', 'isc_image_posts', true ) );
	}
}
