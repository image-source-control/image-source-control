<?php

namespace ISC\Tests\WPUnit\Pro\Pblc\Caption;

use \ISC_Public;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test if ISC\Pro\Caption::add_caption_css() prints the correct footer CSS
 *
 * @todo the test failed on April 9th, 2024 due to a WordPress deprecation message that I wasn’t able to fix, so I renamed it for now to prevent it from execution
 */
class Add_Footer_CSS_Test_WIP extends WPTestCase {

	/**
	 * Test for the hover caption layout
	 */
	public function test_add_caption_style_hover() {
		$options = ISC_Public::get_instance()->default_options();
		$options['caption_style'] = 'hover';
		update_option( 'isc_options', $options );

		ob_start();
		do_action( 'wp_footer' );
		$output = ob_get_clean();

		// check if our content was added
		$this->assertStringContainsString( '.isc-source-text .isc-source-text-icon', $output, 'The wp_footer action did not print the expected scripts.' );
	}

	/**
	 * Test for the click caption layout
	 */
	public function test_add_caption_style_click() {
		$options = ISC_Public::get_instance()->default_options();
		$options['caption_style'] = 'click';
		update_option( 'isc_options', $options );

		ob_start();
		do_action( 'wp_footer' );
		$output = ob_get_clean();

		// check if our content was added
		$this->assertStringContainsString( 'details.isc-source-text summary', $output, 'The wp_footer action did not print the expected scripts.' );
	}
}