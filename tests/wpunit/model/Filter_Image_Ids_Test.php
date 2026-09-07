<?php

namespace ISC\Tests\WPUnit\Model;

use ISC\Tests\WPUnit\WPTestCase;
use ISC_Model;

/**
 * Test if ISC_Model::filter_image_ids() works as expected.
 */
class Filter_Image_Ids_Test extends WPTestCase {

	/**
	 * Attachment ID used for testing.
	 *
	 * @var int
	 */
	public $attachment_id = 0;

	/**
	 * UTF-8 image URL used for testing.
	 *
	 * @var string
	 */
	public $utf8_url = '';

	public function setUp(): void {
		parent::setUp();

		$this->utf8_url = home_url( '/wp-content/uploads/2022/12/Bäume-mit-Umlaut-scaled.jpg' );
		$this->attachment_id = wp_insert_attachment( [
			'guid'      => $this->utf8_url,
			'post_type' => 'attachment',
		] );
	}

	public function tearDown(): void {
		parent::tearDown();

		if ( $this->attachment_id ) {
			wp_delete_post( $this->attachment_id, true );
		}
	}

	/**
	 * Test that filter_image_ids() preserves UTF-8 image URLs when parsing HTML fragments.
	 */
	public function test_filter_image_ids_preserves_utf8_image_urls() {
		$content = '<p><img src="' . $this->utf8_url . '"></p>';

		$result = ISC_Model::filter_image_ids( $content );

		$this->assertSame(
			[
				$this->attachment_id => $this->utf8_url,
			],
			$result,
			'filter_image_ids() should preserve UTF-8 characters in image URLs.'
		);
	}
}
