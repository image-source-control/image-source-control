<?php

namespace ISC\Tests\WPUnit;

use \ISC_Model;
use \ISC_Pro_Public;

/**
 * Test if ISC_Pro_Public::remove_overlay_from_isc_disable_overlay_class() removes images with the isc-disable-overlay class from the content.
 */
class Disable_Overlay_Class_Test extends \Codeception\TestCase\WPTestCase {
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
		add_filter( 'isc_extract_images_from_html', [ 'ISC_Pro_Public', 'remove_overlay_from_isc_disable_overlay_class' ], 10 );
		$actual = ISC_Model::extract_images_from_html( $markup );
		$this->assertEquals( $expected, $actual );
	}
}
