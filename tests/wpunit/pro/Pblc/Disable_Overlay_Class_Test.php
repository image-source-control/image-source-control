<?php

namespace ISC\Tests\WPUnit\Pro\Pblc;

use ISC\Image_Sources\Analyze_HTML;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test if ISC_Pro_Public::remove_overlay_from_isc_disable_overlay_class() removes images with the isc-disable-overlay class from the content.
 */
class Disable_Overlay_Class_Test extends WPTestCase {

	/**
	 * Helper to extract information from HTML
	 *
	 * @var Analyze_HTML
	 */
	public $html_analyzer;

	public function setUp() : void {
		parent::setUp();
		$this->html_analyzer = new Analyze_HTML();
	}

	/**
	 * Test if remove_overlay_from_isc_disable_overlay_class() removes images with the isc-disable-overlay class from the content.
	 * The markup contains three images, two of them have the isc-disable-overlay class somewhere. So only one image should be returned.
	 */
	public function test_remove_overlay_from_isc_disable_overlay_class() {
		$markup   = '<figure class="alignleft isc-disable-overlay"><img src="https://example.com/image.png"/></figure><figure class="alignright"><img src="https://example.com/image2.png"/></figure><figure class="aligncenter"><img src="https://example.com/image3.png" class="isc-disable-overlay"/></figure>';
		$expected = [
			[
				'full'         => '<figure class="alignright"><img src="https://example.com/image2.png"/>',
				'figure_class' => 'alignright',
				'inner_code'   => '<img src="https://example.com/image2.png"/>',
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
