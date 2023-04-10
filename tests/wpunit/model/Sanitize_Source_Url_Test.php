<?php

namespace ISC\Tests\WPUnit;

use \ISC_Model;

/**
 * Test if ISC_Model::sanitize_source_url_string() works as expected.
 */
class Sanitize_Source_Url_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test if sanitize_source_url() escapes a normal URL without change.
	 */
	public function test_sanitize_correct_url() {
		$source_url   = 'https://imagesourcecontrol.com/somepage/?someparam=somevalue';
		$expected_url = 'https://imagesourcecontrol.com/somepage/?someparam=somevalue';

		$model  = new ISC_Model();
		$actual = $model->sanitize_source_url( $source_url, 'Image Source Control' );
		$this->assertEquals( $expected_url, $actual );
	}

	/**
	 * Test if sanitize_source_url() returns the URL string escaped as one string, if multiple URLs are given in the string, but the source text does not contain a comma.
	 */
	public function test_sanitize_multiple_urls_in_string() {
		$source_url   = 'https://imagesourcecontrol.com/, https://imagesourcecontrol.de/';
		$expected_url = 'https://imagesourcecontrol.com/,%20https://imagesourcecontrol.de/';

		$model  = new ISC_Model();
		$actual = $model->sanitize_source_url( $source_url, 'Image Source Control' );
		$this->assertEquals( $expected_url, $actual );
	}

	/**
	 * Test if sanitize_source_url() returns comma-separated URLs if the source string also contains a comma.
	 * Individual URLs are trimmed.
	 */
	public function test_sanitize_multiple_urls_multiple_sources() {
		$source_url   = 'https://imagesourcecontrol.com/, https://imagesourcecontrol.de/';
		$expected_url = 'https://imagesourcecontrol.com/,https://imagesourcecontrol.de/';

		$model  = new ISC_Model();
		$actual = $model->sanitize_source_url( $source_url, 'Image Source Control, Image Source Control DE' );
		$this->assertEquals( $expected_url, $actual );
	}

	/**
	 * Escape invalid URLs
	 */
	public function test_sanitize_invalid_urls() {
		$source_url   = 'https://imagesourcecontrol.com/, invalid url, ftp://invalidurl.com, javascript:alert(1), http://invalidurl|pipe';
		$expected_url = 'https://imagesourcecontrol.com/,http://invalid%20url,ftp://invalidurl.com,,http://invalidurl|pipe';

		$model  = new ISC_Model();
		$actual = $model->sanitize_source_url( $source_url, 'One, Two, Three, Four, Five' );
		$this->assertEquals( $expected_url, $actual );
	}
}