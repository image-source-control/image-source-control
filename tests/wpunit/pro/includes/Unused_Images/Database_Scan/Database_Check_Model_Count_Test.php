<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Database_Scan;

use ISC\Pro\Unused_Images\Database_Scan\Database_Check_Model;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing Database_Check_Model count methods
 */
class Database_Check_Model_Count_Test extends WPTestCase {

	/**
	 * Test count_images_checked_since returns 0 with no checked images
	 */
	public function test_count_images_checked_since_with_no_images() {
		$model = new Database_Check_Model();
		$count = $model->count_images_checked_since( time() - 3600 );

		$this->assertEquals( 0, $count );
	}

	/**
	 * Test count_images_checked_since counts images checked within timeframe
	 */
	public function test_count_images_checked_since_within_timeframe() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$current_time = time();
		update_post_meta( $attachment_id, 'isc_possible_usages', array() );
		update_post_meta( $attachment_id, 'isc_possible_usages_last_check', $current_time );

		$model = new Database_Check_Model();
		$count = $model->count_images_checked_since( $current_time - 60 );

		$this->assertGreaterThanOrEqual( 1, $count );
	}

	/**
	 * Test count_images_checked_since excludes images checked before timeframe
	 */
	public function test_count_images_checked_since_excludes_old_checks() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$old_time = time() - 7200; // 2 hours ago
		update_post_meta( $attachment_id, 'isc_possible_usages', array() );
		update_post_meta( $attachment_id, 'isc_possible_usages_last_check', $old_time );

		$model = new Database_Check_Model();
		$count = $model->count_images_checked_since( time() - 3600 ); // 1 hour ago

		$this->assertEquals( 0, $count );
	}

	/**
	 * Test count_images_checked_since with future timestamp returns 0
	 */
	public function test_count_images_checked_since_future_timestamp() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		update_post_meta( $attachment_id, 'isc_possible_usages', array() );
		update_post_meta( $attachment_id, 'isc_possible_usages_last_check', time() );

		$model = new Database_Check_Model();
		$count = $model->count_images_checked_since( time() + 3600 ); // 1 hour in future

		$this->assertEquals( 0, $count );
	}

	/**
	 * Test count_images_with_usages_since returns 0 when no images have usages
	 */
	public function test_count_images_with_usages_since_with_no_usages() {
		$attachment_id = self:: factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		update_post_meta( $attachment_id, 'isc_possible_usages', array() ); // empty array
		update_post_meta( $attachment_id, 'isc_possible_usages_last_check', time() );

		$model = new Database_Check_Model();
		$count = $model->count_images_with_usages_since( time() - 3600 );

		$this->assertEquals( 0, $count );
	}

	/**
	 * Test count_images_with_usages_since counts images with usages
	 */
	public function test_count_images_with_usages_since_with_usages() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$current_time = time();
		update_post_meta( $attachment_id, 'isc_possible_usages', array( 'posts' => array( 1, 2 ) ) );
		update_post_meta( $attachment_id, 'isc_possible_usages_last_check', $current_time );

		$model = new Database_Check_Model();
		$count = $model->count_images_with_usages_since( $current_time - 60 );

		$this->assertGreaterThanOrEqual( 1, $count );
	}

	/**
	 * Test count_images_with_usages_since excludes empty arrays (serialized as 'a:0:{}')
	 */
	public function test_count_images_with_usages_since_excludes_empty_arrays() {
		$attachment_with_usages = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$attachment_without     = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );

		$current_time = time();

		// Image with usages
		update_post_meta( $attachment_with_usages, 'isc_possible_usages', array( 'posts' => array( 1 ) ) );
		update_post_meta( $attachment_with_usages, 'isc_possible_usages_last_check', $current_time );

		// Image without usages (empty array)
		update_post_meta( $attachment_without, 'isc_possible_usages', array() );
		update_post_meta( $attachment_without, 'isc_possible_usages_last_check', $current_time );

		$model = new Database_Check_Model();
		$count = $model->count_images_with_usages_since( $current_time - 60 );

		// Should count only the one with usages
		$this->assertEquals( 1, $count );
	}

	/**
	 * Test count_truly_unused_since returns 0 when all images have usages
	 */
	public function test_count_truly_unused_since_with_all_used() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$current_time = time();
		update_post_meta( $attachment_id, 'isc_possible_usages', array( 'posts' => array( 1 ) ) );
		update_post_meta( $attachment_id, 'isc_possible_usages_last_check', $current_time );

		$model = new Database_Check_Model();
		$count = $model->count_truly_unused_since( $current_time - 60 );

		$this->assertEquals( 0, $count );
	}

	/**
	 * Test count_truly_unused_since counts images with empty usages array
	 */
	public function test_count_truly_unused_since_with_empty_usages() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$current_time = time();
		update_post_meta( $attachment_id, 'isc_possible_usages', array() ); // truly unused
		update_post_meta( $attachment_id, 'isc_possible_usages_last_check', $current_time );

		$model = new Database_Check_Model();
		$count = $model->count_truly_unused_since( $current_time - 60 );

		$this->assertGreaterThanOrEqual( 1, $count );
	}

	/**
	 * Test count_truly_unused_since excludes images with usages
	 */
	public function test_count_truly_unused_since_excludes_used_images() {
		$unused_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$used_id   = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );

		$current_time = time();

		// Truly unused
		update_post_meta( $unused_id, 'isc_possible_usages', array() );
		update_post_meta( $unused_id, 'isc_possible_usages_last_check', $current_time );

		// Used
		update_post_meta( $used_id, 'isc_possible_usages', array( 'posts' => array( 1 ) ) );
		update_post_meta( $used_id, 'isc_possible_usages_last_check', $current_time );

		$model = new Database_Check_Model();
		$count = $model->count_truly_unused_since( $current_time - 60 );

		// Should count only the unused one
		$this->assertEquals( 1, $count );
	}

	/**
	 * Test all three count methods with multiple images
	 */
	public function test_count_methods_with_multiple_images() {
		$current_time = time();

		// Create 3 images with different states
		$unused_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		update_post_meta( $unused_id, 'isc_possible_usages', array() );
		update_post_meta( $unused_id, 'isc_possible_usages_last_check', $current_time );

		$used_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );
		update_post_meta( $used_id, 'isc_possible_usages', array( 'posts' => array( 1 ) ) );
		update_post_meta( $used_id, 'isc_possible_usages_last_check', $current_time );

		$old_check_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		update_post_meta( $old_check_id, 'isc_possible_usages', array() );
		update_post_meta( $old_check_id, 'isc_possible_usages_last_check', $current_time - 7200 ); // 2 hours ago

		$model = new Database_Check_Model();

		$checked_count = $model->count_images_checked_since( $current_time - 3600 ); // 1 hour ago
		$with_usages   = $model->count_images_with_usages_since( $current_time - 3600 );
		$truly_unused  = $model->count_truly_unused_since( $current_time - 3600 );

		// Should count 2 checked (unused_id and used_id)
		$this->assertEquals( 2, $checked_count );
		// Should count 1 with usages (used_id)
		$this->assertEquals( 1, $with_usages );
		// Should count 1 truly unused (unused_id)
		$this->assertEquals( 1, $truly_unused );
	}

	/**
	 * Test count_checked_unused_images with empty base filter
	 */
	public function test_count_checked_unused_images_with_empty_filter() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		update_post_meta( $attachment_id, 'isc_possible_usages', array() );

		$model = new Database_Check_Model();
		$count = $model->count_checked_unused_images( '' );

		$this->assertGreaterThanOrEqual( 1, $count );
	}

	/**
	 * Test count_checked_unused_images excludes unchecked images
	 */
	public function test_count_checked_unused_images_excludes_unchecked() {
		$checked_id   = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$unchecked_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );

		update_post_meta( $checked_id, 'isc_possible_usages', array() );
		// Don't add meta to unchecked_id

		$model = new Database_Check_Model();
		$count = $model->count_checked_unused_images( '' );

		// Should only count the checked one
		$this->assertGreaterThanOrEqual( 1, $count );
	}

	/**
	 * Test count_checked_unused_images with filter query
	 */
	public function test_count_checked_unused_images_with_filter() {
		$image_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		update_post_meta( $image_id, 'isc_possible_usages', array() );

		$model = new Database_Check_Model();

		// Filter that should match
		$filter = "AND p.post_mime_type LIKE 'image/%'";
		$count  = $model->count_checked_unused_images( $filter );

		$this->assertGreaterThanOrEqual( 1, $count );
	}

	/**
	 * Test count_checked_unused_images returns 0 with no matches
	 */
	public function test_count_checked_unused_images_returns_zero_with_no_matches() {
		$model = new Database_Check_Model();

		// Filter that won't match anything
		$filter = "AND p.ID = 999999";
		$count  = $model->count_checked_unused_images( $filter );

		$this->assertEquals( 0, $count );
	}
}