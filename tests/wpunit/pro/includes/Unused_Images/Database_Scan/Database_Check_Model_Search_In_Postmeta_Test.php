<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Database_Scan;

use ISC\Pro\Unused_Images\Database_Scan\Database_Check_Model;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing Database_Check_Model::search_filepath_in_postmeta() filter support
 */
class Database_Check_Model_Search_In_Postmeta_Test extends WPTestCase {

	/**
	 * Test search_filepath_in_postmeta respects ignored post meta keys filter
	 */
	public function test_respects_ignored_post_meta_keys_filter() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$image_url     = wp_get_attachment_url( $attachment_id );

		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'custom_ignored_key', $image_url );
		update_post_meta( $post_id, 'custom_normal_key', $image_url );

		add_filter( 'isc_unused_images_ignored_post_meta_keys', function( array $keys ) {
			$keys[] = 'custom_ignored_key';

			return $keys;
		} );

		$model  = new Database_Check_Model();
		$result = $model->search_filepath_in_postmeta( basename( $image_url, '.jpg' ), $attachment_id );

		$meta_keys = wp_list_pluck( $result, 'meta_key' );

		$this->assertNotContains( 'custom_ignored_key', $meta_keys );
		$this->assertContains( 'custom_normal_key', $meta_keys );

		remove_all_filters( 'isc_unused_images_ignored_post_meta_keys' );
	}

	/**
	 * Test search_filepath_in_postmeta respects default ignored keys
	 */
	public function test_respects_default_ignored_keys() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$image_url     = wp_get_attachment_url( $attachment_id );

		$post_id = self::factory()->post->create();

		// Add to default ignored keys
		update_post_meta( $post_id, 'isc_possible_usages', $image_url );
		update_post_meta( $post_id, 'isc_post_images', $image_url );
		update_post_meta( $post_id, 'isc_post_images_before_update', $image_url );

		// Add to non-ignored key
		update_post_meta( $post_id, 'custom_field', $image_url );

		$model  = new Database_Check_Model();
		$result = $model->search_filepath_in_postmeta( basename( $image_url, '.jpg' ), $attachment_id );

		$meta_keys = wp_list_pluck( $result, 'meta_key' );

		// Should not find any of the default ignored keys
		$this->assertNotContains( 'isc_possible_usages', $meta_keys );
		$this->assertNotContains( 'isc_post_images', $meta_keys );
		$this->assertNotContains( 'isc_post_images_before_update', $meta_keys );

		// Should find the custom field
		$this->assertContains( 'custom_field', $meta_keys );
	}

	/**
	 * Test search_filepath_in_postmeta excludes the image's own post_id
	 */
	public function test_excludes_image_own_post_id() {
		$attachment_id = self::factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$image_url     = wp_get_attachment_url( $attachment_id );

		// Add meta to the attachment itself
		update_post_meta( $attachment_id, 'some_meta', $image_url );

		// Add meta to another post
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, 'some_meta', $image_url );

		$model  = new Database_Check_Model();
		$result = $model->search_filepath_in_postmeta( basename( $image_url, '.jpg' ), $attachment_id );

		$post_ids = array_map( 'intval', wp_list_pluck( $result, 'post_id' ) );

		// Should not include the attachment's own ID
		$this->assertNotContains( $attachment_id, $post_ids );
		// Should include the other post
		$this->assertContains( $post_id, $post_ids );
	}
}