<?php

namespace ISC\Tests\WPUnit;

use \ISC_Public;

/**
 * Test if ISC_Public::render_image_source_string() renders the image source string correctly
 * if parameters are given with $data
 */
class Render_Source_String_With_Data_Test extends \Codeception\TestCase\WPTestCase {

	private $iscPublic;

	protected function setUp(): void {
		$this->iscPublic = new ISC_Public();
	}

	public function test_render_image_source_string_with_standard_source() {
		$this->assertEquals( '© http://isc.local', $this->iscPublic->render_image_source_string( 1, [ 'own' => true ] ) );
	}

	public function test_render_image_source_string_with_source() {
		$this->assertEquals( 'Test Source', $this->iscPublic->render_image_source_string( 1, [ 'source' => 'Test Source' ] ) );
	}

	public function test_render_image_source_string_without_source() {
		$this->assertFalse( $this->iscPublic->render_image_source_string( 1 ) );
	}

	public function test_render_image_source_string_with_source_url() {
		$this->assertEquals( '<a href="https://example.com" target="_blank" rel="nofollow">Test Source</a>', $this->iscPublic->render_image_source_string( 1, [ 'source' => 'Test Source', 'source_url' => 'https://example.com' ] ) );
	}

	public function test_render_image_source_string_without_source_url() {
		$this->assertEquals( 'Test Source', $this->iscPublic->render_image_source_string( 1, [ 'source' => 'Test Source', 'source_url' => '' ] ) );
	}

	/**
	 * Test with a license set in the default options with a URL
	 */
	public function test_render_image_source_string_with_known_license() {
		// activate licenses
		$isc_options                            = \ISC_Class::get_instance()->get_isc_options();
		$isc_options['enable_licences'] = true;
		update_option( 'isc_options', $isc_options );

		$this->assertEquals(
			'Test Source | <a href="https://creativecommons.org/licenses/by-nc-nd/4.0/" target="_blank" rel="nofollow">CC BY-NC-ND 4.0 International</a>',
		    $this->iscPublic->render_image_source_string( 1, [ 'source' => 'Test Source', 'licence' => 'CC BY-NC-ND 4.0 International' ] )
		);
	}

	/**
	 * Test with a license unknown to ISC that has no URL
	 */
	public function test_render_image_source_string_with_unknown_license() {
		// activate licenses
		$isc_options                            = \ISC_Class::get_instance()->get_isc_options();
		$isc_options['enable_licences'] = true;
		update_option( 'isc_options', $isc_options );

		$this->assertEquals( 'Test Source | Personal License', $this->iscPublic->render_image_source_string( 1, [ 'source' => 'Test Source', 'licence' => 'Personal License' ] ) );
	}

	public function test_render_image_source_string_without_license() {
		// activate licenses
		$isc_options                            = \ISC_Class::get_instance()->get_isc_options();
		$isc_options['enable_licences'] = true;
		update_option( 'isc_options', $isc_options );

		$this->assertEquals( 'Test Source', $this->iscPublic->render_image_source_string( 1, [ 'source' => 'Test Source', 'licence' => '' ] ) );
	}
}