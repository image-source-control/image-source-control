<?php

namespace ISC\Tests\WPUnit\Pro\Pblc;

use ISC\Image_Sources\Analyze_HTML;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test if ISC_Pro_Public:find_more_valid_image_urls() works as expected.
 * The function is hooked into isc_extract_images_from_html filter and should only return something new if the img tag doesn’t have a valid URL in the src attribute.
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

	/**
	 * Test if the function handles invalid src in anchor correctly.
	 * It basically ignored the URL in the href attribute and returns the default value.
	 */
	public function test_invalid_src_in_anchor() {
		$html     = '<a href="https://example.com/actual-image.jpg"><img src="placeholder" alt="Invalid source in anchor"></a>';
		$expected = [
			[
				'full'         => '<a href="https://example.com/actual-image.jpg"><img src="placeholder" alt="Invalid source in anchor"></a>',
				'figure_class' => '',
				'inner_code'   => '<a href="https://example.com/actual-image.jpg"><img src="placeholder" alt="Invalid source in anchor"></a>',
				'img_src'      => 'placeholder',
			],
		];

		// We expect the URL to not be changed because it appears only in href
		$result = $this->html_analyzer->extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'find_more_valid_image_urls should not extract URL from href if it only appears there' );
	}

	/**
	 * Test URL that appears in both href and data-src.
	 */
	public function test_invalid_src_with_url_in_both_href_and_data_attribute() {
		$html     = '<a href="https://example.com/image.jpg"><img src="invalid" data-src="https://example.com/image.jpg" alt="Same URL in href and data-src"></a>';
		$expected = [
			[
				'full'         => '<a href="https://example.com/image.jpg"><img src="invalid" data-src="https://example.com/image.jpg" alt="Same URL in href and data-src"></a>',
				'figure_class' => '',
				'inner_code'   => '<a href="https://example.com/image.jpg"><img src="invalid" data-src="https://example.com/image.jpg" alt="Same URL in href and data-src"></a>',
				'img_src'      => 'https://example.com/image.jpg',
			],
		];

		$result = $this->html_analyzer->extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'find_more_valid_image_urls should extract URL that appears in both href and data attribute' );
	}

	/**
	 * Test multiple attributes with different image URLs.
	 */
	public function test_multiple_attributes_with_different_image_urls() {
		$html     = '<img src="invalid" data-src="https://example.com/image1.jpg" data-large="https://example.com/image2.jpg" data-thumb="https://example.com/thumb.jpg" alt="Multiple data attributes">';
		$expected = [
			[
				'full'         => '<img src="invalid" data-src="https://example.com/image1.jpg" data-large="https://example.com/image2.jpg" data-thumb="https://example.com/thumb.jpg" alt="Multiple data attributes">',
				'figure_class' => '',
				'inner_code'   => '<img src="invalid" data-src="https://example.com/image1.jpg" data-large="https://example.com/image2.jpg" data-thumb="https://example.com/thumb.jpg" alt="Multiple data attributes">',
				'img_src'      => 'https://example.com/image1.jpg',
			],
		];

		$result = $this->html_analyzer->extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'find_more_valid_image_urls should extract the first valid image URL from multiple attributes' );
	}

	/**
	 * Test that URL in href is ignored if it doesn't also appear elsewhere in the image markup.
	 */
	public function test_href_only_url_is_ignored() {
		$html = '<a href="https://example.com/image.jpg"><img src="scrambled" height="100" style="display: block; border: solid;"></a>';
		$expected = [
			[
				'full'         => '<a href="https://example.com/image.jpg"><img src="scrambled" height="100" style="display: block; border: solid;"></a>',
				'figure_class' => '',
				'inner_code'   => '<a href="https://example.com/image.jpg"><img src="scrambled" height="100" style="display: block; border: solid;"></a>',
				'img_src'      => 'scrambled', // URL should remain invalid
			],
		];

		$result = $this->html_analyzer->extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'find_more_valid_image_urls should not extract URL from href if it only appears there' );
	}

	/**
	 * Test that image with invalid src but valid srcset is handled correctly.
	 */
	public function test_invalid_src_with_valid_srcset() {
		$html = '<img src="invalid" srcset="https://example.com/image-small.jpg 300w, https://example.com/image-large.jpg 1000w" alt="Image with srcset">';
		$expected = [
			[
				'full'         => '<img src="invalid" srcset="https://example.com/image-small.jpg 300w, https://example.com/image-large.jpg 1000w" alt="Image with srcset">',
				'figure_class' => '',
				'inner_code'   => '<img src="invalid" srcset="https://example.com/image-small.jpg 300w, https://example.com/image-large.jpg 1000w" alt="Image with srcset">',
				'img_src'      => 'https://example.com/image-small.jpg',
			],
		];

		$result = $this->html_analyzer->extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'find_more_valid_image_urls should extract the first image URL from srcset' );
	}

	/**
	 * Test that the URL is taken from the image
	 */
	public function test_href_url_skipped() {
		$html = '<a href="https://example.com/image1.jpg"><img src="invalid" data-src="https://example.com/image2.jpg"></a>';
		$expected = [
			[
				'full'         => '<a href="https://example.com/image1.jpg"><img src="invalid" data-src="https://example.com/image2.jpg"></a>',
				'figure_class' => '',
				'inner_code'   => '<a href="https://example.com/image1.jpg"><img src="invalid" data-src="https://example.com/image2.jpg"></a>',
				'img_src'      => 'https://example.com/image2.jpg', // Should use the URL from the IMG tag not from the href attribute.
			],
		];

		$result = $this->html_analyzer->extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'find_more_valid_image_urls should skip the URL in the href attribute.' );
	}

	/**
	 * Test that URL is used if it appears both in href and another attribute.
	 */
	public function test_url_used_when_in_both_href_and_attribute() {
		$html = '<a href="https://example.com/image1.jpg"><img src="invalid" data-one="https://example.com/image1.jpg" data-two="https://example.com/image2.jpg" alt="First URL should be used"></a>';
		$expected = [
			[
				'full'         => '<a href="https://example.com/image1.jpg"><img src="invalid" data-one="https://example.com/image1.jpg" data-two="https://example.com/image2.jpg" alt="First URL should be used"></a>',
				'figure_class' => '',
				'inner_code'   => '<a href="https://example.com/image1.jpg"><img src="invalid" data-one="https://example.com/image1.jpg" data-two="https://example.com/image2.jpg" alt="First URL should be used"></a>',
				'img_src'      => 'https://example.com/image1.jpg', // Should use the first URL since it appears in both href and data-one
			],
		];

		$result = $this->html_analyzer->extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'find_more_valid_image_urls should use URL that appears in both href and other attributes' );
	}
}