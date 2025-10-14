<?php

namespace ISC\Tests\WPUnit\Includes\Image_Sources\Analyze_HTML;

use ISC\Image_Sources\Analyze_HTML;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test if ISC/Analyze_HTML:extract_image_id() works as expected.
 */
class Extract_Image_Id_Test extends WPTestCase {

	/**
	 * Helper to extract information from HTML
	 *
	 * @var Analyze_HTML
	 */
	public $html_analyzer;

	public function setUp(): void {
		parent::setUp();
		$this->html_analyzer = new Analyze_HTML();
	}

	/**
	 * Test extracting ID from wp-image class
	 */
	public function test_extract_id_from_wp_image_class() {
		$html     = '<img class="wp-image-123" src="https://example.com/image.jpg">';
		$expected = 123;
		$result   = $this->html_analyzer->extract_image_id( $html );
		$this->assertEquals( $expected, $result, 'extract_image_id should extract ID from wp-image class' );
	}

	/**
	 * Test extracting ID from data-id attribute with double quotes
	 */
	public function test_extract_id_from_data_id_double_quotes() {
		$html     = '<img data-id="456" src="https://example.com/image.jpg">';
		$expected = 456;
		$result   = $this->html_analyzer->extract_image_id( $html );
		$this->assertEquals( $expected, $result, 'extract_image_id should extract ID from data-id with double quotes' );
	}

	/**
	 * Test extracting ID from data-id attribute with single quotes
	 */
	public function test_extract_id_from_data_id_single_quotes() {
		$html     = '<img data-id=\'789\' src="https://example.com/image.jpg">';
		$expected = 789;
		$result   = $this->html_analyzer->extract_image_id( $html );
		$this->assertEquals( $expected, $result, 'extract_image_id should extract ID from data-id with single quotes' );
	}

	/**
	 * Test that wp-image class takes precedence over data-id
	 */
	public function test_wp_image_class_precedence() {
		$html     = '<img class="wp-image-123" data-id="456" src="https://example.com/image.jpg">';
		$expected = 123;
		$result   = $this->html_analyzer->extract_image_id( $html );
		$this->assertEquals( $expected, $result, 'extract_image_id should use wp-image class when both are present' );
	}

	/**
	 * Test with mixed quotes (single quote in data-id)
	 */
	public function test_mixed_quotes() {
		$html     = '<img class="some-class" data-id=\'999\' src=\'https://example.com/image.jpg\'>';
		$expected = 999;
		$result   = $this->html_analyzer->extract_image_id( $html );
		$this->assertEquals( $expected, $result, 'extract_image_id should handle mixed quote styles' );
	}

	/**
	 * Test with no ID present
	 */
	public function test_no_id_present() {
		$html     = '<img src="https://example.com/image.jpg" alt="Test">';
		$expected = 0;
		$result   = $this->html_analyzer->extract_image_id( $html );
		$this->assertEquals( $expected, $result, 'extract_image_id should return 0 when no ID is found' );
	}

	/**
	 * Test with empty HTML
	 */
	public function test_empty_html() {
		$html     = '';
		$expected = 0;
		$result   = $this->html_analyzer->extract_image_id( $html );
		$this->assertEquals( $expected, $result, 'extract_image_id should return 0 for empty HTML' );
	}

	/**
	 * Test with wp-image class in various positions
	 */
	public function test_wp_image_class_various_positions() {
		$html1     = '<img class="alignleft wp-image-111" src="https://example.com/image.jpg">';
		$html2     = '<img class="wp-image-222 alignleft" src="https://example.com/image.jpg">';
		$html3     = '<img class="some-class wp-image-333 another-class" src="https://example.com/image.jpg">';
		
		$this->assertEquals( 111, $this->html_analyzer->extract_image_id( $html1 ), 'Should extract ID when wp-image is in the middle' );
		$this->assertEquals( 222, $this->html_analyzer->extract_image_id( $html2 ), 'Should extract ID when wp-image is at the start' );
		$this->assertEquals( 333, $this->html_analyzer->extract_image_id( $html3 ), 'Should extract ID when wp-image is surrounded by classes' );
	}

	/**
	 * Test with data-id in complex HTML
	 */
	public function test_data_id_in_complex_html() {
		$html     = '<div class="wrapper"><img alt="Test" data-id=\'555\' class="image" src=\'https://example.com/test.jpg\' /></div>';
		$expected = 555;
		$result   = $this->html_analyzer->extract_image_id( $html );
		$this->assertEquals( $expected, $result, 'extract_image_id should extract data-id from complex HTML with single quotes' );
	}
}
