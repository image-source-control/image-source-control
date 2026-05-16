<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Database_Scan;

use ISC\Pro\Unused_Images\Database_Scan\Database_Check_Model;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing Database_Check_Model::search_attachment_id_in_termmeta() and Database_Check_Model::get_term_meta_keys()
 */
class Database_Check_Model_Search_Attachment_Id_In_Termmeta_Test extends WPTestCase {
	/**
	 * Remove filters after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		remove_all_filters( 'isc_unused_images_term_meta_keys' );
	}

	/**
	 * Test Database_Check_Model::search_attachment_id_in_termmeta() returns no results when no term meta keys are configured.
	 */
	public function test_returns_no_results_when_no_term_meta_keys_are_configured() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$term_id       = self::factory()->term->create(
			[
				'taxonomy' => 'category',
			]
		);

		update_term_meta( $term_id, 'custom_image_id', (string) $attachment_id );

		$result = Database_Check_Model::search_attachment_id_in_termmeta( $attachment_id );

		$this->assertSame( [], $result );
	}

	/**
	 * Test Database_Check_Model::search_attachment_id_in_termmeta() finds direct attachment ID matches for configured term meta keys.
	 */
	public function test_finds_direct_attachment_id_matches_for_configured_term_meta_keys() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$term_id       = self::factory()->term->create(
			[
				'taxonomy' => 'category',
			]
		);

		update_term_meta( $term_id, 'custom_image_id', (string) $attachment_id );

		add_filter(
			'isc_unused_images_term_meta_keys',
			function() {
				return [
					[
						'key'  => 'custom_image_id',
						'name' => 'Custom Image',
					],
				];
			}
		);

		$result = Database_Check_Model::search_attachment_id_in_termmeta( $attachment_id );

		$this->assertCount( 1, $result );
		$this->assertSame( $term_id, (int) $result[0]->term_id );
		$this->assertSame( 'custom_image_id', $result[0]->meta_key );
	}

	/**
	 * Test Database_Check_Model::search_attachment_id_in_termmeta() ignores non-configured term meta keys.
	 */
	public function test_ignores_non_configured_term_meta_keys() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$term_id       = self::factory()->term->create(
			[
				'taxonomy' => 'category',
			]
		);

		update_term_meta( $term_id, 'different_image_id', (string) $attachment_id );

		add_filter(
			'isc_unused_images_term_meta_keys',
			function() {
				return [
					[
						'key'  => 'custom_image_id',
						'name' => 'Custom Image',
					],
				];
			}
		);

		$result = Database_Check_Model::search_attachment_id_in_termmeta( $attachment_id );

		$this->assertSame( [], $result );
	}

	/**
	 * Test Database_Check_Model::get_term_meta_keys() accepts structured entries with key and name.
	 */
	public function test_accepts_structured_entries_with_key_and_name() {
		add_filter(
			'isc_unused_images_term_meta_keys',
			function() {
				return [
					[
						'key'  => 'hero_image_id',
						'name' => 'Hero Image',
					],
				];
			}
		);

		$result = Database_Check_Model::get_term_meta_keys();

		$this->assertArrayHasKey( 'hero_image_id', $result );
		$this->assertSame( 'Hero Image', $result['hero_image_id']['name'] );
	}

	/**
	 * Test Database_Check_Model::get_term_meta_keys() falls back to an empty name when omitted.
	 */
	public function test_falls_back_to_empty_name_when_name_is_omitted() {
		add_filter(
			'isc_unused_images_term_meta_keys',
			function() {
				return [
					[
						'key' => 'hero_image_id',
					],
				];
			}
		);

		$result = Database_Check_Model::get_term_meta_keys();

		$this->assertArrayHasKey( 'hero_image_id', $result );
		$this->assertSame( '', $result['hero_image_id']['name'] );
	}

	/**
	 * Test Database_Check_Model::search_attachment_id_in_termmeta() does not match serialized arrays.
	 */
	public function test_does_not_match_serialized_arrays() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$term_id       = self::factory()->term->create(
			[
				'taxonomy' => 'category',
			]
		);

		update_term_meta(
			$term_id,
			'custom_image_id',
			[
				'image_id' => $attachment_id,
			]
		);

		add_filter(
			'isc_unused_images_term_meta_keys',
			function() {
				return [
					[
						'key'  => 'custom_image_id',
						'name' => 'Custom Image',
					],
				];
			}
		);

		$result = Database_Check_Model::search_attachment_id_in_termmeta( $attachment_id );

		$this->assertSame( [], $result );
	}

	/**
	 * Test Database_Check_Model::search_attachment_id_in_termmeta() matches exact string values only.
	 */
	public function test_matches_exact_string_values_only() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$matching_id   = self::factory()->term->create(
			[
				'taxonomy' => 'category',
			]
		);
		$non_match_id  = self::factory()->term->create(
			[
				'taxonomy' => 'category',
			]
		);

		update_term_meta( $matching_id, 'custom_image_id', (string) $attachment_id );
		update_term_meta( $non_match_id, 'custom_image_id', 'prefix-' . $attachment_id );

		add_filter(
			'isc_unused_images_term_meta_keys',
			function() {
				return [
					[
						'key'  => 'custom_image_id',
						'name' => 'Custom Image',
					],
				];
			}
		);

		$result   = Database_Check_Model::search_attachment_id_in_termmeta( $attachment_id );
		$term_ids = array_map( 'intval', wp_list_pluck( $result, 'term_id' ) );

		$this->assertContains( $matching_id, $term_ids );
		$this->assertNotContains( $non_match_id, $term_ids );
		$this->assertCount( 1, $result );
	}

	/**
	 * Test Database_Check_Model::search_attachment_id_in_termmeta() finds flat attachment IDs in remaining keys when enabled.
	 */
	public function test_search_remaining_keys_finds_flat_attachment_id() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$term_id       = self::factory()->term->create(
			[
				'taxonomy' => 'category',
			]
		);

		update_term_meta( $term_id, 'plugin_custom_image_id', (string) $attachment_id );

		$result = Database_Check_Model::search_attachment_id_in_termmeta( $attachment_id, true );

		$this->assertCount( 1, $result );
		$this->assertSame( $term_id, (int) $result[0]->term_id );
		$this->assertSame( 'plugin_custom_image_id', $result[0]->meta_key );
	}

	/**
	 * Test Database_Check_Model::search_attachment_id_in_termmeta() excludes configured keys when searching remaining keys.
	 */
	public function test_search_remaining_keys_excludes_configured_term_meta_keys() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$configured_id = self::factory()->term->create(
			[
				'taxonomy' => 'category',
			]
		);
		$custom_id     = self::factory()->term->create(
			[
				'taxonomy' => 'category',
			]
		);

		update_term_meta( $configured_id, 'custom_image_id', (string) $attachment_id );
		update_term_meta( $custom_id, 'plugin_custom_image_id', (string) $attachment_id );

		add_filter(
			'isc_unused_images_term_meta_keys',
			function() {
				return [
					[
						'key'  => 'custom_image_id',
						'name' => 'Custom Image',
					],
				];
			}
		);

		$result    = Database_Check_Model::search_attachment_id_in_termmeta( $attachment_id, true );
		$meta_keys = wp_list_pluck( $result, 'meta_key' );
		$term_ids  = array_map( 'intval', wp_list_pluck( $result, 'term_id' ) );

		$this->assertContains( 'plugin_custom_image_id', $meta_keys );
		$this->assertContains( $custom_id, $term_ids );
		$this->assertNotContains( 'custom_image_id', $meta_keys );
		$this->assertNotContains( $configured_id, $term_ids );
	}

	/**
	 * Test Database_Check_Model::search_attachment_id_in_termmeta() does not match serialized arrays when searching remaining keys.
	 */
	public function test_search_remaining_keys_does_not_match_serialized_arrays() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$term_id       = self::factory()->term->create(
			[
				'taxonomy' => 'category',
			]
		);

		update_term_meta(
			$term_id,
			'plugin_nested_image_id',
			[
				'image_id' => $attachment_id,
			]
		);

		$result = Database_Check_Model::search_attachment_id_in_termmeta( $attachment_id, true );

		$this->assertSame( [], $result );
	}
}