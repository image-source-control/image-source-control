<?php

namespace ISC\Tests\WPUnit\Pro\Pblc\Caption;

use ISC\Tests\WPUnit\WPTestCase;
use ISC\Options;

/**
 * Test if the isc_caption_apply_default_style filter hook reacts when a pro caption style is used
 */
class Filter_Caption_Apply_Default_Style_Test extends WPTestCase {
	/**
	 * Test the filter in ISC\Pro\Caption::use_default_caption_style() for the hover caption layout
	 */
	public function test_use_default_caption_style_hover() {
		$options = Options::default_options();
		$options['caption_style'] = 'hover';
		update_option( 'isc_options', $options );

		$result = apply_filters( 'isc_caption_apply_default_style', '__return_true' );
		$this->assertFalse( $result, 'The isc_caption_apply_default_style filter returned true despite using the hover caption style' );
	}

	/**
	 * Test the filter in ISC\Pro\Caption::use_default_caption_style() for the click caption layout
	 */
	public function test_use_default_caption_style_click() {
		$options = Options::default_options();
		$options['caption_style'] = 'click';
		update_option( 'isc_options', $options );

		$result = apply_filters( 'isc_caption_apply_default_style', '__return_true' );
		$this->assertFalse( $result, 'The isc_caption_apply_default_style filter returned true despite using the click caption style' );
	}
}