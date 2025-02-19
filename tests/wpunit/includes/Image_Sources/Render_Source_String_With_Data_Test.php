<?php

namespace ISC\Tests\WPUnit\Includes\Image_Sources;

use ISC\Image_Sources\Renderer\Image_Source_String;
use ISC\Options;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test if ISC_Public::render_image_source_string() renders the image source string correctly
 * if parameters are given with $data
 */
class Render_Source_String_With_Data_Test extends WPTestCase {

	private $iscPublic;

	public function test_render_image_source_string_with_standard_source() {
		$this->assertEquals( '© http://isc.local', Image_Source_String::get( 1, [ 'own' => true ] ) );
	}

	public function test_render_image_source_string_with_source() {
		$this->assertEquals( 'Test Source', Image_Source_String::get( 1, [ 'source' => 'Test Source' ] ) );
	}

	public function test_render_image_source_string_without_source() {
		$this->assertFalse( Image_Source_String::get( 1 ) );
	}

	public function test_render_image_source_string_with_source_url() {
		$this->assertEquals( '<a href="https://example.com" target="_blank" rel="nofollow">Test Source</a>', Image_Source_String::get( 1, [ 'source' => 'Test Source', 'source_url' => 'https://example.com' ] ) );
	}

	public function test_render_image_source_string_without_source_url() {
		$this->assertEquals( 'Test Source', Image_Source_String::get( 1, [ 'source' => 'Test Source', 'source_url' => '' ] ) );
	}

	/**
	 * Test with a license set in the default options with a URL
	 */
	public function test_render_image_source_string_with_known_license() {
		// activate licenses
		$isc_options                    = Options::get_options();
		$isc_options['enable_licences'] = true;
		update_option( 'isc_options', $isc_options );

		$this->assertEquals(
			'Test Source | <a href="https://creativecommons.org/licenses/by-nc-nd/4.0/" target="_blank" rel="nofollow">CC BY-NC-ND 4.0 International</a>',
			Image_Source_String::get( 1, [ 'source' => 'Test Source', 'licence' => 'CC BY-NC-ND 4.0 International' ] )
		);
	}

	/**
	 * Test with a license unknown to ISC that has no URL
	 */
	public function test_render_image_source_string_with_unknown_license() {
		// activate licenses
		$isc_options                    = Options::get_options();
		$isc_options['enable_licences'] = true;
		update_option( 'isc_options', $isc_options );

		$this->assertEquals( 'Test Source | Personal License', Image_Source_String::get( 1, [ 'source' => 'Test Source', 'licence' => 'Personal License' ] ) );
	}

	public function test_render_image_source_string_without_license() {
		// activate licenses
		$isc_options                    = Options::get_options();
		$isc_options['enable_licences'] = true;
		update_option( 'isc_options', $isc_options );

		$this->assertEquals( 'Test Source', Image_Source_String::get( 1, [ 'source' => 'Test Source', 'licence' => '' ] ) );
	}
}