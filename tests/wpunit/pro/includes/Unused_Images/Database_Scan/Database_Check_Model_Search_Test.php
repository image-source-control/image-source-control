<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Database_Scan;

use ISC\Pro\Unused_Images\Database_Scan\Database_Check_Model;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing Database_Check_Model:: search() integration tests
 */
class Database_Check_Model_Search_Test extends WPTestCase {

	/**
	 * Test search() returns false with invalid image ID
	 */
	public function test_search_returns_false_with_invalid_id() {
		$model  = new Database_Check_Model();
		$result = $model->search( 0 );

		$this->assertFalse( $result );
	}

	/**
	 * Test search() stores meta data after successful search
	 */
	public function test_search_stores_meta_data() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$model = new Database_Check_Model();
		$model->search( $attachment_id );

		// Check that meta was stored
		$possible_usages = get_post_meta( $attachment_id, 'isc_possible_usages', true );
		$last_check      = get_post_meta( $attachment_id, 'isc_possible_usages_last_check', true );

		$this->assertIsArray( $possible_usages );
		$this->assertIsInt( (int) $last_check );
		$this->assertGreaterThan( 0, (int) $last_check );
	}

	/**
	 * Test search() finds image in post content
	 */
	public function test_search_finds_image_in_post_content() {
		$attachment_id = self:: factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$image_url     = wp_get_attachment_url( $attachment_id );

		// Create post with image in content
		$post_id = self::factory()->post->create( array(
			                                          'post_content' => '<img src="' . $image_url . '" />',
		                                          ) );

		$model = new Database_Check_Model();
		$model->search( $attachment_id );

		$possible_usages = get_post_meta( $attachment_id, 'isc_possible_usages', true );

		$this->assertArrayHasKey( 'posts', $possible_usages );
		$this->assertNotEmpty( $possible_usages['posts'] );
	}

	/**
	 * Test search() finds image in postmeta
	 */
	public function test_search_finds_image_in_postmeta() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$image_url     = wp_get_attachment_url( $attachment_id );

		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'custom_image_field', $image_url );

		$model = new Database_Check_Model();
		$model->search( $attachment_id );

		$possible_usages = get_post_meta( $attachment_id, 'isc_possible_usages', true );

		$this->assertArrayHasKey( 'postmetas', $possible_usages );
		$this->assertNotEmpty( $possible_usages['postmetas'] );
	}

	/**
	 * Test search() finds image in options
	 */
	public function test_search_finds_image_in_options() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$image_url     = wp_get_attachment_url( $attachment_id );

		update_option( 'test_custom_option', array( 'image' => $image_url ) );

		$model = new Database_Check_Model();
		$model->search( $attachment_id );

		$possible_usages = get_post_meta( $attachment_id, 'isc_possible_usages', true );

		$this->assertArrayHasKey( 'options', $possible_usages );
		$this->assertNotEmpty( $possible_usages['options'] );

		delete_option( 'test_custom_option' );
	}

	/**
	 * Test search() finds image in usermeta
	 */
	public function test_search_finds_image_in_usermeta() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$image_url     = wp_get_attachment_url( $attachment_id );

		$user_id = self::factory()->user->create();
		update_user_meta( $user_id, 'profile_image', $image_url );

		$model = new Database_Check_Model();
		$model->search( $attachment_id );

		$possible_usages = get_post_meta( $attachment_id, 'isc_possible_usages', true );

		$this->assertArrayHasKey( 'usermetas', $possible_usages );
		$this->assertNotEmpty( $possible_usages['usermetas'] );
	}

	/**
	 * Test search() returns empty array when image not found anywhere
	 */
	public function test_search_returns_empty_when_not_found() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$model = new Database_Check_Model();
		$model->search( $attachment_id );

		$possible_usages = get_post_meta( $attachment_id, 'isc_possible_usages', true );

		$this->assertIsArray( $possible_usages );
		$this->assertEmpty( $possible_usages );
	}

	/**
	 * Test search() updates timestamp
	 */
	public function test_search_updates_timestamp() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$before = time();
		$model  = new Database_Check_Model();
		$model->search( $attachment_id );
		$after = time();

		$timestamp = (int) get_post_meta( $attachment_id, 'isc_possible_usages_last_check', true );

		$this->assertGreaterThanOrEqual( $before, $timestamp );
		$this->assertLessThanOrEqual( $after, $timestamp );
	}

	/**
	 * Test search() applies isc_unused_images_database_search_results filter
	 */
	public function test_search_applies_results_filter() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		add_filter( 'isc_unused_images_database_search_results', function( $results, $image_id ) {
			$results['custom'] = array( 'custom_data' => 'test' );

			return $results;
		},          10, 2 );

		$model = new Database_Check_Model();
		$model->search( $attachment_id );

		$possible_usages = get_post_meta( $attachment_id, 'isc_possible_usages', true );

		$this->assertArrayHasKey( 'custom', $possible_usages );
		$this->assertEquals( 'test', $possible_usages['custom']['custom_data'] );

		remove_all_filters( 'isc_unused_images_database_search_results' );
	}

	/**
	 * Test search() returns true on successful search
	 */
	public function test_search_returns_true_on_success() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );

		$model  = new Database_Check_Model();
		$result = $model->search( $attachment_id );

		$this->assertTrue( $result );
	}
}