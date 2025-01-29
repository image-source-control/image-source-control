<?php

namespace ISC\Tests\WPUnit\Model;

use \ISC\Tests\WPUnit\WPTestCase;
use ISC_Model;

/**
 * Test if ISC_Model::get_attachments_with_empty_sources() works as expected.
 */
class Get_Attachments_With_Empty_Sources_Test extends WPTestCase {

	private $attachment_ids = [];

	public function setUp(): void {
		parent::setUp();
		// Remove all attachments to start clean
		$this->removeAllAttachments();
	}

	public function tearDown(): void {
		// Clean up all attachments created during the tests
		$this->removeAllAttachments();
		parent::tearDown();
	}

	private function removeAllAttachments() {
		foreach ( $this->attachment_ids as $id ) {
			wp_delete_attachment( $id, true );
		}
		$this->attachment_ids = [];
	}

	private function createAttachmentWithMeta( $meta_key, $meta_value ) {
		// Create an attachment
		$attachment_id = self::factory()->attachment->create();
		// Add specified meta data to the attachment
		update_post_meta( $attachment_id, $meta_key, $meta_value );
		$this->attachment_ids[] = $attachment_id;

		return $attachment_id;
	}

	public function testWithNoAttachments() {
		$attachments = ISC_Model::get_attachments_with_empty_sources();
		$this->assertEmpty( $attachments, 'Expected no attachments but some were found.' );
	}

	public function testWithAttachmentsNoneMatch() {
		// Create an attachment that does not match the criteria
		$this->createAttachmentWithMeta( 'isc_image_source', 'some_value' );
		$attachments = ISC_Model::get_attachments_with_empty_sources();
		$this->assertEmpty( $attachments, 'Expected no attachments to match criteria but some did.' );
	}

	public function testWithAttachmentsEmptySourceNotOwn() {
		// Create an attachment that matches the first criteria
		$this->createAttachmentWithMeta( 'isc_image_source', '' );
		update_post_meta( $this->attachment_ids[0], 'isc_image_source_own', '0' ); // Explicitly setting to '0'
		$attachments = ISC_Model::get_attachments_with_empty_sources();
		$this->assertNotEmpty( $attachments, 'Expected some attachments to match criteria but none did.' );
	}

	public function testWithAttachmentsMissingMetaKey() {
		// Create an attachment without isc_image_source meta key
		$attachment_id          = self::factory()->attachment->create();
		$this->attachment_ids[] = $attachment_id;
		$attachments            = ISC_Model::get_attachments_with_empty_sources();
		$this->assertNotEmpty( $attachments, 'Expected some attachments to match criteria (missing isc_image_source) but none did.' );
	}

	/**
	 * Attachment with 'isc_image_source_own' set to '1' and no other value are not counted as empty sources.
	 *
	 * @return void
	 */
	public function testWithAttachmentsOwnSetToOne() {
		$this->createAttachmentWithMeta( 'isc_image_source_own', '1' );
		$attachments = ISC_Model::get_attachments_with_empty_sources();

		// Assert that the attachment is NOT in the results
		$this->assertEmpty( $attachments, 'Expected no attachments to match criteria but some did.' );
	}

	/**
	 * Attachment with 'isc_image_source_own' set to non-standard values are counted as empty sources.
	 *
	 * @return void
	 */
	public function testWithAttachmentsOwnSetToNonStandardValues() {
		$non_standard_values = [ '2', 'yes', 'no', 'true', 'false', 'null', 'NaN' ];

		foreach ( $non_standard_values as $value ) {
			$this->createAttachmentWithMeta( 'isc_image_source_own', $value );
			$attachments = ISC_Model::get_attachments_with_empty_sources();
		}
		// Assert that there are 7 attachments in the results
		$this->assertCount( 7, $attachments, 'Expected 7 attachments to match criteria but ' . count( $attachments ) . ' did.' );
	}
}