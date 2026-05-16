<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Database_Scan;

use ISC\Options;
use ISC\Pro\Unused_Images\Database_Scan\Database_Check_Model;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing Database_Check_Model::search() integration tests
 */
class Database_Check_Model_Search_Test extends WPTestCase {

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		delete_option( 'isc_options' );

		parent::tearDown();
	}

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
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$image_url     = wp_get_attachment_url( $attachment_id );

		// Create post with image in content
		self::factory()->post->create(
			array(
				'post_content' => '<img src="' . $image_url . '" />',
			)
		);

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

		add_filter(
			'isc_unused_images_database_search_results',
			function( $results, $image_id ) {
				$results['custom'] = array( 'custom_data' => 'test' );

				return $results;
			},
			10,
			2
		);

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

	/**
	 * Test search() includes extended postmeta results when additional checks are enabled.
	 */
	public function test_search_includes_extended_postmeta_results_when_additional_checks_enabled() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id       = self::factory()->post->create();

		update_post_meta( $post_id, 'plugin_custom_image_id', (string) $attachment_id );

		$isc_options                                 = Options::get_options();
		$isc_options['unused_images']['deep_checks'] = [ Database_Check_Model::DEEP_CHECK_EXTENDED_FIELDS ];
		update_option( 'isc_options', $isc_options );

		$model = new Database_Check_Model();
		$model->search( $attachment_id );

		$possible_usages = get_post_meta( $attachment_id, 'isc_possible_usages', true );

		$this->assertArrayHasKey( 'postmetas', $possible_usages );
		$this->assertNotEmpty( $possible_usages['postmetas'] );

		$meta_keys = wp_list_pluck( $possible_usages['postmetas'], 'meta_key' );
		$post_ids  = array_map( 'intval', wp_list_pluck( $possible_usages['postmetas'], 'post_id' ) );

		$this->assertContains( 'plugin_custom_image_id', $meta_keys );
		$this->assertContains( $post_id, $post_ids );
	}

	/**
	 * Test search() excludes extended postmeta results when additional checks are disabled.
	 */
	public function test_search_excludes_extended_postmeta_results_when_additional_checks_disabled() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id       = self::factory()->post->create();

		update_post_meta( $post_id, 'plugin_custom_image_id', (string) $attachment_id );

		$isc_options                                 = Options::get_options();
		$isc_options['unused_images']['deep_checks'] = [];
		update_option( 'isc_options', $isc_options );

		$model = new Database_Check_Model();
		$model->search( $attachment_id );

		$possible_usages = get_post_meta( $attachment_id, 'isc_possible_usages', true );

		if ( isset( $possible_usages['postmetas'] ) ) {
			$meta_keys = wp_list_pluck( $possible_usages['postmetas'], 'meta_key' );
			$this->assertNotContains( 'plugin_custom_image_id', $meta_keys );
		} else {
			$this->assertArrayNotHasKey( 'postmetas', $possible_usages );
		}
	}

	/**
	 * Test search() includes extended termmeta results when additional checks are enabled.
	 */
	public function test_search_includes_extended_termmeta_results_when_additional_checks_enabled() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$term_id       = self::factory()->term->create(
			[
				'taxonomy' => 'category',
			]
		);

		update_term_meta( $term_id, 'plugin_custom_image_id', (string) $attachment_id );

		$isc_options                                 = Options::get_options();
		$isc_options['unused_images']['deep_checks'] = [ Database_Check_Model::DEEP_CHECK_EXTENDED_FIELDS ];
		update_option( 'isc_options', $isc_options );

		$model = new Database_Check_Model();
		$model->search( $attachment_id );

		$possible_usages = get_post_meta( $attachment_id, 'isc_possible_usages', true );

		$this->assertArrayHasKey( 'termmetas', $possible_usages );
		$this->assertNotEmpty( $possible_usages['termmetas'] );

		$meta_keys = wp_list_pluck( $possible_usages['termmetas'], 'meta_key' );
		$term_ids  = array_map( 'intval', wp_list_pluck( $possible_usages['termmetas'], 'term_id' ) );

		$this->assertContains( 'plugin_custom_image_id', $meta_keys );
		$this->assertContains( $term_id, $term_ids );
	}

	/**
	 * Test search() excludes extended termmeta results when additional checks are disabled.
	 */
	public function test_search_excludes_extended_termmeta_results_when_additional_checks_disabled() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$term_id       = self::factory()->term->create(
			[
				'taxonomy' => 'category',
			]
		);

		update_term_meta( $term_id, 'plugin_custom_image_id', (string) $attachment_id );

		$isc_options                                 = Options::get_options();
		$isc_options['unused_images']['deep_checks'] = [];
		update_option( 'isc_options', $isc_options );

		$model = new Database_Check_Model();
		$model->search( $attachment_id );

		$possible_usages = get_post_meta( $attachment_id, 'isc_possible_usages', true );

		if ( isset( $possible_usages['termmetas'] ) ) {
			$meta_keys = wp_list_pluck( $possible_usages['termmetas'], 'meta_key' );
			$this->assertNotContains( 'plugin_custom_image_id', $meta_keys );
		} else {
			$this->assertArrayNotHasKey( 'termmetas', $possible_usages );
		}
	}
}