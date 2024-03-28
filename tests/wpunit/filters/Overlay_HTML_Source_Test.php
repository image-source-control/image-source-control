<?php

namespace ISC\Tests\WPUnit;

use \ISC_Public;

/**
 * Test the isc_overlay_html_source filter hook
 */
class Overlay_HTML_Source__Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var ISC_Public
	 */
	protected $isc_public;

	/**
	 * Image ID
	 *
	 * @var int
	 */
	private $image_id;

	public function setUp(): void {
		parent::setUp();

		$this->image_id = $this->factory()->post->create( [
			                                                  'post_title' => 'Image One',
			                                                  'post_type'  => 'attachment',
			                                                  'guid'       => 'https://example.com/image-one.jpg',
		                                                  ] );

		add_post_meta( $this->image_id, 'isc_image_source', 'Author A' );

		$this->isc_public = new ISC_Public();
	}

	/**
	 * Test the filter in ISC_Public::add_source_captions_to_content()
	 */
	public function test_add_source_captions_to_content() {
		// test the baseline without the filter
		$html     = '<img src="https://example.com/image-one.jpg" alt="Image" />';
		$expected = '<span id="isc_attachment_' . $this->image_id . '" class="isc-source "><img src="https://example.com/image-one.jpg" alt="Image" /><span class="isc-source-text">Source: Author A</span></span>';
		$result   = $this->isc_public->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result );

		// replace "span" with "div" the caption HTML
		add_filter( 'isc_overlay_html_source', function( $source ) {
			return str_replace( 'span', 'div', $source );
		} );

		$expected = '<span id="isc_attachment_' . $this->image_id . '" class="isc-source "><img src="https://example.com/image-one.jpg" alt="Image" /><div class="isc-source-text">Source: Author A</div></span>';
		$result   = $this->isc_public->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result, 'The isc_overlay_html_source filter ran in add_source_captions_to_content()' );
	}
}