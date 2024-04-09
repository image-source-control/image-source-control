<?php

namespace ISC\Tests\WPUnit;

use \ISC_Public;

/**
 * Test if the isc_caption_apply_default_style filter hook reacts when a pro caption style is used
 */
class Filter_Caption_Apply_Default_Style_Test extends \Codeception\TestCase\WPTestCase {
	/**
	 * Test the filter in ISC\Pro\Caption::use_default_caption_style() for the hover caption layout
	 */
	public function test_use_default_caption_style_hover() {
		$options = ISC_Public::get_instance()->default_options();
		$options['caption_style'] = 'hover';
		update_option( 'isc_options', $options );

		$result = apply_filters( 'isc_caption_apply_default_style', '__return_true' );
		$this->assertFalse( $result, 'The isc_caption_apply_default_style filter returned true despite using the hover caption style' );
	}

	/**
	 * Test the filter in ISC\Pro\Caption::use_default_caption_style() for the click caption layout
	 */
	public function test_use_default_caption_style_click() {
		$options = ISC_Public::get_instance()->default_options();
		$options['caption_style'] = 'click';
		update_option( 'isc_options', $options );

		$result = apply_filters( 'isc_caption_apply_default_style', '__return_true' );
		$this->assertFalse( $result, 'The isc_caption_apply_default_style filter returned true despite using the click caption style' );
	}
}