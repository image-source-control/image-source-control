<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Database_Scan;

use ISC\Pro\Unused_Images\Database_Scan\Database_Check_Model;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing Database_Check_Model::search_image_id_in_postmeta() and Database_Check_Model::get_image_id_post_meta_keys()
 */
class Database_Check_Model_Search_Attachment_Id_In_Postmeta_Test extends WPTestCase {
	/**
	 * Remove filters after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		remove_all_filters( 'isc_unused_images_attachment_id_post_meta_keys' );
		remove_all_filters( 'isc_unused_images_ignored_post_meta_keys' );
	}

	/**
	 * Test Database_Check_Model::search_image_id_in_postmeta() returns no results when no meta keys are configured.
	 */
	public function test_returns_no_results_when_no_meta_keys_are_configured() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id       = self::factory()->post->create();

		update_post_meta( $post_id, 'custom_image_id', (string) $attachment_id );

		$result = ( new Database_Check_Model() )->search_image_id_in_postmeta( $attachment_id );

		$this->assertSame( [], $result );
	}

	/**
	 * Test Database_Check_Model::search_image_id_in_postmeta() finds direct attachment ID matches for configured post meta keys.
	 */
	public function test_finds_direct_attachment_id_matches_for_configured_post_meta_keys() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id       = self::factory()->post->create();

		update_post_meta( $post_id, 'custom_image_id', (string) $attachment_id );

		add_filter(
			'isc_unused_images_attachment_id_post_meta_keys',
			function() {
				return [ 'custom_image_id' ];
			}
		);

		$result = ( new Database_Check_Model() )->search_image_id_in_postmeta( $attachment_id );

		$this->assertCount( 1, $result );
		$this->assertSame( $post_id, (int) $result[0]->post_id );
		$this->assertSame( 'custom_image_id', $result[0]->meta_key );
		$this->assertSame( 'id', $result[0]->search_type );
	}

	/**
	 * Test Database_Check_Model::search_image_id_in_postmeta() ignores non-configured post meta keys.
	 */
	public function test_ignores_non_configured_post_meta_keys() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id       = self::factory()->post->create();

		update_post_meta( $post_id, 'different_image_id', (string) $attachment_id );

		add_filter(
			'isc_unused_images_attachment_id_post_meta_keys',
			function() {
				return [ 'custom_image_id' ];
			}
		);

		$result = ( new Database_Check_Model() )->search_image_id_in_postmeta( $attachment_id );

		$this->assertSame( [], $result );
	}

	/**
	 * Test Database_Check_Model::search_image_id_in_postmeta() respects ignored post meta keys.
	 */
	public function test_respects_ignored_post_meta_keys() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id       = self::factory()->post->create();

		update_post_meta( $post_id, 'custom_image_id', (string) $attachment_id );

		add_filter(
			'isc_unused_images_attachment_id_post_meta_keys',
			function() {
				return [ 'custom_image_id' ];
			}
		);

		add_filter(
			'isc_unused_images_ignored_post_meta_keys',
			function( array $keys ) {
				$keys[] = 'custom_image_id';

				return $keys;
			}
		);

		$result = ( new Database_Check_Model() )->search_image_id_in_postmeta( $attachment_id );

		$this->assertSame( [], $result );
	}

	/**
	 * Test Database_Check_Model::get_image_id_post_meta_keys() accepts structured entries with key and name.
	 */
	public function test_accepts_structured_entries_with_key_and_name() {
		add_filter(
			'isc_unused_images_attachment_id_post_meta_keys',
			function() {
				return [
					[
						'key'  => 'hero_image_id',
						'name' => 'Hero Image',
					],
				];
			}
		);

		$result = Database_Check_Model::get_image_id_post_meta_keys();

		$this->assertArrayHasKey( 'hero_image_id', $result );
		$this->assertSame( 'Hero Image', $result['hero_image_id']['name'] );
	}

	/**
	 * Test Database_Check_Model::search_image_id_in_postmeta() does not match serialized arrays.
	 */
	public function test_does_not_match_serialized_arrays() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id       = self::factory()->post->create();

		update_post_meta(
			$post_id,
			'custom_image_id',
			[
				'image_id' => $attachment_id,
			]
		);

		add_filter(
			'isc_unused_images_attachment_id_post_meta_keys',
			function() {
				return [ 'custom_image_id' ];
			}
		);

		$result = ( new Database_Check_Model() )->search_image_id_in_postmeta( $attachment_id );

		$this->assertSame( [], $result );
	}

	/**
	 * Test Database_Check_Model::search_image_id_in_postmeta() excludes the attachment's own post ID.
	 */
	public function test_excludes_the_attachments_own_post_id() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id       = self::factory()->post->create();

		update_post_meta( $attachment_id, 'custom_image_id', (string) $attachment_id );
		update_post_meta( $post_id, 'custom_image_id', (string) $attachment_id );

		add_filter(
			'isc_unused_images_attachment_id_post_meta_keys',
			function() {
				return [ 'custom_image_id' ];
			}
		);

		$result   = ( new Database_Check_Model() )->search_image_id_in_postmeta( $attachment_id );
		$post_ids = array_map( 'intval', wp_list_pluck( $result, 'post_id' ) );

		$this->assertNotContains( $attachment_id, $post_ids );
		$this->assertContains( $post_id, $post_ids );
		$this->assertCount( 1, $result );
	}

	/**
	 * Test Database_Check_Model::search_image_id_in_postmeta() finds flat attachment IDs in remaining keys when enabled.
	 */
	public function test_search_remaining_keys_finds_flat_attachment_id() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id       = self::factory()->post->create();

		update_post_meta( $post_id, 'plugin_custom_image_id', (string) $attachment_id );

		$result = ( new Database_Check_Model() )->search_image_id_in_postmeta( $attachment_id, true );

		$this->assertCount( 1, $result );
		$this->assertSame( $post_id, (int) $result[0]->post_id );
		$this->assertSame( 'plugin_custom_image_id', $result[0]->meta_key );
		$this->assertSame( 'id', $result[0]->search_type );
	}

	/**
	 * Test Database_Check_Model::search_image_id_in_postmeta() excludes configured keys when searching remaining keys.
	 */
	public function test_search_remaining_keys_excludes_configured_postmeta_keys() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id       = self::factory()->post->create();

		update_post_meta( $post_id, '_thumbnail_id', (string) $attachment_id );
		update_post_meta( $post_id, 'plugin_custom_image_id', (string) $attachment_id );

		add_filter(
			'isc_unused_images_attachment_id_post_meta_keys',
			function() {
				return [ '_thumbnail_id' ];
			}
		);

		$result    = ( new Database_Check_Model() )->search_image_id_in_postmeta( $attachment_id, true );
		$meta_keys = wp_list_pluck( $result, 'meta_key' );

		$this->assertContains( 'plugin_custom_image_id', $meta_keys );
		$this->assertNotContains( '_thumbnail_id', $meta_keys );
	}

	/**
	 * Test Database_Check_Model::search_image_id_in_postmeta() respects ignored keys when searching remaining keys.
	 */
	public function test_search_remaining_keys_respects_default_ignored_post_meta_keys() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id       = self::factory()->post->create();

		update_post_meta( $post_id, 'isc_possible_usages', (string) $attachment_id );
		update_post_meta( $post_id, 'custom_image_id', (string) $attachment_id );

		$result    = ( new Database_Check_Model() )->search_image_id_in_postmeta( $attachment_id, true );
		$meta_keys = wp_list_pluck( $result, 'meta_key' );

		$this->assertNotContains( 'isc_possible_usages', $meta_keys );
		$this->assertContains( 'custom_image_id', $meta_keys );
	}

	/**
	 * Test Database_Check_Model::search_image_id_in_postmeta() excludes the attachment's own post ID when searching remaining keys.
	 */
	public function test_search_remaining_keys_excludes_the_attachments_own_post_id() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id       = self::factory()->post->create();

		update_post_meta( $attachment_id, 'plugin_custom_image_id', (string) $attachment_id );
		update_post_meta( $post_id, 'plugin_custom_image_id', (string) $attachment_id );

		$result   = ( new Database_Check_Model() )->search_image_id_in_postmeta( $attachment_id, true );
		$post_ids = array_map( 'intval', wp_list_pluck( $result, 'post_id' ) );

		$this->assertNotContains( $attachment_id, $post_ids );
		$this->assertContains( $post_id, $post_ids );
		$this->assertCount( 1, $result );
	}

	/**
	 * Test Database_Check_Model::search_image_id_in_postmeta() does not match serialized arrays when searching remaining keys.
	 */
	public function test_search_remaining_keys_does_not_match_serialized_arrays() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$post_id       = self::factory()->post->create();

		update_post_meta(
			$post_id,
			'plugin_nested_image_id',
			[
				'image_id' => $attachment_id,
			]
		);

		$result = ( new Database_Check_Model() )->search_image_id_in_postmeta( $attachment_id, true );

		$this->assertSame( [], $result );
	}
}