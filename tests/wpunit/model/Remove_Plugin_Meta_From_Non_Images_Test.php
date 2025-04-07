<?php

namespace ISC\Tests\WPUnit\Includes;

use ISC_Model;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test ISC_Model::remove_plugin_meta_from_non_images()
 */
class Remove_Plugin_Meta_From_Non_Images_Test extends WPTestCase {

	private $image_id;
	private $doc_id;

	private $meta_keys = [
		'isc_image_source',
		'isc_image_source_url',
		'isc_image_license',
		'isc_image_source_own',
		'isc_image_posts',
		'isc_possible_usages',
		'isc_possible_usages_last_check',
	];

	public function setUp(): void {
		parent::setUp();

		// Create image attachment with ISC meta
		$this->image_id = self::factory()->attachment->create( [
			                                                       'post_mime_type' => 'image/jpeg',
			                                                       'post_type'      => 'attachment',
		                                                       ] );
		$this->add_isc_meta( $this->image_id );

		// Create document attachment with ISC meta
		$this->doc_id = self::factory()->attachment->create( [
			                                                     'post_mime_type' => 'application/pdf',
			                                                     'post_type'      => 'attachment',
		                                                     ] );
		$this->add_isc_meta( $this->doc_id );
	}

	/**
	 * Add ISC meta to an attachment
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	private function add_isc_meta( int $attachment_id ): void {
		update_post_meta( $attachment_id, 'isc_image_source', 'John Doe' );
		update_post_meta( $attachment_id, 'isc_image_source_url', 'https://example.com' );
		update_post_meta( $attachment_id, 'isc_image_license', 'CC-BY' );
		update_post_meta( $attachment_id, 'isc_image_source_own', '1' );
		update_post_meta( $attachment_id, 'isc_image_posts', [ 1, 2 ] );
		update_post_meta( $attachment_id, 'isc_possible_usages', [ 1 ] );
		update_post_meta( $attachment_id, 'isc_possible_usages_last_check', time() );
	}

	/**
	 * Test if ISC meta is removed from non-image attachments
	 */
	public function test_meta_is_removed_from_non_image() {
		ISC_Model::remove_plugin_meta_from_non_images();

		foreach ( $this->meta_keys as $key ) {
			$this->assertEmpty(
				get_post_meta( $this->doc_id, $key, true ),
				"Meta key $key should have been deleted from non-image attachment."
			);
		}
	}

	/**
	 * Test if ISC meta is not removed from image attachments
	 */
	public function test_meta_is_not_removed_from_image() {
		ISC_Model::remove_plugin_meta_from_non_images();

		foreach ( $this->meta_keys as $key ) {
			$this->assertNotEmpty(
				get_post_meta( $this->image_id, $key, true ),
				"Meta key $key should NOT have been deleted from image attachment."
			);
		}
	}
}
