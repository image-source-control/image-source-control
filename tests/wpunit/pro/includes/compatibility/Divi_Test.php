<?php

namespace ISC\Tests\WPUnit\Pro\Compatibility;

use ISC\Pro\Compatibility\Divi;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test the Divi compatibility class for frontend features.
 *
 * @package ISC\Tests\WPUnit\Pro\Compatibility
 */
class DiviTest extends WPTestCase {

	/**
	 * Instance of the Divi class under test.
	 * @var Divi
	 */
	protected Divi $divi;

	/**
	 * Set up the test environment before each test.
	 */
	public function _before() {
		parent::_before();

		// Mock Divi's existence by defining its constant.
		if ( ! defined( 'ET_SHORTCODES_VERSION' ) ) {
			define( 'ET_SHORTCODES_VERSION', '4.20' );
		}
	}

	/**
	 * Helper function to create a mock ET_Builder_Element instance.
	 *
	 * @param string|null $image_url The background image URL to set.
	 *
	 * @return object
	 */
	protected function create_mock_module( ?string $image_url = null ): object {
		$module = new \stdClass();
		$module->props = [];
		if ( $image_url ) {
			$module->props['background_image'] = $image_url;
		}
		return $module;
	}

	/**
	 * Test that the filter modifies the row output when a valid background image is set.
	 */
	public function test_add_row_attributes_and_class_with_valid_image() {
		// 1. Arrange
		$this->divi      = new Divi(); // Instantiate for this test.
		$image_id        = $this->factory()->attachment->create_upload_object( codecept_data_dir( 'test-image1.jpg' ) );
		$image_url       = wp_get_attachment_url( $image_id );
		$mock_module     = $this->create_mock_module( $image_url );
		$original_output = '<div class="et_pb_row et_pb_row_0 et_pb_equal_columns"></div>';

		// 2. Act
		$filtered_output = $this->divi->add_module_attributes_and_class( $original_output, 'et_pb_row', $mock_module );

		// 3. Assert
		$this->assertStringContainsString( 'data-isc-images="' . $image_id . '"', $filtered_output, 'The data-isc-images attribute was not added.' );
		$this->assertStringContainsString( 'class="isc-source et_pb_row', $filtered_output, 'The isc-source class was not added correctly.' );
	}

	/**
	 * Test that the filter does not modify the output if the background image URL is invalid.
	 */
	public function test_add_row_attributes_and_class_with_invalid_image_url() {
		// 1. Arrange
		$this->divi      = new Divi(); // Instantiate for this test.
		$mock_module     = $this->create_mock_module( 'https://example.com/non-existent-image.jpg' );
		$original_output = '<div class="et_pb_row et_pb_row_0 et_pb_equal_columns"></div>';

		// 2. Act
		$filtered_output = $this->divi->add_module_attributes_and_class( $original_output, 'et_pb_row', $mock_module );

		// 3. Assert
		$this->assertEquals( $original_output, $filtered_output, 'Output should not be modified for an invalid image URL.' );
	}

	/**
	 * Test that the front_head method outputs the correct CSS styles.
	 */
	public function test_front_head_outputs_styles() {
		// 1. Arrange
		$this->divi = new Divi(); // Instantiate for this test.
		$expected_style = '<style>
			.et_pb_row.isc-source,.et_pb_section.isc-source{display:inherit;}
			.et_pb_fullwidth_image .isc-source { display: inherit; }
			.isc_image_has_fullwidth.et_pb_image .isc-source { position: revert; display: revert; }
		</style>';

		// 2. Act
		ob_start();
		$this->divi->front_head();
		$output = ob_get_clean();

		// 3. Assert
		$this->assertEquals( preg_replace( '/\s+/', '', $expected_style ), preg_replace( '/\s+/', '', $output ) );
	}

	/**
	 * Test that frontend hooks are correctly registered and applied when the feature is enabled.
	 */
	public function test_hooks_are_applied_when_feature_is_enabled() {
		// 1. Arrange
		update_option( 'isc_options', [
			'enable_image_source_overlay' => true,
			'divi_background_images'      => true,
		] );

		$this->divi = new Divi();
		$this->divi->register_hooks();

		$image_id        = $this->factory()->attachment->create_upload_object( codecept_data_dir( 'test-image2.jpg' ) );
		$image_url       = wp_get_attachment_url( $image_id );
		$mock_module     = $this->create_mock_module( $image_url );
		$original_output = '<div class="et_pb_row"></div>';

		// 2. Act & Assert for the filter
		$filtered_output = apply_filters( 'et_module_shortcode_output', $original_output, 'et_pb_row', $mock_module );
		$this->assertStringContainsString( 'data-isc-images="' . $image_id . '"', $filtered_output );
		$this->assertStringContainsString( 'class="isc-source et_pb_row', $filtered_output );

		// 3. Act & Assert for the action
		ob_start();
		do_action( 'wp_head' );
		$head_output = ob_get_clean();
		$this->assertStringContainsString( '.et_pb_row.isc-source, .et_pb_section.isc-source { display: inherit; }', $head_output );
	}

	/**
	 * Test that frontend hooks are not applied if the specific Divi option is disabled.
	 */
	public function test_hooks_are_not_applied_when_divi_option_is_disabled() {
		// 1. Arrange
		update_option( 'isc_options', [
			'enable_image_source_overlay' => true,
			'divi_background_images'      => false,
		] );

		// Instantiate the class to add its method to the 'init' hook.
		$this->divi = new Divi();
		$this->divi->register_hooks();

		$mock_module     = $this->create_mock_module( 'https://example.com/any.jpg' );
		$original_output = '<div class="et_pb_row"></div>';

		// 2. Act & Assert for the filter
		$filtered_output = apply_filters( 'et_pb_row_shortcode_output', $original_output, 'et_pb_row', $mock_module );
		$this->assertEquals( $original_output, $filtered_output, 'Filter should not be applied when divi_background_images is false.' );

		// 3. Act & Assert for the action
		ob_start();
		do_action( 'wp_head' );
		$head_output = ob_get_clean();
		$this->assertStringNotContainsString( '.et_pb_row.isc-source { display: inherit; }', $head_output, 'Style should not be added when divi_background_images is false.' );
	}
}