<?php

namespace ISC\Tests\WPUnit\Pblc\Captions;

use ISC\Tests\WPUnit\WPTestCase;

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
	public function setUp(): void {
		// Before each test, set the default options
		$this->options = \ISC_Public::get_instance()->default_options();
	}

	/**
	 * Test that the front_scripts function is hooked in and executed when the caption_style option is an empty string
	 */
	public function test_front_scripts_executed_when_caption_style_is_empty() {
		// Mock the ISC_Public class
		$mockISCPublic = $this->getMockBuilder( \ISC_Public::class )
		                      ->onlyMethods( [ 'front_scripts', 'get_isc_options' ] )
		                      ->getMock();

		// The Mock should only return certain options
		$isc_options                  = \ISC_Class::get_instance()->default_options();
		$isc_options['caption_style'] = '';
		$mockISCPublic->method('get_isc_options')->willReturn( $isc_options );

		$mockISCPublic->expects( $this->once() )
		              ->method( 'front_scripts' );

		// Call the register_hooks method
		$mockISCPublic->register_hooks();

		// Register ISC_Public hooks
		do_action( 'wp' );
		// Trigger the wp_enqueue_scripts action
		do_action( 'wp_enqueue_scripts' );
	}

	/**
	 * Test that the front_head function is hooked in and executed when the caption_style option is an empty string
	 */
	public function test_front_head_executed_when_caption_style_is_empty() {
		// Mock the ISC_Public class
		$mockISCPublic = $this->getMockBuilder( \ISC_Public::class )
		                      ->onlyMethods( [ 'front_head', 'get_isc_options' ] )
		                      ->getMock();

		// The Mock should only return certain options
		$isc_options                  = \ISC_Class::get_instance()->default_options();
		$isc_options['caption_style'] = '';
		$mockISCPublic->method('get_isc_options')->willReturn( $isc_options );

		$mockISCPublic->expects( $this->once() )
		              ->method( 'front_head' );

		// Call the register_hooks method
		$mockISCPublic->register_hooks();

		// Register ISC_Public hooks
		do_action( 'wp' );
		// Trigger the wp_head action
		do_action( 'wp_head' );
	}

	/**
	 * Test that the front_scripts function is not executed when the caption_style option is "none"
	 */
	public function test_front_scripts_not_executed_when_caption_style_is_none() {
		// Mock the ISC_Public class
		$mockISCPublic = $this->getMockBuilder( \ISC_Public::class )
		                      ->onlyMethods( [ 'front_scripts', 'get_isc_options' ] )
		                      ->getMock();

		// The Mock should only return certain options
		$isc_options                  = \ISC_Class::get_instance()->default_options();
		$isc_options['caption_style'] = 'none';
		$mockISCPublic->method('get_isc_options')->willReturn( $isc_options );

		$mockISCPublic->expects( $this->never() )
		              ->method( 'front_scripts' );

		// Call the register_hooks method
		$mockISCPublic->register_hooks();

		// Register ISC_Public hooks
		do_action( 'wp' );
		// Trigger the wp_enqueue_scripts action
		do_action( 'wp_enqueue_scripts' );
	}

	/**
	 * Test that the front_head function is not executed when the caption_style option is "none"
	 */
	public function test_front_head_not_executed_when_caption_style_is_none() {
		// Mock the ISC_Public class
		$mockISCPublic = $this->getMockBuilder( \ISC_Public::class )
		                      ->onlyMethods( [ 'front_head', 'get_isc_options' ] )
		                      ->getMock();

		// The Mock should only return certain options
		$isc_options                  = \ISC_Class::get_instance()->default_options();
		$isc_options['caption_style'] = 'none';
		$mockISCPublic->method('get_isc_options')->willReturn( $isc_options );

		$mockISCPublic->expects( $this->never() )
		              ->method( 'front_head' );

		// Call the register_hooks method
		$mockISCPublic->register_hooks();

		// Register ISC_Public hooks
		do_action( 'wp' );
		// Trigger the wp_head action
		do_action( 'wp_head' );
	}
}