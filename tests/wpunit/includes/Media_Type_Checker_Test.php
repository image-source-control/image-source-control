<?php

namespace ISC\Tests\WPUnit\Includes;

use ISC\Tests\WPUnit\WPTestCase;
use ISC\Media_Type_Checker;

/**
 * Test if ISC\Media_Type_Checker works as expected.
 */
class Media_Type_Checker_Test extends WPTestCase {

	/**
	 * @var int
	 */
	private $image_id;

	/**
	 * @var int
	 */
	private $document_id;

	public function setUp(): void {
		parent::setUp();

		// Create an image attachment
		$this->image_id = self::factory()->attachment->create( [
			'post_mime_type' => 'image/jpeg',
			'post_type' => 'attachment',
		] );

		// Create a document attachment
		$this->document_id = self::factory()->attachment->create( [
			'post_mime_type' => 'application/pdf',
			'post_type' => 'attachment',
		] );
	}

	/**
	 * Test if should_process_attachment returns true for images when images_only is enabled
	 */
	public function test_should_process_attachment_image_with_images_only() {
		// Enable images_only option
		$options = \ISC\Plugin::get_options();
		$options['images_only'] = true;
		update_option( 'isc_options', $options );

		$result = Media_Type_Checker::should_process_attachment( $this->image_id );
		$this->assertTrue( $result, 'should_process_attachment should return true for images when images_only is enabled' );
	}

	/**
	 * Test if should_process_attachment returns false for non-images when images_only is enabled
	 */
	public function test_should_process_attachment_non_image_with_images_only() {
		// Enable images_only option
		$options = \ISC\Plugin::get_options();
		$options['images_only'] = true;
		update_option( 'isc_options', $options );

		$result = Media_Type_Checker::should_process_attachment( $this->document_id );
		$this->assertFalse( $result, 'should_process_attachment should return false for non-images when images_only is enabled' );
	}

	/**
	 * Test if should_process_attachment returns true for all attachments when images_only is disabled
	 */
	public function test_should_process_attachment_without_images_only() {
		// Disable images_only option
		$options = \ISC\Plugin::get_options();
		$options['images_only'] = false;
		update_option( 'isc_options', $options );

		$result_image = Media_Type_Checker::should_process_attachment( $this->image_id );
		$result_doc = Media_Type_Checker::should_process_attachment( $this->document_id );

		$this->assertTrue( $result_image, 'should_process_attachment should return true for images when images_only is disabled' );
		$this->assertTrue( $result_doc, 'should_process_attachment should return true for non-images when images_only is disabled' );
	}

	/**
	 * Test if should_process_attachment returns true for invalid attachment IDs
	 * if the option to process only images isn’t enabled
	 */
	public function test_should_process_attachment_invalid_id_without_images_only() {
		$result = Media_Type_Checker::should_process_attachment( 999999 );
		$this->assertTrue( $result, 'should_process_attachment should return true for invalid attachment IDs if Images-only isn’t enabled' );
	}

	/**
	 * Test if should_process_attachment returns false for invalid attachment IDs
	 * if the option to process only images is enabled
	 */
	public function test_should_process_attachment_invalid_id_with_images_only() {
		// Disable images_only option
		$options = \ISC\Plugin::get_options();
		$options['images_only'] = true;
		update_option( 'isc_options', $options );

		$result = Media_Type_Checker::should_process_attachment( 999999 );
		$this->assertFalse( $result, 'should_process_attachment should return false for invalid attachment IDs if Images-only is enabled' );
	}

	/**
	 * Test if should_process_attachment returns true for non-attachment post types
	 * if the option to process only images isn’t enabled
	 */
	public function test_should_process_attachment_non_attachment_without_images_only() {
		$post_id = self::factory()->post->create();
		$result = Media_Type_Checker::should_process_attachment( $post_id );

		$this->assertTrue( $result, 'should_process_attachment should return true for non-attachment post types if Images-only isn’t enabled' );
	}

	/**
	 * Test should_process_attachment accepts a WP_Post object for an image
	 */
	public function test_should_process_attachment_image_object_with_images_only() {
		$options = \ISC\Plugin::get_options();
		$options['images_only'] = true;
		update_option( 'isc_options', $options );

		$image_post = get_post( $this->image_id );
		$result = Media_Type_Checker::should_process_attachment( $image_post );

		$this->assertTrue( $result, 'should_process_attachment should return true for image post object when images_only is enabled' );
	}

	/**
	 * Test should_process_attachment accepts a WP_Post object for a non-image
	 */
	public function test_should_process_attachment_document_object_with_images_only() {
		$options = \ISC\Plugin::get_options();
		$options['images_only'] = true;
		update_option( 'isc_options', $options );

		$doc_post = get_post( $this->document_id );
		$result = Media_Type_Checker::should_process_attachment( $doc_post );

		$this->assertFalse( $result, 'should_process_attachment should return false for document post object when images_only is enabled' );
	}
} 