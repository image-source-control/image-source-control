<?php

namespace ISC\Tests\WPUnit\Pblc\Captions;

use ISC\Tests\WPUnit\WPTestCase;
use ISC\Options;

/**
 * Test if the functions front_scripts and front_head are hooked in/out based on the caption_style option
 */
class Caption_Style_Option_Test extends WPTestCase {

	/**
	 * Default options
	 */
	protected $options;

	/**
	 * Setup the test environment
	 */
	protected function setUp(): void {
		parent::setUp();
		// Reset the wp_scripts global
		global $wp_scripts;
		$wp_scripts = null;

		set_current_screen( 'front' );
	}

	/**
	 * Test if the frontend scripts and head output are based on the caption_style option
	 *
	 * @dataProvider captionStyleProvider
	 */
	public function test_script_and_head_output_based_on_caption_style( $caption_style, $should_enqueue, $expected_data_and_styles ) {
		global $wp_scripts;

		$this->setCaptionStyle($caption_style);
		$head_content = $this->runHooksAndCaptureHead();

		if ( $should_enqueue ) {
			$this->assertContains( 'isc_caption', $wp_scripts->queue, 'Script should be enqueued' );
		} else {
			$this->assertNotContains( 'isc_caption', $wp_scripts->queue, 'Script should not be enqueued' );
		}

		$localized_data_string = $wp_scripts->get_data( 'isc_caption', 'data' );
		if ( $expected_data_and_styles ) {
			$this->assertNotEmpty( $localized_data_string, 'Localized data string should exist for "isc_caption".' );
			$this->assertStringContainsString( 'var isc_front_data =', $localized_data_string, 'Localized data string should define "isc_front_data".' );
			$this->assertStringContainsString( 'caption_position', $localized_data_string, '"caption_position" should be in localized data.' );

			$this->assertStringNotContainsString( 'var isc_front_data', $head_content, '"isc_front_data" should NOT be printed directly in wp_head anymore.' );

			$this->assertStringContainsString( '.isc-source {', $head_content, 'Caption CSS styles should be in head when captions are active.' );
		} else {
			$this->assertEmpty( $localized_data_string, 'Localized data string should NOT exist for "isc_caption" when captions are inactive.' );

			$this->assertStringNotContainsString( 'var isc_front_data', $head_content, '"isc_front_data" should not be printed in wp_head.' );

			$this->assertStringNotContainsString( '.isc-source {', $head_content, 'Caption CSS styles should NOT be in head when captions are inactive.' );
		}
	}

	/**
	 * Iterate over different caption style options
	 *
	 * @return array[]
	 */
	public function captionStyleProvider() {
		return [
			'empty style'     => [ '', true, true ],
			'random style'    => [ 'oujdo98', true, true ],
			'none style'      => [ 'none', false, false ],
		];
	}

	protected function setCaptionStyle( $style ) {
		$options = Options::default_options();
		$options['caption_style'] = $style;
		update_option( 'isc_options', $options );
	}

	protected function runHooksAndCaptureHead() {
		do_action( 'wp' );
		do_action( 'wp_enqueue_scripts' );
		ob_start();
		do_action( 'wp_head' );
		return ob_get_clean();
	}

	protected function tearDown(): void {
		delete_option( 'isc_options' );
		// Reset the wp_scripts global
		global $wp_scripts;
		$wp_scripts = null;

		remove_all_actions( 'wp' );
		remove_all_actions( 'wp_enqueue_scripts' );
		remove_all_actions( 'wp_head' );

		parent::tearDown();
	}
}