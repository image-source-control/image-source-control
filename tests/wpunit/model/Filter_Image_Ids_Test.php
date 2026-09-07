<?php

namespace ISC\Tests\WPUnit\Model;

use ISC\Tests\WPUnit\WPTestCase;
use ISC_Model;

/**
 * Test if ISC_Model::filter_image_ids() works as expected.
 */
class Filter_Image_Ids_Test extends WPTestCase {

	/**
	 * Attachment IDs used for testing.
	 *
	 * @var int[]
	 */
	public $attachment_ids = [];

	/**
	 * UTF-8 image URLs used for testing.
	 *
	 * @var string[]
	 */
	public $utf8_urls = [];

	public function setUp(): void {
		parent::setUp();

		$this->utf8_urls = [
			'umlaut' => home_url( '/wp-content/uploads/2022/12/Bäume-mit-Umlaut-scaled.jpg' ),
			'russian' => home_url( '/wp-content/uploads/2022/12/Привет-мир-scaled.jpg' ),
			'japanese' => home_url( '/wp-content/uploads/2022/12/東京-写真-scaled.jpg' ),
		];

		foreach ( $this->utf8_urls as $url ) {
			$this->attachment_ids[] = wp_insert_attachment( [
				'guid'      => $url,
				'post_type' => 'attachment',
			] );
		}
	}

	public function tearDown(): void {
		parent::tearDown();

		foreach ( $this->attachment_ids as $attachment_id ) {
			wp_delete_post( $attachment_id, true );
		}
	}

	/**
	 * Test that filter_image_ids() preserves UTF-8 image URLs when parsing HTML fragments.
	 */
	public function test_filter_image_ids_preserves_utf8_image_urls() {
		foreach ( array_values( $this->utf8_urls ) as $index => $url ) {
			$content = '<p><img src="' . $url . '"></p>';
			$result  = ISC_Model::filter_image_ids( $content );

			$this->assertSame(
				[
					$this->attachment_ids[ $index ] => $url,
				],
				$result,
				'filter_image_ids() should preserve UTF-8 characters in image URLs.'
			);
		}
	}
}
