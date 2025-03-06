<?php

namespace ISC\Tests\WPUnit\Pro\Pblc;

use ISC\Image_Sources\Analyze_HTML;
use ISC\Image_Sources\Image_Sources;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test if ISC_Pro_Public:find_more_valid_image_urls() works as expected.
 * The method finds additional image URLs as a fallback to the base function.
 */
class Find_More_Valid_Image_URLs_Test extends WPTestCase {

	/**
	 * Helper to extract information from HTML
	 *
	 * @var Analyze_HTML
	 */
	public $html_analyzer;

	public function setUp() : void {
		parent::setUp();
		// Remove all hooked functions prevent accidental override.
		remove_all_actions('isc_public_caption_regex');
		remove_all_filters('isc_extract_images_from_html');

		// Add the Pro function to the filter.
		add_filter( 'isc_extract_images_from_html', [ 'ISC_Pro_Public', 'find_more_valid_image_urls' ], 10 );

		$this->html_analyzer = new Analyze_HTML();
	}

	/**
	 * Verify, that without the Pro function, ISC picks the value from the src attribute without checking if the URL is valid.
	 */
	public function test_wrong_image_url() {
		// remove the Pro function from the filter to reproduce the default behavior.
		remove_all_filters('isc_extract_images_from_html');

		$html     = '<img src="data:base" data="https://example.com/image.png">';
		$expected = [
			[
				'full'         => '<img src="data:base" data="https://example.com/image.png">',
				'figure_class' => '',
				'inner_code'   => '<img src="data:base" data="https://example.com/image.png">',
				'img_src'      => 'data:base',
			],
		];

		$result = $this->html_analyzer->extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'without find_more_valid_image_urls(), the invalid value from src is picked' );
	}

	/**
	 * Test if the function returns the valid image URL from the data attribute.
	 */
	public function test_datasrc_attribute() {
		$html     = '<img src="data:base" data="https://example.com/image.png">';
		$expected = [
			[
				'full'         => '<img src="data:base" data="https://example.com/image.png">',
				'figure_class' => '',
				'inner_code'   => '<img src="data:base" data="https://example.com/image.png">',
				'img_src'      => 'https://example.com/image.png',
			],
		];

		$result = $this->html_analyzer->extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'find_more_valid_image_urls should extract the valid image URL' );
	}

	/**
	 * Test if the function returns the first valid image URL.
	 */
	public function test_multiple_urls() {
		$html     = '<img src="data:base" data="https://example.com/first.png" data-src="https://example.com/second.png">';
		$expected = [
			[
				'full'         => '<img src="data:base" data="https://example.com/first.png" data-src="https://example.com/second.png">',
				'figure_class' => '',
				'inner_code'   => '<img src="data:base" data="https://example.com/first.png" data-src="https://example.com/second.png">',
				'img_src'      => 'https://example.com/first.png',
			],
		];

		$result = $this->html_analyzer->extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'find_more_valid_image_urls should extract the valid image URL' );
	}
}