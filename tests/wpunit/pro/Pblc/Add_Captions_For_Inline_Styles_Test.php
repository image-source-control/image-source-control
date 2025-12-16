<?php

namespace ISC\Tests\WPUnit\Pro\Pblc;

use ISC\Tests\WPUnit\WPTestCase;
use ISC_Pro_Public;
use ISC\Plugin;

/**
 * Test ISC_Pro_Public::add_captions_for_inline_styles()
 *
 * This test class focuses on testing the method that adds captions to HTML elements
 * with inline style attributes containing background image URLs.
 */
class Add_Captions_For_Inline_Styles_Test extends WPTestCase {

	/**
	 * @var ISC_Pro_Public
	 */
	protected ISC_Pro_Public $isc_pro_public;

	/**
	 * Image ID for testing
	 *
	 * @var int
	 */
	private $image_id;

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a test image attachment
		$this->image_id = $this->factory()->post->create( [
			                                                  'post_title' => 'Test Background Image',
			                                                  'post_type'  => 'attachment',
			                                                  'guid'       => 'https://example.com/background-image.jpg',
		                                                  ] );

		add_post_meta( $this->image_id, 'isc_image_source', 'Test Source' );

		$this->isc_pro_public = new ISC_Pro_Public();

		// Enable the inline style options
		$options                              = Plugin::get_options();
		$options['overlay_included_advanced'] = [
			'inline_style_data',
			'inline_style_show',
		];
		update_option( 'isc_options', $options );
	}

	/**
	 * Clean up after each test
	 */
	public function tearDown(): void {
		delete_option( 'isc_options' );
		parent::tearDown();
	}

	/**
	 * Test that the method handles URLs without quotes
	 */
	public function test_handles_urls_without_quotes() {
		$html = '<div style="background-image:url(https://example.com/background-image.jpg);"></div>';

		$result = $this->isc_pro_public->add_captions_for_inline_styles( $html );

		$this->assertStringContainsString( 'data-isc-source-text=', $result, 'Should handle URLs without quotes' );
		$this->assertStringContainsString( 'data-isc-images="' . $this->image_id . '"', $result, 'Should find image without quotes' );
	}

	/**
	 * Test that the method doesn't modify HTML when no background images are present
	 */
	public function test_returns_unchanged_html_when_no_images() {
		$html = '<div style="color: red; padding: 10px;"></div>';

		$result = $this->isc_pro_public->add_captions_for_inline_styles( $html );

		$this->assertEquals( $html, $result, 'Should not modify HTML without background images' );
	}

	/**
	 * Test that the method doesn't process duplicate URLs multiple times
	 */
	public function test_prevents_duplicate_processing() {
		$html = '<div style="background-image:url(&apos;https://example.com/background-image.jpg&apos;);"></div>' .
		        '<div style="background-image:url(&apos;https://example.com/background-image.jpg&apos;);"></div>';

		$result = $this->isc_pro_public->add_captions_for_inline_styles( $html );

		// Count how many times the image ID appears
		$count = substr_count( $result, 'data-isc-images="' . $this->image_id . '"' );

		// Both elements should get the attribute (they are different HTML elements)
		$this->assertEquals( 2, $count, 'Should process each element but track duplicates by URL hash' );
	}

	/**
	 * Test that both inline_style_data and inline_style_show options work correctly
	 */
	public function test_respects_inline_style_options() {
		$html = '<div style="background-image:url(&apos;https://example.com/background-image.jpg&apos;);"></div>';

		// Test with only data option enabled
		$options                              = Plugin::get_options();
		$options['overlay_included_advanced'] = [ 'inline_style_data' ];
		update_option( 'isc_options', $options );

		$result = $this->isc_pro_public->add_captions_for_inline_styles( $html );
		$this->assertStringContainsString( 'data-isc-source-text=', $result, 'Should add data attribute when option is enabled' );
		$this->assertStringNotContainsString( 'data-isc-images=', $result, 'Should not add images attribute when show option is disabled' );

		// Test with only show option enabled
		$options['overlay_included_advanced'] = [ 'inline_style_show' ];
		update_option( 'isc_options', $options );

		$result = $this->isc_pro_public->add_captions_for_inline_styles( $html );
		$this->assertStringNotContainsString( 'data-isc-source-text=', $result, 'Should not add data attribute when option is disabled' );
		$this->assertStringContainsString( 'data-isc-images=', $result, 'Should add images attribute when show option is enabled' );
	}

	/**
	 * Test that the method handles WordPress 6.4 escaped single quotes (&#039;)
	 * WordPress 6.4 started escaping single quotes in inline styles using &#039;
	 */
	public function test_handles_wordpress_64_escaped_quotes() {
		$html = '<div style="background-image: url(&#039;https://example.com/background-image.jpg&#039;);"></div>';

		$result = $this->isc_pro_public->add_captions_for_inline_styles( $html );

		// The method should strip the &#039; entities and find the image
		$this->assertStringContainsString( 'data-isc-source-text=', $result, 'Should add data-isc-source-text attribute' );
		$this->assertStringContainsString( 'data-isc-images="' . $this->image_id . '"', $result, 'Should add data-isc-images attribute with correct ID' );
	}

	/**
	 * Test that the method handles WordPress 6.9 escaped single quotes (&apos;)
	 * WordPress 6.9 changed the escape format from &#039; to &apos;
	 */
	public function test_handles_wordpress_69_escaped_quotes() {
		$html = '<div style="background-image:url(&apos;https://example.com/background-image.jpg&apos;);"></div>';

		$result = $this->isc_pro_public->add_captions_for_inline_styles( $html );

		// The method should strip the &apos; entities and find the image
		$this->assertStringContainsString( 'data-isc-source-text=', $result, 'Should add data-isc-source-text attribute' );
		$this->assertStringContainsString( 'data-isc-images="' . $this->image_id . '"', $result, 'Should add data-isc-images attribute with correct ID' );
	}
}