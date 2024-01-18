<?php

namespace ISC\Tests\WPUnit;

/**
 * Test if \ISC\Pro\Compatibility\Avada_Builder:add_source_text_for_databg_attribute() converts a data-bg attribute into a caption.
 */
class Avada_Builder_Test extends \Codeception\TestCase\WPTestCase {

	protected $avadaBuilder;

	public function setUp(): void {
		parent::setUp();
		$this->avadaBuilder = new \ISC\Pro\Compatibility\Avada_Builder();

		$this->create_image_with_post_meta();
	}

	/**
	 * Create an image with post meta.
	 *
	 * @return void
	 */
	public function create_image_with_post_meta() {
		// Create an image (attachment post type)
		$this->image_id = wp_insert_post([
                      'post_type' => 'attachment',
                      'post_title' => 'Test Image',
                      'guid' => 'https://example.com/image.jpg',
                      'post_mime_type' => 'image/jpeg',
                  ]);

		// Add post meta to the image
		add_post_meta($this->image_id, 'isc_image_source', 'Test Source');
	}

	/**
	 * Return the original div unchanged if it doesn't contain a data-bg attribute.
	 *
	 * @return void
	 */
	public function test_no_databg() {
		$html = '<div><p>Some Content</p></div>';

		$result = $this->avadaBuilder->add_source_text_for_databg_attribute( $html );

		$this->assertEquals( $html, $result );
	}

	/**
	 * Test the `add_source_text_for_databg_attribute` method.
	 *
	 * This method adds the `data-isc-source-text` attribute to the given HTML element that has the `data-bg` attribute.
	 *
	 * @return void
	 */
	public function test_div_with_databg_attribute() {
		$html         = '<div data-bg="https://example.com/image.jpg"></div>';
		$expected     = '<div data-bg="https://example.com/image.jpg" data-isc-source-text="Source: Test Source"></div>';

		$result = $this->avadaBuilder->add_source_text_for_databg_attribute( $html );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test the `add_source_text_for_databg_attribute` method.
	 *
	 * This method adds the `data-isc-source-text` attribute to the given HTML element that has the `data-bg` attribute.
	 *
	 * @return void
	 */
	public function test_div_with_databg_attribute_unknown_image() {
		$html         = '<div data-bg="https://example.com/picture.jpg"></div>';
		$expected     = '<div data-bg="https://example.com/picture.jpg"></div>';

		$result = $this->avadaBuilder->add_source_text_for_databg_attribute( $html );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * The inline data attribute with the source text is ignored for non-div tages.
	 *
	 * @return void
	 */
	public function test_span_with_databg_attribute() {
		$html         = '<span data-bg="https://example.com/image.jpg"></span>';
		$expected     = '<span data-bg="https://example.com/image.jpg"></span>';

		$result = $this->avadaBuilder->add_source_text_for_databg_attribute( $html );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * This method adds the `data-isc-source-text` attribute to the given HTML elements that have the `data-bg` attribute,
	 * except for the second div element in the HTML string, which does not have an image source.
	 *
	 * @return void
	 */
	public function test_multiple_div_elements() {
		$html         = '<div data-bg="https://example.com/image.jpg"></div><div data-bg="https://example.com/picture.jpg"></div>';
		$expected     = '<div data-bg="https://example.com/image.jpg" data-isc-source-text="Source: Test Source"></div><div data-bg="https://example.com/picture.jpg"></div>';

		$result = $this->avadaBuilder->add_source_text_for_databg_attribute( $html );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Add the `data-isc-images` attribute to the code if the option to force the overlay for Avada background images is enabled.
	 */
	public function test_add_overlay() {
		$html         = '<div data-bg="https://example.com/image.jpg"></div>';
		$expected     = '<div data-bg="https://example.com/image.jpg" data-isc-source-text="Source: Test Source" data-isc-images="' . $this->image_id . '"></div>';

		// dynamically enable the Avada setting
		add_filter( 'isc_default_settings', function( $settings ) {
			$settings['overlay_included_advanced'][] = 'avada_background_overlay';
			return $settings;
		} );

		$result = $this->avadaBuilder->add_source_text_for_databg_attribute( $html );

		$this->assertEquals( $expected, $result );
	}
}