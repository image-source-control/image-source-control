<?php

namespace ISC\Tests\WPUnit\Model;

use \ISC\Tests\WPUnit\WPTestCase;
use \ISC_Model;

/**
 * Test if ISC_Model::sanitize_source_url_string() works as expected.
 */
class Sanitize_Source_Url_Test extends WPTestCase {

	/**
	 * Test if sanitize_source_url() escapes a normal URL without change.
	 */
	public function test_sanitize_correct_url() {
		$source_url   = 'https://imagesourcecontrol.com/somepage/?someparam=somevalue';
		$expected_url = 'https://imagesourcecontrol.com/somepage/?someparam=somevalue';

		$model  = new ISC_Model();
		$actual = $model->sanitize_source_url( $source_url );
		$this->assertEquals( $expected_url, $actual );
	}

	/**
	 * Test if sanitize_source_url() returns comma-separated URLs
	 */
	public function test_sanitize_multiple_urls_multiple_sources() {
		$source_url   = 'https://imagesourcecontrol.com/, https://imagesourcecontrol.de/';
		$expected_url = 'https://imagesourcecontrol.com/, https://imagesourcecontrol.de/';

		$model  = new ISC_Model();
		$actual = $model->sanitize_source_url( $source_url );
		$this->assertEquals( $expected_url, $actual );
	}

	/**
	 * Test if sanitize_source_url() keeps multiple commas
	 */
	public function test_sanitize_multiple_urls_multiple_sources_with_multiple_commas() {
		$source_url   = 'https://imagesourcecontrol.com/, , https://imagesourcecontrol.de/';
		$expected_url = 'https://imagesourcecontrol.com/, , https://imagesourcecontrol.de/';

		$model  = new ISC_Model();
		$actual = $model->sanitize_source_url( $source_url );
		$this->assertEquals( $expected_url, $actual );
	}

	/**
	 * Test if sanitize_source_url() returns comma-separated URLs correctly for URLs that contain a comma
	 */
	public function test_sanitize_multiple_urls_multiple_sources_with_comma() {
		$source_url   = 'https://imagesourcecontrol.com/?values=one,two,https://imagesourcecontrol.de/';
		$expected_url = 'https://imagesourcecontrol.com/?values=one,two,https://imagesourcecontrol.de/';

		$model  = new ISC_Model();
		$actual = $model->sanitize_source_url( $source_url );
		$this->assertEquals( $expected_url, $actual );
	}

	/**
	 * Remove HTML from URLs
	 */
	public function test_sanitize_invalid_urls() {
		$source_url   = 'https://imagesourcecontrol.com/,https://imagesourcecontrol.de/<script>alert("XSS")</script>';
		$expected_url = 'https://imagesourcecontrol.com/,https://imagesourcecontrol.de/alert("XSS")';

		$model  = new ISC_Model();
		$actual = $model->sanitize_source_url( $source_url );
		$this->assertEquals( $expected_url, $actual );
	}
}