<?php

namespace ISC\Tests\WPUnit\Includes\Image_Sources;

use ISC\Image_Sources\Renderer\Image_Source_String;
use ISC\Options;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test if ISC_Public::render_image_source_string() renders the image source string correctly
 * giving an image ID and using the "disable-links" parameter to not print any links
 */
class Render_Source_String_With_Id_Disable_Link_Test extends WPTestCase {

	private $iscPublic;

	/**
	 * Image ID
	 *
	 * @var int
	 */
	private $image_id;

	protected function setUp(): void {
		$this->image_id = $this->factory()->post->create( [
			                                          'post_title' => 'Image One',
			                                          'post_type'  => 'attachment',
			                                          'guid'       => 'https://example.com/image-one.jpg',
		                                          ] );

		add_post_meta( $this->image_id, 'isc_image_source', 'Author A' );
		add_post_meta( $this->image_id, 'isc_image_source_url', 'https://example.com' );
	}


	/**
	 * Render the image source with a source URL
	 */
	public function test_render_image_source_string_with_source_url() {
		$this->assertEquals(
			'Author A',
			Image_Source_String::get( $this->image_id, [], [ 'disable-links' => true ] )
		);
	}

	/**
	 * Test with a license set in the default options with a URL
	 */
	public function test_render_image_source_string_with_known_license() {
		// activate licenses
		$isc_options                    = Options::get_options();
		$isc_options['enable_licences'] = true;
		update_option( 'isc_options', $isc_options );

		add_post_meta( $this->image_id, 'isc_image_licence', 'CC BY-NC-ND 4.0 International' );

		$this->assertEquals(
			'Author A | CC BY-NC-ND 4.0 International',
			Image_Source_String::get( $this->image_id, [], [ 'disable-links' => true ] )
		);
	}
}