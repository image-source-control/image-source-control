<?php

namespace ISC\Tests\WPUnit\Pblc\Captions;

use ISC\Tests\WPUnit\WPTestCase;
use \ISC_Public;

/**
 * Test if ISC_Public::add_source_captions_to_content() splits the content correctly when `isc_stop_overlay` exists
 */
class Stop_Overlay_Test extends WPTestCase {
	/**
	 * @var ISC_Public
	 */
	protected $isc_public;

	public function setUp(): void {
		parent::setUp();
		$this->isc_public = new ISC_Public();
	}

	/**
	 * Test if ISC_Public::add_source_captions_to_content() splits the content correctly when `isc_stop_overlay` exists
	 * No image given in the HTML
	 */
	public function test_split_content_without_image() {
		$html     = '<p>Some text</p><div class="isc_stop_overlay"></div><p>Some more text</p>';
		$expected = '<p>Some text</p><div class=""></div><p>Some more text</p>';
		$result   = ISC_Public::get_instance()->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result, 'Content with isc_stop_overlay but without images was not combined correctly' );
	}

	/**
	 * Test if ISC_Public::add_source_captions_to_content() splits the content correctly when `isc_stop_overlay` exists
	 * One image given in the HTML
	 */
	public function test_split_content_with_image() {
		$html     = '<p>Some text</p><img src="https://example.com/image.jpg" alt="Image" /><div class="isc_stop_overlay"></div><p>Some more text</p>';
		$expected = '<p>Some text</p><img src="https://example.com/image.jpg" alt="Image" /><div class=""></div><p>Some more text</p>';
		$result   = ISC_Public::get_instance()->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result, 'Content with isc_stop_overlay and one image was not combined correctly' );
	}
}
