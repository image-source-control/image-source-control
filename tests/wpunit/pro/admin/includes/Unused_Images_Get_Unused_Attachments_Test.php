<?php

namespace ISC\Tests\WPUnit\Pro\Admin\Includes;

use ISC\Pro\Unused_Images;
use ISC\Tests\WPUnit\Includes\Unused_Images_Basic_Test;

/**
 * Testing \ISC\Pro\Unused_Images::get_unused_attachments()
 */
class Unused_Images_Get_Unused_Attachments_Test extends Unused_Images_Basic_Test {

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
}