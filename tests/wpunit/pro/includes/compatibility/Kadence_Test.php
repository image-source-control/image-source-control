<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Compatibility;

use ISC\Tests\WPUnit\Includes\Image_Sources\Analyze_HTML\Extract_Images_From_Html_Test;

/**
 * Test if ISC_Pro_Kadence provides compatibility with Kadence Blocks and Kadence Theme specific HTML.
 * This test is based on the Extract_Images_From_Html_Test class to see if all previous patters also work
 */
class Kadence_Test extends Extract_Images_From_Html_Test {

	public function setUp(): void {
		parent::setUp();
		// adjust the general regular expression to also search for DIVs between the image and the link tag.
		$kadence_class = new \ISC_Pro_Compatibility_Kadence();
		add_filter( 'isc_public_caption_regex', [ $kadence_class, 'public_caption_regex' ] );
		// filter the matches from the regular expression to apply some fixes.
		add_filter( 'isc_extract_images_from_html', [ $kadence_class, 'filter_matches' ], 10, 2 );
	}

	/**
	 * Test if ISC_Pro_Kadence::public_caption_regex() finds images that have a DIV container between A tag and IMG tag.
	 */
	public function test_public_caption_regex() {
		$markup   = '<figure class="alignleft"><a href="https://example.com/image.png"><div class="kadence-blocks-gallery-item"><img src="https://example.com/image.png"/></div></a></figure>';
		$expected = [
			[
				'full' => '<figure class="alignleft"><a href="https://example.com/image.png"><div class="kadence-blocks-gallery-item"><img src="https://example.com/image.png"/></div></a>',
				'figure_class' => 'alignleft',
				'inner_code' => '<a href="https://example.com/image.png"><div class="kadence-blocks-gallery-item"><img src="https://example.com/image.png"/></div></a>',
				'img_src' => 'https://example.com/image.png',
			],
		];
		$actual   = $this->html_analyzer->extract_images_from_html( $markup );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test multiple nested div containers
	 */
	public function test_public_caption_regex_nested_divs() {
		$markup = '<figure class="my-figure"><a href="https://example.com"><div><div><img src="https://example.com/image.jpg" alt="test image" /></div></div></a></figure>';
		$expected = [
			[
				'full' => '<figure class="my-figure"><a href="https://example.com"><div><div><img src="https://example.com/image.jpg" alt="test image" /></div></div></a>',
				'figure_class' => 'my-figure',
				'inner_code' => '<a href="https://example.com"><div><div><img src="https://example.com/image.jpg" alt="test image" /></div></div></a>',
				'img_src' => 'https://example.com/image.jpg',
			],
		];
		$actual   = $this->html_analyzer->extract_images_from_html( $markup );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test mismatching DIVs
	 */
	public function test_public_caption_regex_mismatching_divs() {
		$markup = '<figure class="my-figure"><a href="https://example.com"><div><div><img src="https://example.com/image.jpg" alt="test image" /></div></a></div></figure>';
		$expected = [
			[
				'full' => '<figure class="my-figure"><a href="https://example.com"><div><div><img src="https://example.com/image.jpg" alt="test image" /></div></a>',
				'figure_class' => 'my-figure',
				'inner_code' => '<a href="https://example.com"><div><div><img src="https://example.com/image.jpg" alt="test image" /></div></a>',
				'img_src' => 'https://example.com/image.jpg',
			],
		];
		$actual   = $this->html_analyzer->extract_images_from_html( $markup );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test taken from Disable_Overlay_Class_Test
	 * @return void
	 */
	public function test_remove_overlay_from_isc_disable_overlay_class() {
		$markup   = '<figure class="alignleft isc-disable-overlay"><a href="https://example.com/page.png"><div class="kadence-blocks-gallery-item"><img src="https://example.com/image.png"/></div></a></figure><figure class="alignright"><a href="https://example.com/page2.png"><div class="kadence-blocks-gallery-item"><img src="https://example.com/image2.png"/></div></a></figure><figure class="aligncenter"><a href="https://example.com/page3.png"><div class="kadence-blocks-gallery-item"><img src="https://example.com/image3.png" class="isc-disable-overlay"/></div></a></figure>';
		$expected = [
			[
				'full'         => '<figure class="alignright"><a href="https://example.com/page2.png"><div class="kadence-blocks-gallery-item"><img src="https://example.com/image2.png"/></div></a>',
				'figure_class' => 'alignright',
				'inner_code'   => '<a href="https://example.com/page2.png"><div class="kadence-blocks-gallery-item"><img src="https://example.com/image2.png"/></div></a>',
				'img_src'      => 'https://example.com/image2.png',

			],
		];
		// run the filter ISC_Pro_Public::remove_overlay_from_isc_disable_overlay_class() manually
		$pro_public = new \ISC_Pro_Public();
		add_filter( 'isc_extract_images_from_html', [ $pro_public, 'remove_overlay_from_isc_disable_overlay_class' ], 10 );
		$actual = $this->html_analyzer->extract_images_from_html( $markup );
		$this->assertEquals( $expected, $actual );
	}
}