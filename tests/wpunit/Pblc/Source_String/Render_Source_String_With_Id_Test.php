<?php

namespace ISC\Tests\WPUnit\Pblc\Source_String;

use ISC\Tests\WPUnit\WPTestCase;
use \ISC_Public;

/**
 * Test if ISC_Public::render_image_source_string() renders the image source string correctly
 * giving an image ID with appropriate data
 */
class Render_Source_String_With_Id_Test extends WPTestCase {

	private $iscPublic;

	/**
	 * Image ID
	 *
	 * @var int
	 */
	private $image_id;

	protected function setUp(): void {
		$this->iscPublic = new ISC_Public();

		$this->image_id = $this->factory()->post->create( [
			                                          'post_title' => 'Image One',
			                                          'post_type'  => 'attachment',
			                                          'guid'       => 'https://example.com/image-one.jpg',
		                                          ] );

		add_post_meta( $this->image_id, 'isc_image_source', 'Author A' );

		// delete options to reset them to standard
		delete_option( 'isc_options' );
	}

	/**
	 * Cleanup
	 */
	protected function tearDown(): void {
		// delete the image
		wp_delete_post( $this->image_id, true );

		delete_post_meta( $this->image_id, 'isc_image_source' );
		delete_post_meta( $this->image_id, 'isc_image_source_own' );
		delete_post_meta( $this->image_id, 'isc_image_source_url' );
		delete_post_meta( $this->image_id, 'isc_image_licence' );

		// Call parent tearDown
		parent::tearDown();
	}

	/**
	 * If the standard source is set to be shown, show that instead of the image author
	 */
	public function test_render_image_source_string_with_standard_source() {
		// activate standard source for the image
		add_post_meta( $this->image_id, 'isc_image_source_own', 1, true );
		$this->assertEquals( '© http://isc.local', $this->iscPublic->render_image_source_string( $this->image_id ) );
	}

	/**
	 * Show the image author text
	 */
	public function test_render_image_source_string_with_source() {
		$this->assertEquals( 'Author A', $this->iscPublic->render_image_source_string( $this->image_id ) );
	}

	/**
	 * If no source is set, return false
	 */
	public function test_render_image_source_string_without_source() {
		delete_post_meta( $this->image_id, 'isc_image_source' );
		$this->assertFalse( $this->iscPublic->render_image_source_string( $this->image_id ) );
	}

	/**
	 * Render the image source with a source URL
	 */
	public function test_render_image_source_string_with_source_url() {
		add_post_meta( $this->image_id, 'isc_image_source_url', 'https://example.com' );
		$this->assertEquals(
			'<a href="https://example.com" target="_blank" rel="nofollow">Author A</a>',
			$this->iscPublic->render_image_source_string( $this->image_id )
		);
	}

	/**
	 * Test with a license set in the default options with a URL
	 */
	public function test_render_image_source_string_with_known_license() {
		// activate licenses
		$isc_options                            = \ISC_Class::get_instance()->get_isc_options();
		$isc_options['enable_licences'] = true;
		update_option( 'isc_options', $isc_options );

		add_post_meta( $this->image_id, 'isc_image_licence', 'CC BY-NC-ND 4.0 International' );

		$this->assertEquals(
			'Author A | <a href="https://creativecommons.org/licenses/by-nc-nd/4.0/" target="_blank" rel="nofollow">CC BY-NC-ND 4.0 International</a>',
		    $this->iscPublic->render_image_source_string( $this->image_id )
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

		add_post_meta( $this->image_id, 'isc_image_licence', 'Personal License' );

		$this->assertEquals( 'Author A | Personal License', $this->iscPublic->render_image_source_string( $this->image_id ) );
	}
}