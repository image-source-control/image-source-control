<?php

namespace ISC\Tests\WPUnit;

use \ISC_Public;

/**
 * Test ISC_Public::add_source_captions_to_content()
 */
class Add_Source_Captions_To_Content_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test a complex HTML example with multiples images
	 * - extract the image ID from wp-image-(\d+)|data-id
	 */

	/**
	 * @var ISC_Public
	 */
	protected $isc_public;

	/**
	 * Image ID
	 *
	 * @var int
	 */
	private $image_id;

	public function setUp(): void {
		parent::setUp();

		$this->image_id = $this->factory()->post->create( [
			                                                  'post_title' => 'Image One',
			                                                  'post_type'  => 'attachment',
			                                                  'guid'       => 'https://example.com/image-one.jpg',
		                                                  ] );

		add_post_meta( $this->image_id, 'isc_image_source', 'Author A' );

		$this->isc_public = new ISC_Public();
	}

	/**
	 * Test an image from the media library with a source given
	 */
	public function test_image_with_source() {
		$html     = '<img src="https://example.com/image-one.jpg" alt="Image" />';
		$expected = '<span id="isc_attachment_' . $this->image_id . '" class="isc-source "><img src="https://example.com/image-one.jpg" alt="Image" /><span class="isc-source-text">Source: Author A</span></span>';
		$result   = $this->isc_public->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result, 'Image with source was marked incorrectly' );
	}

	/**
	 * Test an image from the media library without a source
	 */
	public function test_image_without_source() {
		$this->factory()->post->create( [
			                                'post_title' => 'Image Two',
			                                'post_type'  => 'attachment',
			                                'guid'       => 'https://example.com/image-two.jpg',
		                                ] );

		$html     = '<img src="https://example.com/image-two.jpg" alt="Image" />';
		$expected = '<img src="https://example.com/image-two.jpg" alt="Image" />';
		$result   = $this->isc_public->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result, 'Image without a source was marked incorrectly' );
	}

	/**
	 * Extract the image based on the image ID in the wp-image- attribute
	 */
	public function test_image_with_wp_image_id() {
		// note, the URL is not the same as the one in the image, since we want to force ISC to look for the ID in `wp-image-ID`
		$html     = '<img src="https://example.com/image.jpg" alt="Image" class="wp-image-' . $this->image_id . '" />';
		$expected = '<span id="isc_attachment_' . $this->image_id . '" class="isc-source "><img src="https://example.com/image.jpg" alt="Image" class="wp-image-' . $this->image_id . ' with-source" /><span class="isc-source-text">Source: Author A</span></span>';
		$result   = $this->isc_public->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result, 'Image with wp-image- class found incorrectly' );
	}

	/**
	 * Test an image that is not part of the media library
	 */
	public function test_image_not_in_media_library() {
		$html     = '<img src="https://example.com/image.jpg" alt="Image" />';
		$expected = '<img src="https://example.com/image.jpg" alt="Image" />';
		$result   = $this->isc_public->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result, 'Image not in media library was marked incorrectly' );
	}

	/**
	 * Test an image that does not have a src attribute
	 */
	public function test_image_without_src() {
		$html     = '<img alt="Image" />';
		$expected = '<img alt="Image" />';
		$result   = $this->isc_public->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result, 'Image without a src attribute was marked incorrectly' );
	}

	/**
	 * An image with a source and also the "use standard source" option set, so it should fall back to show the standard source text
	 */
	public function test_image_with_standard_source() {
		$image_id = $this->factory()->post->create( [
			                                            'post_title' => 'Image Three',
			                                            'post_type'  => 'attachment',
			                                            'guid'       => 'https://example.com/image-three.jpg',
		                                            ] );

		add_post_meta( $image_id, 'isc_image_source', 'Author C' );
		add_post_meta( $image_id, 'isc_image_source_own', true );

		$html     = '<img src="https://example.com/image-three.jpg" alt="Image" />';
		$expected = '<span id="isc_attachment_' . $image_id . '" class="isc-source "><img src="https://example.com/image-three.jpg" alt="Image" /><span class="isc-source-text">Source: © http://isc.local</span></span>';
		$result   = $this->isc_public->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result, 'Image with source and standard source was marked incorrectly' );
	}

	/**
	 * An image that has the "alignright" attribute in its container and the source markup inherits it
	 */
	public function test_image_with_alignright() {
		$html     = '<figcaption class="alignright"><img src="https://example.com/image-one.jpg" alt="Image" /></figcaption>';
		$expected = '<figcaption class="alignright"><span id="isc_attachment_' . $this->image_id . '" class="isc-source "><img src="https://example.com/image-one.jpg" alt="Image" /><span class="isc-source-text">Source: Author A</span></span></figcaption>';
		$result   = $this->isc_public->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result, 'Image with alignright class was marked incorrectly' );
	}

	/**
	 * Display the caption while the pretext in the main options is empty
	 */
	public function test_image_without_pretext() {
		// empty the pretext
		$isc_options                   = \ISC_Class::get_instance()->get_isc_options();
		$isc_options['source_pretext'] = '';
		update_option( 'isc_options', $isc_options );

		$html     = '<img src="https://example.com/image-one.jpg" alt="Image" />';
		$expected = '<span id="isc_attachment_' . $this->image_id . '" class="isc-source "><img src="https://example.com/image-one.jpg" alt="Image" /><span class="isc-source-text">Author A</span></span>';
		$result   = $this->isc_public->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result, 'Image caption text should not have a pretext' );
	}

	/**
	 * Don’t show the caption markup when the caption_style option is set to "none"
	 */
	public function test_image_with_caption_style_none() {
		// set the caption style to none
		$isc_options                  = \ISC_Class::get_instance()->get_isc_options();
		$isc_options['caption_style'] = 'none';
		update_option( 'isc_options', $isc_options );

		$html     = '<img src="https://example.com/image-one.jpg" alt="Image" />';
		$expected = '<img src="https://example.com/image-one.jpg" alt="Image" />Source: Author A';
		$result   = $this->isc_public->add_source_captions_to_content( $html );
		$this->assertEquals( $expected, $result, 'Image caption markup should not be shown' );
	}
}