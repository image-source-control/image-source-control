<?php

namespace ISC\Tests\WPUnit\Pblc\Captions;

use ISC\Tests\WPUnit\WPTestCase;
use ISC\Plugin;

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
	}

	/**
	 * Test if the frontend scripts and head output are based on the caption_style option
	 *
	 * @dataProvider captionStyleProvider
	 */
	public function test_script_and_head_output_based_on_caption_style( $caption_style, $should_enqueue, $expected_in_head ) {
		global $wp_scripts;

		$this->setCaptionStyle($caption_style);
		$head_content = $this->runHooksAndCaptureHead();

		if ( $should_enqueue ) {
			$this->assertContains( 'isc_caption', $wp_scripts->queue, 'Script should be enqueued' );
		} else {
			$this->assertNotContains( 'isc_caption', $wp_scripts->queue, 'Script should not be enqueued' );
		}

		if ( $expected_in_head ) {
			$this->assertStringContainsString( 'var isc_front_data', $head_content, 'Frontend data should be printed' );
		} else {
			$this->assertStringNotContainsString( 'var isc_front_data', $head_content, 'Frontend data should not be printed' );
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
		$options = Plugin::default_options();
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
		parent::tearDown();
	}
}