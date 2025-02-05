<?php

namespace ISC\Tests\WPUnit\Pro\Pblc\Caption;

use ISC\Tests\WPUnit\WPTestCase;
use ISC_Public;
use ISC\Options;

/**
 * Test the isc_overlay_html_source filter hook with pro caption layouts
 */
class Overlay_HTML_Source_Caption_Markup_Test extends WPTestCase {

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
	 * Test the filter in ISC\Pro\Caption::render_caption_style() for the hover caption layout
	 */
	public function test_render_caption_hover_style() {
		// test the baseline without the filter
		$html     = '<img src="https://example.com/image-one.jpg" alt="Image" />';
		$expected = '<span id="isc_attachment_' . $this->image_id . '" class="isc-source "><img src="https://example.com/image-one.jpg" alt="Image" /><span class="isc-source-text">Source: Author A</span></span>';
		$result   = $this->isc_public->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result );

		// switch to hover style
		$options = Options::default_options();
		$options['caption_style'] = 'hover';
		update_option( 'isc_options', $options );

		$expected = '<span id="isc_attachment_' . $this->image_id . '" class="isc-source "><img src="https://example.com/image-one.jpg" alt="Image" /><span class="isc-source-text"><span class="isc-source-text-icon">Source:</span><span>Author A</span></span></span>';
		$result   = $this->isc_public->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result, 'The isc_overlay_html_source filter ran in add_source_captions_to_content()' );
	}

	/**
	 * Test the filter in ISC\Pro\Caption::render_caption_style() for the click caption layout
	 */
	public function test_render_caption_click_style() {
		// test the baseline without the filter
		$html     = '<img src="https://example.com/image-one.jpg" alt="Image" />';
		$expected = '<span id="isc_attachment_' . $this->image_id . '" class="isc-source "><img src="https://example.com/image-one.jpg" alt="Image" /><span class="isc-source-text">Source: Author A</span></span>';
		$result   = $this->isc_public->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result );

		// switch to hover style
		$options = Options::default_options();
		$options['caption_style'] = 'click';
		update_option( 'isc_options', $options );

		$expected = '<span id="isc_attachment_' . $this->image_id . '" class="isc-source "><img src="https://example.com/image-one.jpg" alt="Image" /><details class="isc-source-text"><summary>Source:</summary>Author A</details></span>';
		$result   = $this->isc_public->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result, 'The isc_overlay_html_source filter ran in add_source_captions_to_content()' );
	}
}