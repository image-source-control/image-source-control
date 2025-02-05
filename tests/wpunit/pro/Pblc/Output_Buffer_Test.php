<?php

namespace ISC\Tests\WPUnit\Pro\Pblc;

use ISC\Tests\WPUnit\WPTestCase;
use ISC\Options;

/**
 * Test output buffering in ISC_Pro_Public
 */
class Output_Buffer_Test extends WPTestCase {
	/**
	 * Helper function to enable the overlay for images in the whole body
	 */
	protected function enable_overlay_for_body_images() {
		$isc_options                            = Options::get_options();
		$isc_options['display_type'][]          = 'overlay';
		$isc_options['overlay_included_images'] = 'body_img';
		update_option( 'isc_options', $isc_options );
	}

	/**
	 * Check if the output buffering is omitted if overlays are only enabled within the content, which is the default behavior
	 */
	public function test_no_output_buffering_for_overlays_in_content() {
		$mockPlugin = $this->getMockBuilder( \ISC_Pro_Public::class )
		                   ->onlyMethods( [ 'start_output_buffering', 'stop_output_buffering' ] )
		                   ->getMock();

		// Setup expectations
		$mockPlugin->expects( $this->never() )
		           ->method( 'start_output_buffering' );

		$mockPlugin->expects( $this->never() )
		           ->method( 'stop_output_buffering' );

		// Manually trigger the actions to simulate a page load – these are the methods used by ISC_Pro_Public
		do_action( 'wp' );
		do_action( 'get_header' );
		do_action( 'wp_print_footer_scripts' );
	}

	/**
	 * Check if the output buffering is working when overlays are enabled for the whole page, not just the content
	 */
	public function test_output_buffering_enabled_for_overlays() {
		$this->enable_overlay_for_body_images();

		$ISCProPublic = $this->getMockBuilder( \ISC_Pro_Public::class )
		                     ->onlyMethods( [ 'start_output_buffering', 'stop_output_buffering' ] )
		                     ->getMock();

		// Setup expectations
		$ISCProPublic->expects( $this->once() )
		             ->method( 'start_output_buffering' );

		$ISCProPublic->expects( $this->once() )
		             ->method( 'stop_output_buffering' );

		// Manually trigger the actions to simulate a page load – these are the methods used by ISC_Pro_Public
		do_action( 'wp' );
		do_action( 'get_header' );
		do_action( 'wp_print_footer_scripts' );
	}

	/**
	 * Check if stop_output_buffering returns manipulated content
	 */
	public function test_processed_content_from_output_buffering() {
		$this->enable_overlay_for_body_images();

		$ISCProPublic = new \ISC_Pro_Public();

		$image_id = $this->factory->post->create( [
			                                                'post_title' => 'Image One',
			                                                'post_type'  => 'attachment',
			                                                'guid'       => 'https://example.com/image-one.jpg',
		                                                ] );

		add_post_meta( $image_id, 'isc_image_source', 'Author A' );

		ob_start();
		$ISCProPublic->start_output_buffering();

		// This is basically the content of our page
		echo '<img src="https://example.com/image-one.jpg"/>';

		$ISCProPublic->stop_output_buffering();
		$returnedContent = ob_get_clean();

		$expectedContent = '<span id="isc_attachment_' . $image_id . '" class="isc-source "><img src="https://example.com/image-one.jpg"/><span class="isc-source-text">Source: Author A</span></span>';
		$this->assertSame( $expectedContent, $returnedContent );

		wp_delete_post( $image_id, true );
	}
}