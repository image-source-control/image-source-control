<?php

namespace ISC\Tests\WPUnit\Pro\Filters;

use ISC\Tests\WPUnit\WPTestCase;
use ISC_Pro_Public;
use ISC\Options;

/**
 * Test the isc_overlay_html_source filter hook in Pro-related functions
 */
class Overlay_HTML_Source_Pro_Test extends WPTestCase {

	/**
	 * @var ISC_Pro_Public
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

		$this->isc_public = new ISC_Pro_Public();
	}

	/**
	 * Test the filter in ISC_Pro_Public::add_caption_from_isc_images_attribute()
	 */
	public function test_add_caption_from_isc_images_attribute() {
		// test the baseline without the filter
		$html     = '<div data-isc-images="' . $this->image_id . '"></div>';
		$expected = '<div data-isc-images="' . $this->image_id . '"><span class="isc-source-text">Source: Author A</span></div>';
		$result   = $this->isc_public->add_caption_from_isc_images_attribute( $html );
		$this->assertEquals( $expected, $result );

		// replace "span" with "div" the caption HTML
		add_filter( 'isc_overlay_html_source', function( $source ) {
			return str_replace( 'span', 'div', $source );
		} );

		$expected = '<div data-isc-images="' . $this->image_id . '"><div class="isc-source-text">Source: Author A</div></div>';
		$result   = $this->isc_public->add_caption_from_isc_images_attribute( $html );
		$this->assertEquals( $expected, $result, 'The isc_overlay_html_source filter ran in add_caption_from_isc_images_attribute()' );
	}

	/**
	 * Test the filter in ISC_Pro_Public::add_captions_to_style_blocks()
	 */
	public function test_add_captions_to_style_blocks() {
		// enable the option to display the caption in the style blocks
		$options = Options::get_options();
		$options['overlay_included_images'] = 'body_img';
		$options['overlay_included_advanced'][] = 'style_block_show';
		update_option( 'isc_options', $options );

		// test the baseline without the filter
		$html     = '<style>div { background-image: url("https://example.com/image-one.jpg"); }</style>';
		$expected = '<style>div { background-image: url("https://example.com/image-one.jpg"); }</style><span class="isc-source-text">Source: Author A</span>';
		$result   = $this->isc_public->add_captions_to_style_blocks( $html );
		$this->assertEquals( $expected, $result, 'The caption was missing in the style block');

		// replace "div" with "span" the caption HTML
		add_filter( 'isc_overlay_html_source', function( $source ) {
			return str_replace( 'span', 'div', $source );
		} );

		$expected = '<style>div { background-image: url("https://example.com/image-one.jpg"); }</style><div class="isc-source-text">Source: Author A</div>';
		$result   = $this->isc_public->add_captions_to_style_blocks( $html );
		$this->assertEquals( $expected, $result, 'The isc_overlay_html_source filter ran in add_captions_to_style_blocks()' );
	}
}