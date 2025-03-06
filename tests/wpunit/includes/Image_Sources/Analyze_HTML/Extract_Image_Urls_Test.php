<?php

namespace ISC\Tests\WPUnit\Includes\Image_Sources\Analyze_HTML;

use ISC\Image_Sources\Analyze_HTML;
use ISC\Tests\WPUnit\WPTestCase;
use ISC\Image_Sources\Image_Sources;

/**
 * Test if ISC/Analyze_HTML:extract_image_urls() works as expected.
 */
class Extract_Image_Urls_Test extends WPTestCase {

	/**
	 * Helper to extract information from HTML
	 *
	 * @var Analyze_HTML
	 */
	public $html_analyzer;

	public function setUp() : void {
		parent::setUp();
		$this->html_analyzer = new Analyze_HTML();

		// Mock the allowed_extensions property in Image_Sources
		$image_sources = $this->getMockBuilder(Image_Sources::class)
		                      ->disableOriginalConstructor()
		                      ->getMock();
		$image_sources->allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

		// Use reflection to set the singleton instance
		$reflection = new \ReflectionClass(Image_Sources::class);
		$instance_property = $reflection->getProperty('instance');
		$instance_property->setAccessible(true);
		$instance_property->setValue(null, $image_sources);
	}

	/**
	 * Test if the function returns an array with empty input
	 */
	public function test_empty_input() {
		$html = '';
		$result = Analyze_HTML::extract_image_urls($html);
		$this->assertEquals([], $result, 'extract_image_urls should return an empty array for empty input');
		$this->assertIsArray($result, 'extract_image_urls should return an array');
	}

	/**
	 * Test with HTML containing no images
	 */
	public function test_no_images() {
		$html = '<p>This is a paragraph with no images.</p>';
		$result = Analyze_HTML::extract_image_urls($html);
		$this->assertEquals([], $result, 'extract_image_urls should return an empty array when no images are present');
	}

	/**
	 * Test with a simple image tag
	 */
	public function test_simple_image_tag() {
		$html = '<img src="https://example.com/image.jpg">';
		$expected = ['https://example.com/image.jpg'];
		$result = Analyze_HTML::extract_image_urls($html);
		$this->assertEquals($expected, $result, 'extract_image_urls did not extract the correct URL from a simple image tag');
	}

	/**
	 * Test with multiple image URLs
	 */
	public function test_multiple_image_urls() {
		$html = '<img src="https://example.com/image1.jpg"><img src="https://example.com/image2.png">';
		$expected = ['https://example.com/image1.jpg', 'https://example.com/image2.png'];
		$result = Analyze_HTML::extract_image_urls($html);
		$this->assertEquals($expected, $result, 'extract_image_urls did not extract all URLs from multiple image tags');
	}

	/**
	 * Test with image URLs in various HTML attributes
	 */
	public function test_urls_in_various_attributes() {
		$html = '
            <a href="https://example.com/image.jpg">Link to image</a>
            <div data-background="https://example.com/background.png"></div>
            <source srcset="https://example.com/image.webp">
        ';
		$expected = [
			'https://example.com/image.jpg',
			'https://example.com/background.png',
			'https://example.com/image.webp'
		];
		$result = Analyze_HTML::extract_image_urls($html);
		sort($expected);
		sort($result);
		$this->assertEquals($expected, $result, 'extract_image_urls should extract image URLs from various HTML attributes');
	}

	/**
	 * Test with image URLs in CSS with quotes
	 */
	public function test_urls_in_css_with_quotes() {
		$html = '<style>
            .background { background-image: url("https://example.com/background.jpg"); }
            .another { background: url("https://example.com/another.png") no-repeat; }
        </style>';
		$expected = [
			'https://example.com/background.jpg',
			'https://example.com/another.png'
		];
		$result = Analyze_HTML::extract_image_urls($html);
		sort($expected);
		sort($result);
		$this->assertEquals($expected, $result, 'extract_image_urls should extract image URLs from CSS with quotes');
	}

	/**
	 * Test with image URLs with query parameters
	 */
	public function test_urls_with_query_parameters() {
		$html = '<img src="https://example.com/image.jpg?width=300&height=200">';
		$expected = ['https://example.com/image.jpg?width=300&height=200'];
		$result = Analyze_HTML::extract_image_urls($html);
		$this->assertEquals($expected, $result, 'extract_image_urls should extract URLs with query parameters');
	}

	/**
	 * Test with URLs that don't have allowed extensions
	 */
	public function test_non_image_extensions() {
		$html = '<a href="https://example.com/document.pdf">Document</a>';
		$expected = [];
		$result = Analyze_HTML::extract_image_urls($html);
		$this->assertEquals($expected, $result, 'extract_image_urls should not extract URLs with non-image extensions');
	}

	/**
	 * Test with duplicate image URLs
	 */
	public function test_duplicate_urls() {
		$html = '<img src="https://example.com/image.jpg"><img src="https://example.com/image.jpg">';
		$expected = ['https://example.com/image.jpg'];
		$result = Analyze_HTML::extract_image_urls($html);
		$this->assertEquals($expected, $result, 'extract_image_urls should return unique URLs');
	}

	/**
	 * Test with URLs in srcset attribute
	 */
	public function test_srcset_attribute() {
		$html = '<img src="https://example.com/image.jpg" srcset="https://example.com/image-small.jpg 300w, https://example.com/image-large.jpg 1200w">';
		$expected = [
			'https://example.com/image.jpg',
			'https://example.com/image-small.jpg',
			'https://example.com/image-large.jpg'
		];
		$result = Analyze_HTML::extract_image_urls($html);
		sort($expected);
		sort($result);
		$this->assertEquals($expected, $result, 'extract_image_urls should extract URLs from srcset attribute');
	}

	/**
	 * Test with URLs in different formats (single quotes, double quotes)
	 */
	public function test_url_formats() {
		$html = '
            <img src="https://example.com/double-quotes.jpg">
            <img src=\'https://example.com/single-quotes.jpg\'>
        ';
		$expected = [
			'https://example.com/double-quotes.jpg',
			'https://example.com/single-quotes.jpg'
		];
		$result = Analyze_HTML::extract_image_urls($html);
		sort($expected);
		sort($result);
		$this->assertEquals($expected, $result, 'extract_image_urls should extract URLs in different quote formats');
	}

	/**
	 * Test with URLs wrapped in parentheses
	 */
	public function test_urls_in_parentheses() {
		$html = '<div style="background-image: url(https://example.com/in-parentheses.jpg)"></div>';
		$expected = ['https://example.com/in-parentheses.jpg'];
		$result = Analyze_HTML::extract_image_urls($html);
		$this->assertEquals($expected, $result, 'extract_image_urls should extract URLs wrapped in parentheses');
	}

	/**
	 * Test with URLs in CSS url() function with various formats
	 */
	public function test_urls_in_css_url_function() {
		$html = '<style>
            .bg1 { background: url(https://example.com/bg1.jpg); }
            .bg2 { background: url("https://example.com/bg2.png"); }
            .bg3 { background: url(\'https://example.com/bg3.gif\'); }
            .bg4 { background: url( https://example.com/bg4.webp ); }
        </style>';
		$expected = [
			'https://example.com/bg1.jpg',
			'https://example.com/bg2.png',
			'https://example.com/bg3.gif',
			'https://example.com/bg4.webp'
		];
		$result = Analyze_HTML::extract_image_urls($html);
		sort($expected);
		sort($result);
		$this->assertEquals($expected, $result, 'extract_image_urls should extract URLs from CSS url() function in various formats');
	}

	/**
	 * Test with relative URLs
	 */
	public function test_relative_urls() {
		$html = '<img src="/images/relative.jpg">';
		$expected = [];
		$result = Analyze_HTML::extract_image_urls($html);
		$this->assertEquals($expected, $result, 'extract_image_urls should not extract relative URLs');
	}

	/**
	 * Test with complex HTML structure
	 */
	public function test_complex_html() {
		$html = '
            <div class="container">
                <figure class="wp-block-image">
                    <a href="https://example.com">
                        <img src="https://example.com/figure-image.jpg" alt="Figure image">
                    </a>
                </figure>
                <p>Some text with <img src="https://example.com/inline-image.png"> inline image.</p>
                <div style="background-image: url(https://example.com/background.webp)"></div>
                <picture>
                    <source srcset="https://example.com/image.webp" type="image/webp">
                    <source srcset="https://example.com/image.jpg" type="image/jpeg">
                    <img src="https://example.com/fallback.jpg">
                </picture>
            </div>
        ';
		$expected = [
			'https://example.com/figure-image.jpg',
			'https://example.com/inline-image.png',
			'https://example.com/background.webp',
			'https://example.com/image.webp',
			'https://example.com/image.jpg',
			'https://example.com/fallback.jpg'
		];
		$result = Analyze_HTML::extract_image_urls($html);
		sort($expected);
		sort($result);
		$this->assertEquals($expected, $result, 'extract_image_urls did not extract all URLs from complex HTML');
	}

	/**
	 * Test with malformed HTML
	 */
	public function test_malformed_html() {
		$html = '<img src="https://example.com/image.jpg" <p>Broken HTML</p>';
		$expected = ['https://example.com/image.jpg'];
		$result = Analyze_HTML::extract_image_urls($html);
		$this->assertEquals($expected, $result, 'extract_image_urls should handle malformed HTML');
	}

	/**
	 * Test with data URLs
	 */
	public function test_data_urls() {
		$html = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==">';
		$expected = [];
		$result = Analyze_HTML::extract_image_urls($html);
		$this->assertEquals($expected, $result, 'extract_image_urls should not extract data URLs');
	}

	/**
	 * Test with URLs at the end of the string
	 */
	public function test_urls_at_end_of_string() {
		$html = 'This is a URL https://example.com/image.jpg';
		$expected = [];
		$result = Analyze_HTML::extract_image_urls($html);
		$this->assertEquals($expected, $result, 'extract_image_urls should not extract URLs at the end of the string');
	}

	/**
	 * Test with URLs in inline CSS
	 */
	public function test_urls_in_inline_css() {
		$html = '<div style="background: url(\'https://example.com/background.jpg\')"></div>';
		$expected = ['https://example.com/background.jpg'];
		$result = Analyze_HTML::extract_image_urls($html);
		$this->assertEquals($expected, $result, 'extract_image_urls should extract URLs from inline CSS');
	}

	/**
	 * Test with URLs in JavaScript
	 */
	public function test_urls_in_javascript() {
		$html = '<script>
            var imageUrl = "https://example.com/script-image.jpg";
            var anotherUrl = "https://example.com/another-image.png";
            function loadImage(url) {
                return new Image().src = url;
            }
            loadImage("https://example.com/loaded-image.gif");
        </script>';
		$expected = [
			'https://example.com/script-image.jpg',
			'https://example.com/another-image.png',
			'https://example.com/loaded-image.gif'
		];
		$result = Analyze_HTML::extract_image_urls($html);
		sort($expected);
		sort($result);
		$this->assertEquals($expected, $result, 'extract_image_urls should extract URLs from JavaScript');
	}
}