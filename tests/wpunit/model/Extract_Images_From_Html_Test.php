<?php

namespace ISC\Tests\WPUnit;

use \ISC_Model;

/**
 * Test if ISC_Model::extract_images_from_html() works as expected.
 */
class Extract_Images_From_Html_Test extends \Codeception\TestCase\WPTestCase {

	public function setUp() : void {
		parent::setUp();
		// Remove all hooked functions from 'isc_public_caption_regex' action hook to prevent accidental override.
		remove_all_actions('isc_public_caption_regex');
		// Remove all hooked functions from 'isc_extract_images_from_html' filter hook to prevent accidental override.
		remove_all_filters('isc_extract_images_from_html');
	}

	/**
	 * Test if the function returns an array
	 * Whatever is entered, it is always an array
	 */
	public function test_array() {
		$html   = 'some random string';
		$result = ISC_Model::extract_images_from_html( $html );
		$this->assertEquals( [], $result, 'extract_images_from_html did not return an empty array' );
		$this->assertIsArray( $result, 'extract_images_from_html did not return an array' );
	}

	/**
	 * Extract information from a simple image tag
	 * - 0 full HTML
	 * - 3 img tag
	 * - 8 image URL
	 */
	public function test_extract_image() {
		$html     = '<img src="https://example.com/test.jpg">';
		$expected = [
			[
				'full'         => '<img src="https://example.com/test.jpg">',
				'figure_class' => '',
				'inner_code'   => '<img src="https://example.com/test.jpg">',
				'img_src'      => 'https://example.com/test.jpg',
			],
		];
		$result   = ISC_Model::extract_images_from_html( $html );
		// fwrite( STDERR, print_r( $result, TRUE ) );
		$this->assertEquals( $expected, $result, 'extract_images_from_html did not return the correct image information' );
	}

	/**
	 * Test image src URL not having a file extension
	 */
	public function test_image_src_without_extension() {
		$html     = '<img src="https://example.com/test">';
		$expected = [
			[
				'full'         => '<img src="https://example.com/test">',
				'figure_class' => '',
				'inner_code'   => '<img src="https://example.com/test">',
				'img_src'      => 'https://example.com/test',
			],
		];
		$result   = ISC_Model::extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'extract_images_from_html did not return the correct image information' );
	}

	/**
	 * Test an unquoted src attribute
	 * Missing or single quotes are not supported.
	 */
	public function test_unquoted_src_attribute() {
		$html     = '<img src=https://example.com/test>';
		$expected = [];
		$result   = ISC_Model::extract_images_from_html( $html );
		file_put_contents( WP_CONTENT_DIR . '/test.log', print_r( $result, true ) . "\n". FILE_APPEND );
		$this->assertEquals( $expected, $result, 'extract_images_from_html did not return the correct image information' );
	}

	/**
	 * Extract information from an image wrapped in a link
	 */
	public function test_extract_image_in_link() {
		$html     = '<a href="https://example.com"><img src="https://example.com/test.jpg"></a>';
		$expected = [
			[
				'full'         => '<a href="https://example.com"><img src="https://example.com/test.jpg"></a>',
				'figure_class' => '',
				'inner_code'   => '<a href="https://example.com"><img src="https://example.com/test.jpg"></a>',
				'img_src'      => 'https://example.com/test.jpg',
			],
		];
		$result   = ISC_Model::extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'extract_images_from_html did not return the correct image information' );
	}

	/**
	 * Extract information from an image wrapped in a figure tag without classes
	 * <figure> is ignored since it doesn’t have any classes
	 */
	public function test_extract_image_in_figure_without_classes() {
		$html     = '<figure><img src="https://example.com/test.jpg"></figure>';
		$expected = [
			[
				'full'         => '<img src="https://example.com/test.jpg">',
				'figure_class' => '',
				'inner_code'   => '<img src="https://example.com/test.jpg">',
				'img_src'      => 'https://example.com/test.jpg',
			],
		];
		$result   = ISC_Model::extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'extract_images_from_html did not return the correct image information' );
	}

	/**
	 * Test with missing closing a tag
	 * ISC will include the <a> tag even if it is unclosed.
	 */
	public function test_missing_closing_a_tag() {
		$html     = '<figure class="some-class"><a href="https://example.com"><img src="https://example.com/test.jpg"></figure>';
		$expected = [
			[
				'full'         => '<figure class="some-class"><a href="https://example.com"><img src="https://example.com/test.jpg">',
				'figure_class' => 'some-class',
				'inner_code'   => '<a href="https://example.com"><img src="https://example.com/test.jpg">',
				'img_src'      => 'https://example.com/test.jpg',
			],
		];
		$result   = ISC_Model::extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'extract_images_from_html did not return the correct image information' );
	}

	/**
	 * Extract information from an image wrapped in a figure tag with classes
	 */
	public function test_extract_image_in_figure_with_classes() {
		$html     = '<figure class="wp-block-image"><img src="https://example.com/test.jpg"></figure>';
		$expected = [
			[
				'full'         => '<figure class="wp-block-image"><img src="https://example.com/test.jpg">',
				'figure_class' => 'wp-block-image',
				'inner_code'   => '<img src="https://example.com/test.jpg">',
				'img_src'      => 'https://example.com/test.jpg',
			],
		];
		$result   = ISC_Model::extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'extract_images_from_html did not return the correct image information' );
	}

	/**
	 * Extract information from image wrapped in a figure tag with classes and a link
	 */
	public function test_extract_image_in_figure_with_classes_and_link() {
		$html     = '<figure class="wp-block-image isc-disable-overlay"><a href="https://example.com"><img src="https://example.com/test.jpg"></a></figure>';
		$expected = [
			[
				'full'         => '<figure class="wp-block-image isc-disable-overlay"><a href="https://example.com"><img src="https://example.com/test.jpg"></a>',
				'figure_class' => 'wp-block-image isc-disable-overlay',
				'inner_code'   => '<a href="https://example.com"><img src="https://example.com/test.jpg"></a>',
				'img_src'      => 'https://example.com/test.jpg',
			],
		];
		$result   = ISC_Model::extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'extract_images_from_html did not return the correct image information' );
	}

	/**
	 * Test real HTML from Classic Editor
	 */
	public function test_extract_image_from_classic_editor() {
		$html     = '<p><img decoding="async" loading="lazy" class="alignnone size-medium wp-image-6315" src="http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg" alt="" width="300" height="179" srcset="http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg 300w, http://example.com/wp-content/uploads/2023/04/logo.jpeg 512w" sizes="(max-width: 300px) 100vw, 300px" /></p>';
		$expected = [
			[
				'full'         => '<img decoding="async" loading="lazy" class="alignnone size-medium wp-image-6315" src="http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg" alt="" width="300" height="179" srcset="http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg 300w, http://example.com/wp-content/uploads/2023/04/logo.jpeg 512w" sizes="(max-width: 300px) 100vw, 300px" />',
				'figure_class' => '',
				'inner_code'   => '<img decoding="async" loading="lazy" class="alignnone size-medium wp-image-6315" src="http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg" alt="" width="300" height="179" srcset="http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg 300w, http://example.com/wp-content/uploads/2023/04/logo.jpeg 512w" sizes="(max-width: 300px) 100vw, 300px" />',
				'img_src'      => 'http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg',
			],
		];
		$result   = ISC_Model::extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'extract_images_from_html did not return the correct image information' );
	}

	/**
	 * Test real HTML from Block Editor
	 */
	public function test_extract_image_from_block_editor() {
		$html     = '<figure class="wp-block-image aligncenter size-full is-style-default isc_disable_overlay"><a href="http://example.com/wp-content/uploads/2023/04/logo.jpeg"><img decoding="async" width="512" height="306" src="http://example.com/wp-content/uploads/2023/04/logo.jpeg" alt="" class="wp-image-6315" srcset="http://example.com/wp-content/uploads/2023/04/logo.jpeg 512w, http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg 300w" sizes="(max-width: 512px) 100vw, 512px" /></a></figure>';
		$expected = [
			[
				'full'         => '<figure class="wp-block-image aligncenter size-full is-style-default isc_disable_overlay"><a href="http://example.com/wp-content/uploads/2023/04/logo.jpeg"><img decoding="async" width="512" height="306" src="http://example.com/wp-content/uploads/2023/04/logo.jpeg" alt="" class="wp-image-6315" srcset="http://example.com/wp-content/uploads/2023/04/logo.jpeg 512w, http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg 300w" sizes="(max-width: 512px) 100vw, 512px" /></a>',
				'figure_class' => 'wp-block-image aligncenter size-full is-style-default isc_disable_overlay',
				'inner_code'   => '<a href="http://example.com/wp-content/uploads/2023/04/logo.jpeg"><img decoding="async" width="512" height="306" src="http://example.com/wp-content/uploads/2023/04/logo.jpeg" alt="" class="wp-image-6315" srcset="http://example.com/wp-content/uploads/2023/04/logo.jpeg 512w, http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg 300w" sizes="(max-width: 512px) 100vw, 512px" /></a>',
				'img_src'      => 'http://example.com/wp-content/uploads/2023/04/logo.jpeg',
			],
		];
		$result   = ISC_Model::extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'extract_images_from_html did not return the correct image information' );
	}

	/**
	 * Find multiple images without additional markup between them, but slightly different attributes.
	 */
	public function test_extract_multiple_images_without_markup() {
		$html     = '<img src="https://example.com/image.jpeg" alt="" width="300" height="179"/><img alt="Second Image" src="https://example.com/image2.jpeg" width="728" height="90"/><img alt="" width="120" height="50" src="https://example.com/image3.jpeg"/>';
		$expected = [
			[
				'full' => '<img src="https://example.com/image.jpeg" alt="" width="300" height="179"/>',
				'figure_class' => '',
				'inner_code' => '<img src="https://example.com/image.jpeg" alt="" width="300" height="179"/>',
				'img_src' => 'https://example.com/image.jpeg',
			],
			[
				'full' => '<img alt="Second Image" src="https://example.com/image2.jpeg" width="728" height="90"/>',
				'figure_class' => '',
				'inner_code' => '<img alt="Second Image" src="https://example.com/image2.jpeg" width="728" height="90"/>',
				'img_src' => 'https://example.com/image2.jpeg',
			],
			[
				'full' => '<img alt="" width="120" height="50" src="https://example.com/image3.jpeg"/>',
				'figure_class' => '',
				'inner_code' => '<img alt="" width="120" height="50" src="https://example.com/image3.jpeg"/>',
				'img_src' => 'https://example.com/image3.jpeg',
			]
		];
		$result   = ISC_Model::extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'extract_images_from_html did not return the correct image information' );
	}

	/**
	 * Find multiple images in HTML
	 */
	public function test_extract_multiple_images_with_markup() {
		$html     = '<figure class="wp-block-image"><img decoding="async" width="267" height="200" class="wp-image-177" src="http://example.com/wp-content/uploads/2020/11/400x300-267x200.png" alt="" srcset="http://example.com/wp-content/uploads/2020/11/400x300-267x200.png 267w, http://example.com/wp-content/uploads/2020/11/400x300.png 400w" sizes="(max-width: 267px) 100vw, 267px" /></figure>
<p><img decoding="async" loading="lazy" class="alignnone size-medium wp-image-6315" src="http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg" alt="" width="300" height="179" srcset="http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg 300w, http://example.com/wp-content/uploads/2023/04/logo.jpeg 512w" sizes="(max-width: 300px) 100vw, 300px" /></p>';
		$expected = [
			[
				'full'         => '<figure class="wp-block-image"><img decoding="async" width="267" height="200" class="wp-image-177" src="http://example.com/wp-content/uploads/2020/11/400x300-267x200.png" alt="" srcset="http://example.com/wp-content/uploads/2020/11/400x300-267x200.png 267w, http://example.com/wp-content/uploads/2020/11/400x300.png 400w" sizes="(max-width: 267px) 100vw, 267px" />',
				'figure_class' => 'wp-block-image',
				'inner_code'   => '<img decoding="async" width="267" height="200" class="wp-image-177" src="http://example.com/wp-content/uploads/2020/11/400x300-267x200.png" alt="" srcset="http://example.com/wp-content/uploads/2020/11/400x300-267x200.png 267w, http://example.com/wp-content/uploads/2020/11/400x300.png 400w" sizes="(max-width: 267px) 100vw, 267px" />',
				'img_src'      => 'http://example.com/wp-content/uploads/2020/11/400x300-267x200.png',
			],
			[
				'full'         => '<img decoding="async" loading="lazy" class="alignnone size-medium wp-image-6315" src="http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg" alt="" width="300" height="179" srcset="http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg 300w, http://example.com/wp-content/uploads/2023/04/logo.jpeg 512w" sizes="(max-width: 300px) 100vw, 300px" />',
				'figure_class' => '',
				'inner_code'   => '<img decoding="async" loading="lazy" class="alignnone size-medium wp-image-6315" src="http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg" alt="" width="300" height="179" srcset="http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg 300w, http://example.com/wp-content/uploads/2023/04/logo.jpeg 512w" sizes="(max-width: 300px) 100vw, 300px" />',
				'img_src'      => 'http://example.com/wp-content/uploads/2023/04/logo-300x179.jpeg',
			],
		];
		$result   = ISC_Model::extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'extract_images_from_html did not return the correct image information' );
	}

	/**
	 * Test line breaks between the tags
	 */
	public function test_line_breaks_between_tags() {
		$html     = '<figure class="wp-block-image isc-disable-overlay">
			<a href="https://example.com">
				<img src="https://example.com/test.jpg">
			</a>
		</figure>';
		$expected = [
			[
				'full'         => '<figure class="wp-block-image isc-disable-overlay">
			<a href="https://example.com">
				<img src="https://example.com/test.jpg">
			</a>',
				'figure_class' => 'wp-block-image isc-disable-overlay',
				'inner_code'   => '<a href="https://example.com">
				<img src="https://example.com/test.jpg">
			</a>',
				'img_src'      => 'https://example.com/test.jpg',
			],
		];
		$result   = ISC_Model::extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'extract_images_from_html did not return the correct image information' );
	}

	/**
	 * Test line breaks within the tags
	 */
	public function test_line_breaks_within_tags() {
		$html     = '<figure
class="with-line-break"><a
href="https://example.com"><img
src="https://example.com/image.jpg">
</a>
</figure>';
		$expected = [
			[
				'full'         => '<figure
class="with-line-break"><a
href="https://example.com"><img
src="https://example.com/image.jpg">
</a>',
				'figure_class' => 'with-line-break',
				'inner_code'   => '<a
href="https://example.com"><img
src="https://example.com/image.jpg">
</a>',
				'img_src'      => 'https://example.com/image.jpg',
			],
		];
		$result   = ISC_Model::extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'extract_images_from_html did not return the correct image information' );
	}

	/**
	 * Test with line breaks and spaces
	 */
	public function test_line_breaks_and_spaces_between_tags() {
		$html     = '<figure class="wp-block-image isc-disable-overlay"> 
			<a href="https://example.com"> 
				<img src="https://example.com/test.jpg"> 
			</a>
		</figure>';
		$expected = [
			[
				'full'         => '<figure class="wp-block-image isc-disable-overlay"> 
			<a href="https://example.com"> 
				<img src="https://example.com/test.jpg"> 
			</a>',
				'figure_class' => 'wp-block-image isc-disable-overlay',
				'inner_code'   => '<a href="https://example.com"> 
				<img src="https://example.com/test.jpg"> 
			</a>',
				'img_src'      => 'https://example.com/test.jpg',
			],
		];
		$result   = ISC_Model::extract_images_from_html( $html );
		$this->assertEquals( $expected, $result, 'extract_images_from_html did not return the correct image information' );
	}
}

