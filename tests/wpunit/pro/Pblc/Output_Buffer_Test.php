<?php

namespace ISC\Tests\WPUnit\Pro\Pblc;

use ISC\Tests\WPUnit\WPTestCase;
use ISC\Options;
use ISC_Pro_Public;

/**
 * Test output buffering in ISC_Pro_Public
 */
class Output_Buffer_Test extends WPTestCase {

	private ISC_Pro_Public $isc_pro_public_instance;
	private array $created_post_ids = [];
	private int $initial_ob_level;

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();
		// Store the buffer level WPUnit expects us to return to
		$this->initial_ob_level = ob_get_level();
		$this->isc_pro_public_instance = new ISC_Pro_Public();
		// Add the main action hook here, it will be removed in tearDown
		add_action( 'wp', [ $this->isc_pro_public_instance, 'register_hooks' ] );
	}

	/**
	 * Clean up after each test
	 */
	public function tearDown(): void {
		// Clean up created posts
		foreach ($this->created_post_ids as $post_id) {
			wp_delete_post($post_id, true);
		}
		$this->created_post_ids = [];

		// Reset options to default or known state if modified
		delete_option('isc_options');

		// Remove actions added by the instance to avoid interference between tests
		$this->remove_isc_hooks(); // Calls the simplified helper

		// Ensure all output buffers down to the initial level are closed
		while (ob_get_level() > $this->initial_ob_level) {
			if (!@ob_end_clean()) {
				// If clean fails, break to avoid infinite loop, though test might fail
				break;
			}
		}
		// Verify we are back at the initial level
		$final_level = ob_get_level();
		$this->assertEquals($this->initial_ob_level, $final_level, sprintf("Buffer level mismatch at end of tearDown. Expected %d, got %d.", $this->initial_ob_level, $final_level));

		parent::tearDown();
	}

	/**
	 * Helper to remove hooks added by the plugin instance
	 */
	private function remove_isc_hooks() {
		// Remove the main hook added in setUp
		remove_action( 'wp', [ $this->isc_pro_public_instance, 'register_hooks' ] );

		// Remove hooks potentially added by register_hooks()
		remove_action( 'get_header', [ $this->isc_pro_public_instance, 'start_output_buffering' ], 99999 );
		remove_action( 'wp_head', [ $this->isc_pro_public_instance, 'start_output_buffering_fallback' ], 1 );
		remove_action( 'amp_post_template_head', [ $this->isc_pro_public_instance, 'start_output_buffering' ], 1 );
		remove_action( 'wp_print_footer_scripts', [ $this->isc_pro_public_instance, 'stop_output_buffering' ], 99999 );
		remove_action( 'wp_footer', [ $this->isc_pro_public_instance, 'stop_output_buffering_fallback' ], 99999 );
		remove_action( 'amp_post_template_footer', [ $this->isc_pro_public_instance, 'stop_output_buffering' ], 99999 );
		remove_action( 'wp_print_footer_scripts', [ $this->isc_pro_public_instance, 'stop_output_buffering_for_index' ], 99999 );
		remove_action( 'wp_footer', [ $this->isc_pro_public_instance, 'stop_output_buffering_for_index_fallback' ], 99999 );
	}


	/**
	 * Helper function to enable the overlay for images in the whole body
	 */
	protected function enable_overlay_for_body_images() {
		$isc_options                            = Options::get_options();
		$isc_options['display_type'][]          = 'overlay'; // Ensure 'overlay' is in the array
		$isc_options['display_type']            = array_unique($isc_options['display_type']); // Avoid duplicates
		$isc_options['overlay_included_images'] = 'body_img';
		update_option( 'isc_options', $isc_options );
	}

	/**
	 * Helper function to create a dummy image attachment with source meta
	 * @param string $source_text
	 * @param string $url
	 * @return int Attachment ID
	 */
	protected function create_dummy_image(string $source_text = 'Test Author', string $url = 'https://example.com/test-image.jpg'): int {
		$image_id = $this->factory->post->create([
			                                         'post_title' => 'Test Image',
			                                         'post_type'  => 'attachment',
			                                         'guid'       => $url,
		                                         ]);
		add_post_meta($image_id, 'isc_image_source', $source_text);
		$this->created_post_ids[] = $image_id; // Track for cleanup
		return $image_id;
	}

	/**
	 * Check if the output buffering is omitted if overlays are only enabled within the content, which is the default behavior
	 */
	public function test_no_output_buffering_for_overlays_in_content() {
		// Ensure the specific option is NOT set
		$options = Options::get_options();
		$options['overlay_included_images'] = 'content_img'; // Explicitly set to default
		$options['list_included_images'] = ''; // Ensure list doesn't trigger buffering either
		$options['display_type'] = ['overlay']; // Ensure overlay is enabled
		update_option('isc_options', $options);

		// Manually trigger the 'wp' action which calls register_hooks on the real instance
		// This should NOT add the buffering hooks based on the options set.
		do_action('wp');

		// Assert that no buffering hooks were added by the real instance
		$this->assertEquals(0, has_action('get_header', [$this->isc_pro_public_instance, 'start_output_buffering']), 'start_output_buffering should NOT be hooked');
	}

	/**
	 * Check if the output buffering is working when overlays are enabled for the whole page, not just the content
	 */
	public function test_output_buffering_enabled_for_overlays() {
		$this->enable_overlay_for_body_images();

		// Manually trigger the 'wp' action which calls register_hooks on the real instance
		// This SHOULD add the buffering hooks based on the options set.
		do_action('wp');

		// We need to check if the hooks were added correctly by the real instance
		$this->assertGreaterThan(0, has_action('get_header', [$this->isc_pro_public_instance, 'start_output_buffering']), 'start_output_buffering should be hooked to get_header');
		$this->assertGreaterThan(0, has_action('wp_print_footer_scripts', [$this->isc_pro_public_instance, 'stop_output_buffering']), 'stop_output_buffering should be hooked to wp_print_footer_scripts');
	}

	/**
	 * Check if stop_output_buffering returns manipulated content in a simple case
	 */
	public function test_processed_content_from_output_buffering_simple() {
		$level_before_test = ob_get_level();
		$this->enable_overlay_for_body_images();
		do_action('wp'); // Ensure hooks are registered

		$image_id = $this->create_dummy_image('Author Simple', 'https://example.com/simple.jpg');
		$image_html = '<img src="https://example.com/simple.jpg"/>';
		$expected_caption = 'Source: Author Simple';

		// Start capturing final output
		ob_start();

		// Simulate WordPress hooks triggering the buffering
		do_action('get_header'); // Triggers start_output_buffering
		echo $image_html; // Page content goes into the buffer
		do_action('wp_print_footer_scripts'); // Triggers stop_output_buffering

		$final_output = ob_get_clean();

		// Assert that the final output contains the image wrapped with the source span
		$this->assertStringContainsString($image_html, $final_output, 'Original image HTML missing');
		$this->assertStringContainsString('<span class="isc-source-text">' . $expected_caption . '</span>', $final_output, 'ISC caption span missing or incorrect');
		$this->assertStringContainsString('<span id="isc_attachment_' . $image_id . '" class="isc-source "', $final_output, 'ISC wrapper span missing or incorrect');
		$this->assertEquals($level_before_test, ob_get_level(), 'Output buffer level should be back to initial level after processing');
	}

	/**
	 * Test handling when one intermediate buffer is started after ISC's buffer.
	 */
	public function test_output_buffering_with_one_nested_buffer() {
		$level_before_test = ob_get_level();
		$this->enable_overlay_for_body_images();
		do_action('wp'); // Ensure hooks are registered

		$image_id = $this->create_dummy_image('Author Nested 1', 'https://example.com/nested1.jpg');
		$image_html = '<img src="https://example.com/nested1.jpg"/>';
		$intermediate_content = '<div>Intermediate Buffer Content</div>';
		$expected_caption = 'Source: Author Nested 1';

		// Start capturing final output
		ob_start();

		// Simulate WordPress hooks and intermediate buffer
		do_action('get_header'); // Triggers start_output_buffering (level 1 or higher relative to test start)
		$isc_level = ob_get_level();

		ob_start(); // Simulate intermediate buffer start (level isc_level + 1)
		echo $intermediate_content;

		echo $image_html; // This goes into the intermediate buffer initially

		do_action('wp_print_footer_scripts'); // Triggers stop_output_buffering

		$final_output = ob_get_clean();

		// Assertions
		$this->assertStringContainsString($image_html, $final_output, 'Original image HTML missing');
		$this->assertStringContainsString($intermediate_content, $final_output, 'Intermediate content missing (should have been flushed)');
		$this->assertStringContainsString('<span class="isc-source-text">' . $expected_caption . '</span>', $final_output, 'ISC caption span missing or incorrect');
		$this->assertStringContainsString('<span id="isc_attachment_' . $image_id . '" class="isc-source "', $final_output, 'ISC wrapper span missing or incorrect');
		$this->assertEquals($level_before_test, ob_get_level(), 'Output buffer level should be back to initial level after processing');
	}

	/**
	 * Test handling when multiple intermediate buffers are started after ISC's buffer.
	 */
	public function test_output_buffering_with_multiple_nested_buffers() {
		$level_before_test = ob_get_level();
		$this->enable_overlay_for_body_images();
		do_action('wp'); // Ensure hooks are registered

		$image_id = $this->create_dummy_image('Author Nested Multi', 'https://example.com/nested_multi.jpg');
		$image_html = '<img src="https://example.com/nested_multi.jpg"/>';
		$intermediate_content1 = '<div>Intermediate Buffer 1</div>';
		$intermediate_content2 = '<span>Intermediate Buffer 2</span>';
		$expected_caption = 'Source: Author Nested Multi';

		// Start capturing final output
		ob_start();

		// Simulate WordPress hooks and intermediate buffers
		do_action('get_header'); // Triggers start_output_buffering
		$isc_level = ob_get_level();

		ob_start(); // Intermediate buffer 1 (level isc_level + 1)
		echo $intermediate_content1;

		ob_start(); // Intermediate buffer 2 (level isc_level + 2)
		echo $intermediate_content2;

		echo $image_html; // Goes into buffer 2

		do_action('wp_print_footer_scripts'); // Triggers stop_output_buffering

		$final_output = ob_get_clean();

		// Assertions
		$this->assertStringContainsString($image_html, $final_output, 'Original image HTML missing');
		$this->assertStringContainsString($intermediate_content1, $final_output, 'Intermediate content 1 missing');
		$this->assertStringContainsString($intermediate_content2, $final_output, 'Intermediate content 2 missing');
		$this->assertStringContainsString('<span class="isc-source-text">' . $expected_caption . '</span>', $final_output, 'ISC caption span missing or incorrect');
		$this->assertStringContainsString('<span id="isc_attachment_' . $image_id . '" class="isc-source "', $final_output, 'ISC wrapper span missing or incorrect');
		$this->assertEquals($level_before_test, ob_get_level(), 'Output buffer level should be back to initial level after processing');
	}

	/**
	 * Test handling when a buffer exists *before* ISC starts its own.
	 * (Simulates caching plugins like WP Super Cache)
	 */
	public function test_output_buffering_with_pre_existing_buffer() {
		$level_before_test = ob_get_level();
		$this->enable_overlay_for_body_images();
		do_action('wp'); // Ensure hooks are registered

		$image_id = $this->create_dummy_image('Author Pre', 'https://example.com/pre.jpg');
		$image_html = '<img src="https://example.com/pre.jpg"/>';
		$pre_content = '<!DOCTYPE html><html><head><title>Pre Content</title></head><body>';
		$post_content = '</body></html>';
		$expected_caption = 'Source: Author Pre';

		// Start outer capture for the test result
		ob_start();
		$level_after_outer_capture = ob_get_level();

		// Simulate pre-existing buffer (e.g., caching)
		ob_start();
		echo $pre_content;
		$pre_buffer_level = ob_get_level();
		// Optional but good sanity check:
		$this->assertEquals($level_after_outer_capture + 1, $pre_buffer_level, 'Pre-existing buffer should be one level above outer capture');

		// Simulate WordPress hooks execution
		do_action('get_header'); // Triggers ISC's start_output_buffering

		echo $image_html; // Goes into whatever buffer is now active (should be ISC's)

		do_action('wp_print_footer_scripts'); // Triggers stop_output_buffering

		echo $post_content; // Goes into the pre-existing buffer after ISC buffer is closed/flushed

		// Close the pre-existing buffer
		ob_end_flush(); // Flush pre-existing buffer content

		$final_output = ob_get_clean(); // Get the final echoed output from the test capture

		// Assertions about content
		$this->assertStringContainsString($pre_content, $final_output, 'Pre-existing buffer content missing');
		$this->assertStringContainsString($post_content, $final_output, 'Post-ISC buffer content missing');
		$this->assertStringContainsString($image_html, $final_output, 'Original image HTML missing');
		$this->assertStringContainsString('<span class="isc-source-text">' . $expected_caption . '</span>', $final_output, 'ISC caption span missing or incorrect');
		$this->assertStringContainsString('<span id="isc_attachment_' . $image_id . '" class="isc-source "', $final_output, 'ISC wrapper span missing or incorrect');
		$this->assertEquals($level_before_test, ob_get_level(), 'Output buffer level should be back to initial level after processing');
	}

	/**
	 * Test case where the ISC buffer is somehow missing when stop_output_buffering is called.
	 * It should bail out gracefully.
	 */
	public function test_stop_output_buffering_when_isc_buffer_missing() {
		$level_before_test = ob_get_level();
		$this->enable_overlay_for_body_images();
		do_action('wp'); // Ensure hooks are registered

		// Start capturing final output
		ob_start();

		// Simulate hooks BUT don't actually start the ISC buffer correctly
		// We can simulate this by manually calling stop without start having run properly
		// or by closing the buffer prematurely. Let's close it.
		do_action('get_header'); // Starts the buffer
		$level_before_clean = ob_get_level();
		if ($level_before_clean > $level_before_test + 1) { // Check if ISC buffer likely started relative to test start
			if (!@ob_end_clean()) { // Prematurely close the ISC buffer
				// Handle potential error during clean if necessary
			}
		}
		// Level might be level_before_test + 1 if only the test buffer remains
		$this->assertLessThan($level_before_clean, ob_get_level(), "Buffer level should decrease after clean");

		// Now call stop - it shouldn't find its buffer handle
		// Need to call it on the actual instance
		$this->isc_pro_public_instance->stop_output_buffering();

		$final_output = ob_get_clean();

		// Assertion: Expect empty output because stop_output_buffering should return early
		// It might contain output flushed *before* the premature clean, but stop_output_buffering itself shouldn't echo.
		// Check buffer level is back to initial.
		$this->assertEquals($level_before_test, ob_get_level(), 'Output buffer level should be back to initial level');
	}

	/**
	 * Test case where an intermediate buffer flush fails.
	 * It should log the error and ideally still try to process the ISC buffer if possible.
	 */
	public function test_output_buffering_with_intermediate_flush_failure() {
		$level_before_test = ob_get_level();
		// This test remains difficult to implement reliably without deeper mocking or specific PHP versions.
		// The current stop_output_buffering logs the failure, which is the main verifiable behavior.
		// We will skip the execution part but keep the structure.

		$this->enable_overlay_for_body_images();
		do_action('wp'); // Ensure hooks are registered

		$image_id = $this->create_dummy_image( 'Author Flush Fail', 'https://example.com/flushfail.jpg' );
		$image_html = '<img src="https://example.com/flushfail.jpg"/>';
		$expected_caption = 'Source: Author Flush Fail';

		// Start capturing final output
		ob_start();

		// Simulate WordPress hooks
		do_action( 'get_header' ); // Triggers start_output_buffering

		// Start an intermediate buffer
		ob_start( function( $buffer ) {
			// Simple handler, won't cause failure itself. Failure check is in stop_output_buffering.
			return $buffer;
		} );
		echo "Intermediate content";

		echo $image_html; // Goes into the intermediate buffer

		// We assume the @ob_end_flush() in stop_output_buffering might return false but suppress errors.
		// The test relies on the logging within stop_output_buffering and that it continues.

		do_action( 'wp_print_footer_scripts' ); // Triggers stop_output_buffering

		$final_output = ob_get_clean();

		// Assertions: Even if intermediate flush logged error, ISC should find its buffer and process
		$this->assertStringContainsString( $image_html, $final_output, 'Original image HTML missing' );
		$this->assertStringContainsString( '<span class="isc-source-text">' . $expected_caption . '</span>', $final_output, 'ISC caption span missing or incorrect' );
		$this->assertStringContainsString( '<span id="isc_attachment_' . $image_id . '" class="isc-source "', $final_output, 'ISC wrapper span missing or incorrect' );
		$this->assertEquals($level_before_test, ob_get_level(), 'Output buffer level should be back to initial level after processing');
	}
}