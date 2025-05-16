<?php

namespace ISC\Tests\WPUnit\Pro\Includes;

use ISC\Pro\List_Layout_Details;
use ISC_Public;
use ISC\Tests\WPUnit\WPTestCase;

class List_Layout_Details_Test extends WPTestCase {

	protected $list_layout_details_instance;

	public function setUp(): void {
		parent::setUp();

		// Initialize with the feature off by default for tests
		update_option( 'isc_options', array_merge( \ISC\Plugin::get_options(), [
			//'enable_image_sources' => true, // Ensure the main module is on
			'image_list_headline'  => 'Test Headline',
			'list_layout'          => [ 'details' => false ],
		] ) );

		// Instantiate the class responsible for the <details> layout
		$this->list_layout_details_instance = new List_Layout_Details();
	}

	public function tearDown(): void {
		// Clean up options
		delete_option( 'isc_options' );

		remove_filter( 'isc_image_list_box_tag', [ $this->list_layout_details_instance, 'change_tag_to_details_for_testing' ], 10 );
		remove_filter( 'isc_render_image_source_box', [ $this->list_layout_details_instance, 'render_image_source_box' ], 10 );

		parent::tearDown();
	}

	/**
	 * Helper to activate or deactivate the 'details' layout option
	 * and ensure hooks are registered/deregistered accordingly.
	 */
	private function set_details_layout_active( bool $active ) {
		$options                           = (array) get_option( 'isc_options' );
		$options['list_layout']['details'] = $active;
		update_option( 'isc_options', $options );

		// Manually call register_hooks() to simulate the 'wp' action
		// based on the current option state. This is crucial because
		// the hooks are conditional on this option.
		$this->list_layout_details_instance->register_hooks();
	}

	/**
	 * Test if the details layout option is inactive
	 * this is the default state and should output the <div> layout
	 *
	 * @return void
	 */
	public function test_frontend_renders_base_div_layout_when_option_is_inactive() {
		$public_base_instance = ISC_Public::get_instance();
		$test_content         = '<ul><li>Source Item for Div</li></ul>';

		$output = $public_base_instance->render_image_source_box( $test_content, false );

		$this->assertStringContainsString( '<div class="isc_image_list_box">', $output, "Output should contain base <div> tag." );
		$this->assertStringContainsString( '<p class="isc_image_list_title">Test Headline</p>', $output, "Output should contain base <p> headline." );
		$this->assertStringContainsString( $test_content, $output, "Output should contain the provided content." );
		$this->assertStringContainsString( '</div>', $output, "Output should close with </div> tag." );
		$this->assertStringNotContainsString( '<details', $output, "Output should NOT contain <details> tag when option is inactive." );
		$this->assertStringNotContainsString( '<summary', $output, "Output should NOT contain <summary> tag when option is inactive." );
	}

	/**
	 * Test if the details layout option is active
	 *
	 * @return void
	 */
	public function test_frontend_renders_details_layout_when_option_is_active() {
		$this->set_details_layout_active( true );
		$public_base_instance = ISC_Public::get_instance();
		$test_content         = '<ul><li>Source Item for Details</li></ul>';

		$output = $public_base_instance->render_image_source_box( $test_content );

		$this->assertStringContainsString( '<details class="isc_image_list_box">', $output, "Output should contain <details> tag." );
		$this->assertStringContainsString( '<summary class="isc_image_list_title">Test Headline</summary>', $output, "Output should contain <summary> with headline." );
		$this->assertStringContainsString( $test_content, $output, "Output should contain the provided content." );
		$this->assertStringContainsString( '</details>', $output, "Output should close with </details> tag." );
		$this->assertStringNotContainsString( '<div class="isc_image_list_box">', $output, "Output should NOT contain base <div> wrapper when details is active." );
		$this->assertStringNotContainsString( '<p class="isc_image_list_title">', $output, "Output should NOT contain base <p> headline when details is active." );
	}

	/**
	 * Test if the <div> placeholder without content shows up if the details layout option is inactive
	 *
	 * @return void
	 */
	public function test_frontend_renders_base_div_placeholder_when_option_is_inactive() {
		$public_base_instance = ISC_Public::get_instance();
		$test_content         = '<ul><li>Source Item for Details</li></ul>';

		// this is the line with the relevant change setting the second parameter to true
		$output = $public_base_instance->render_image_source_box( $test_content, true );

		$this->assertStringContainsString( '<div class="isc_image_list_box">', $output, "Placeholder should use base <div> tag." );
		$this->assertStringNotContainsString( '<p class="isc_image_list_title">', $output, "Placeholder should not have a <p> headline." ); // As per base plugin's placeholder logic
		$this->assertMatchesRegularExpression( '/<div class="isc_image_list_box">\s*<\/div>/s', $output, "Placeholder should be an empty <div> tag." );
	}

	/**
	 * Test if the <details> placeholder without contant shows up if the details layout option is active
	 *
	 * @return void
	 */
	public function test_frontend_renders_details_placeholder_when_option_is_active() {
		$this->set_details_layout_active( true );
		$public_base_instance = ISC_Public::get_instance();
		$test_content         = '<ul><li>Source Item for Details</li></ul>';

		// this is the line with the relevant change setting the second parameter to true
		$output = $public_base_instance->render_image_source_box( $test_content, true );

		$this->assertStringContainsString( '<details class="isc_image_list_box">', $output, "Placeholder should use <details> tag." );
		$this->assertStringNotContainsString( '<summary', $output, "Placeholder should not have a <summary>." );
		$this->assertMatchesRegularExpression( '/<details class="isc_image_list_box">\s*<\/details>/s', $output, "Placeholder should be an empty <details> tag." );
	}
}