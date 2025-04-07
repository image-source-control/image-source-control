<?php

namespace ISC\Tests\WPUnit\Pro\Admin\Includes;

use ISC\Pro\Unused_Images;
use ISC\Tests\WPUnit\Includes\Unused_Images_Basic_Test;

/**
 * Testing \ISC\Pro\Unused_Images::get_unused_attachments()
 */
class Unused_Images_Get_Unused_Attachments_Test extends Unused_Images_Basic_Test {

	/**
	 * Set the images_only setting.
	 *
	 * @param bool $enabled
	 */
	private function setImagesOnly( bool $enabled ): void {
		$options = \ISC\Plugin::get_options();
		$options['images_only'] = $enabled;
		update_option( 'isc_options', $options );
	}

	/**
	 * Create an attachment with a specific mime type.
	 *
	 * @param string $mime
	 * @return int
	 */
	private function createAttachmentWithMime( string $mime ): int {
		$att_id = $this->factory->attachment->create( [
			                                              'post_mime_type' => $mime,
			                                              'post_type'      => 'attachment',
		                                              ] );

		$this->attachment_ids[] = $att_id;
		return $att_id;
	}

	/**
	 * Test with the filter set to "unchecked"
	 */
	public function test_filter_unchecked() {
		parent::setUpAttachments();

		// get the attachments without a filter
		$unused_attachments = Unused_Images::get_unused_attachments();

		// check if the attachments are correct
		$this->assertCount( 3, $unused_attachments );

		// setting the post meta data means that the image was already checked
		add_post_meta( $this->attachment_ids[0], 'isc_possible_usages', '', true );

		$unused_attachments = Unused_Images::get_unused_attachments( [ 'filter' => 'unchecked' ] );

		// check if the attachments are correct
		$this->assertCount( 2, $unused_attachments );

		delete_post_meta( $this->attachment_ids[0], 'isc_possible_usages' );
	}

	/**
	 * Test with the filter set to "unused"
	 */
	public function test_filter_unused() {
		parent::setUpAttachments();

		// get the attachments without a filter
		$unused_attachments = Unused_Images::get_unused_attachments();

		// check if the attachments are correct
		$this->assertCount( 3, $unused_attachments );

		add_post_meta( $this->attachment_ids[0], 'isc_possible_usages', [], true );

		$unused_attachments = Unused_Images::get_unused_attachments( [ 'filter' => 'unused' ] );

		// only one attachment should match
		$this->assertCount( 1, $unused_attachments );
	}

	/**
	 * Test: when images_only is disabled, non-image attachments are included.
	 */
	public function test_images_only_disabled_includes_all_mime_types() {
		$this->setImagesOnly( false );

		// Create one image and one non-image attachment
		$image_id = $this->createAttachmentWithMime( 'image/png' );
		$doc_id   = $this->createAttachmentWithMime( 'application/pdf' );

		$results = Unused_Images::get_unused_attachments();
		$ids     = wp_list_pluck( $results, 'ID' );

		$this->assertContains( $image_id, $ids, 'Expected image to be in unused attachments' );
		$this->assertContains( $doc_id, $ids, 'Expected document to be in unused attachments' );
	}

	/**
	 * Test: when images_only is enabled, non-image attachments are excluded.
	 */
	public function test_images_only_enabled_excludes_non_images() {
		$this->setImagesOnly( true );

		// Create one image and one non-image attachment
		$image_id = $this->createAttachmentWithMime( 'image/jpeg' );
		$doc_id   = $this->createAttachmentWithMime( 'application/pdf' );

		$results = Unused_Images::get_unused_attachments();
		$ids     = wp_list_pluck( $results, 'ID' );

		$this->assertContains( $image_id, $ids, 'Expected image to be in unused attachments' );
		$this->assertNotContains( $doc_id, $ids, 'Expected non-image to be excluded when images_only is enabled' );
	}

	/**
	 * Test: images_only enabled + filter "unchecked".
	 */
	public function test_images_only_enabled_with_filter_unchecked() {
		$this->setImagesOnly( true );

		// Create one image and one non-image
		$image_id = $this->createAttachmentWithMime( 'image/webp' );
		$doc_id   = $this->createAttachmentWithMime( 'application/pdf' );

		// Mark both as already checked
		add_post_meta( $doc_id, 'isc_possible_usages', '' );
		add_post_meta( $image_id, 'isc_possible_usages', '' );

		// Should return nothing because both are considered checked
		$results = Unused_Images::get_unused_attachments( [ 'filter' => 'unchecked' ] );
		$this->assertEmpty( $results, 'No attachments should match unchecked if all were checked' );

		delete_post_meta( $doc_id, 'isc_possible_usages' );
		delete_post_meta( $image_id, 'isc_possible_usages' );
	}
}
