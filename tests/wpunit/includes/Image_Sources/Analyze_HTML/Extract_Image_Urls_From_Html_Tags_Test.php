<?php

namespace ISC\Tests\WPUnit\Includes\Image_Sources\Analyze_HTML;

use ISC\Image_Sources\Analyze_HTML;
use ISC\Tests\WPUnit\WPTestCase;
use ISC\Image_Sources\Image_Sources;

/**
 * Test if ISC/Analyze_HTML:extract_image_urls_from_html_tags() works as expected.
 */
class Extract_Image_Urls_From_Html_Tags_Test extends WPTestCase {

	/**
	 * Helper to extract information from HTML
	 *
	 * @var Analyze_HTML
	 */
	public $html_analyzer;

	public function setUp(): void {
		parent::setUp();
		$this->html_analyzer = new Analyze_HTML();

		// Mock the allowed_extensions property in Image_Sources
		$image_sources                     = $this->getMockBuilder( Image_Sources::class )
		                                          ->disableOriginalConstructor()
		                                          ->getMock();
		$image_sources->allowed_extensions = [ 'jpg', 'jpeg', 'png', 'gif', 'webp' ];

		// Use reflection to set the singleton instance
		$reflection        = new \ReflectionClass( Image_Sources::class );
		$instance_property = $reflection->getProperty( 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( null, $image_sources );
	}

	/**
	 * Test if the function returns an array with empty input
	 */
	public function test_empty_input() {
		$html   = '';
		$result = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( [], $result, 'extract_image_urls_from_html_tags should return an empty array for empty input' );
		$this->assertIsArray( $result, 'extract_image_urls_from_html_tags should return an array' );
	}

	/**
	 * Test with HTML containing no images
	 */
	public function test_no_images() {
		$html   = '<p>This is a paragraph with no images.</p>';
		$result = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( [], $result, 'extract_image_urls_from_html_tags should return an empty array when no images are present' );
	}

	/**
	 * Test with a simple image tag
	 */
	public function test_simple_image_tag() {
		$html     = '<img src="https://example.com/image.jpg">';
		$expected = [ 'https://example.com/image.jpg' ];
		$result   = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( $expected, $result, 'extract_image_urls_from_html_tags did not extract the correct URL from a simple image tag' );
	}

	/**
	 * Test with multiple image tags
	 */
	public function test_multiple_image_tags() {
		$html     = '<img src="https://example.com/image1.jpg"><img src="https://example.com/image2.png">';
		$expected = [ 'https://example.com/image1.jpg', 'https://example.com/image2.png' ];
		$result   = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( $expected, $result, 'extract_image_urls_from_html_tags did not extract all URLs from multiple image tags' );
	}

	/**
	 * Test with image tags and other URLs in the HTML
	 */
	public function test_mixed_content() {
		$html     = '<p>Text with a <a href="https://example.com">link</a> and an <img src="https://example.com/image.jpg"> image.</p>';
		$expected = [ 'https://example.com/image.jpg' ];
		$result   = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( $expected, $result, 'extract_image_urls_from_html_tags should only extract image URLs' );
	}

	/**
	 * Test with image URLs in CSS background
	 */
	public function test_css_background_image() {
		$html     = '<div style="background-image: url(https://example.com/background.jpg)"></div>';
		$expected = [ 'https://example.com/background.jpg' ];
		$result   = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( $expected, $result, 'extract_image_urls_from_html_tags should extract URLs from CSS background images' );
	}

	/**
	 * Test with image URLs in src and srcset attribute
	 */
	public function test_src_and_srcset_attribute() {
		$html     = '<img src="https://example.com/image.jpg" srcset="https://example.com/image-small.jpg 300w, https://example.com/image-large.jpg 1200w">';
		$expected = [ 'https://example.com/image.jpg' ];
		$result   = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( $expected, $result, 'extract_image_urls_from_html_tags should not extract URLs from srcset attribute if they follow a valid src attribute' );
	}

	/**
	 * Test with image URLs in srcset attribute
	 * Return the first URL
	 */
	public function test_srcset_attribute() {
		$html     = '<img srcset="https://example.com/image-small.jpg 300w, https://example.com/image-large.jpg 1200w">';
		$expected = [ 'https://example.com/image-small.jpg' ];
		$result   = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( $expected, $result, 'extract_image_urls_from_html_tags should extract the first URL from srcset attribute' );
	}

	/**
	 * Test with image URLs with query parameters
	 */
	public function test_urls_with_query_parameters() {
		$html     = '<img src="https://example.com/image.jpg?width=300&height=200">';
		$expected = [ 'https://example.com/image.jpg?width=300&height=200' ];
		$result   = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( $expected, $result, 'extract_image_urls_from_html_tags should extract URLs with query parameters' );
	}

	/**
	 * Test with image URLs with no file extension
	 */
	public function test_urls_without_extension() {
		$html     = '<img src="https://example.com/image">';
		$expected = [ 'https://example.com/image' ];
		$result   = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( $expected, $result, 'extract_image_urls_from_html_tags should extract URLs without file extensions from img tags' );
	}

	/**
	 * Test with duplicate image URLs
	 */
	public function test_duplicate_urls() {
		$html     = '<img src="https://example.com/image.jpg"><img src="https://example.com/image.jpg">';
		$expected = [ 'https://example.com/image.jpg' ];
		$result   = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( $expected, $result, 'extract_image_urls_from_html_tags should return unique URLs' );
	}

	/**
	 * Test with complex HTML structure
	 */
	public function test_complex_html() {
		$html     = '
            <div class="container">
                <figure class="wp-block-image">
                    <a href="https://example.com">
                        <img src="https://example.com/figure-image.jpg" alt="Figure image">
                    </a>
                </figure>
                <p>Some text with <img src="https://example.com/inline-image.png"> inline image.</p>
                <div style="background-image: url(https://example.com/background.webp)"></div>
            </div>
        ';
		$expected = [
			'https://example.com/figure-image.jpg',
			'https://example.com/inline-image.png',
			'https://example.com/background.webp'
		];
		$result   = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		sort( $expected );
		sort( $result );
		$this->assertEquals( $expected, $result, 'extract_image_urls_from_html_tags did not extract all URLs from complex HTML' );
	}

	/**
	 * Test with relative URLs
	 */
	public function test_relative_urls() {
		$html     = '<img src="/images/relative.jpg">';
		$expected = ['/images/relative.jpg'];
		$result   = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( $expected, $result, 'extract_image_urls_from_html_tags should not extract relative URLs' );
	}

	/**
	 * Test with malformed HTML
	 */
	public function test_malformed_html() {
		$html     = '<img src="https://example.com/image.jpg" <p>Broken HTML</p>';
		$expected = [ 'https://example.com/image.jpg' ];
		$result   = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( $expected, $result, 'extract_image_urls_from_html_tags should handle malformed HTML' );
	}

	/**
	 * Test with URLs that don't have allowed extensions. They are accepted in src attributes.
	 */
	public function test_non_image_extensions_in_src() {
		$html     = '<img src="https://example.com/document.pdf">';
		$expected = ['https://example.com/document.pdf'];
		$result   = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( $expected, $result, 'extract_image_urls_from_html_tags should not extract URLs with non-image extensions' );
	}

	/**
	 * Test with URLs that don't have allowed extensions. They are not accepted outside of src attributes.
	 */
	public function test_non_image_extensions_outside_src() {
		$html     = '<img data-some="https://example.com/document.pdf">';
		$expected = [];
		$result   = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( $expected, $result, 'extract_image_urls_from_html_tags should not extract URLs with non-image extensions' );
	}

	/**
	 * Test with invalid URLs in the src attribute.
	 * This is a known limitation and fixed in Pro.
	 */
	public function test_invalid_url_in_src() {
		$html     = '<img src="data:base" src="https://example.com/image.png">';
		$expected = ['data:base'];
		$result   = Analyze_HTML::extract_image_urls_from_html_tags( $html );
		$this->assertEquals( $expected, $result, 'extract_image_urls_from_html_tags should not extract URLs with non-image extensions' );
	}

	/**
	 * Test extracting image URLs from a nested HTML structure with various attributes.
	 *
	 * This tests a structure like: div[data-thumb] > a[href] > img[src][data-src][data-large_image]
	 * It expects to find URLs from:
	 * - The `src` attribute of the `img` tag.
	 * - Attributes outside the `img` tag (like `data-thumb` and `href`) if their value ends with an allowed extension.
	 * It should *not* find URLs from other data attributes within the `img` tag (`data-src`, `data-large_image`)
	 * when the `src` attribute is present, due to the regex matching behavior.
	 */
	public function test_extract_urls_from_nested_html_with_various_attributes() {
		$html = '
        <div 
          data-thumb="http://example.com/thumb.png" 
          class="image-container" 
        >
          <a href="http://example.com/link-to-large.png">
            <img 
              src="http://example.com/main-image.png" 
              data-src="http://example.com/data-src-image.png"
              data-large_image="http://example.com/large-image.png"
              alt="Test Image" 
            />
          </a>
        </div>
        ';
		// URLs expected based on the function's actual behavior:
		// - href found by general URL pattern (ends in .png)
		// - src found by specific img tag pattern
		// - data-thumb found by general URL pattern (ends in .png)
		$expected = [
			'http://example.com/link-to-large.png', // from href
			'http://example.com/main-image.png',    // from src
			'http://example.com/thumb.png',         // from data-thumb
		];
		$result = Analyze_HTML::extract_image_urls_from_html_tags( $html );

		// Sort arrays to ensure order doesn't affect comparison as extraction order might vary
		sort( $expected );
		sort( $result );

		$this->assertEquals( $expected, $result, 'Failed to extract expected URLs from nested HTML with various attributes' );
	}
}