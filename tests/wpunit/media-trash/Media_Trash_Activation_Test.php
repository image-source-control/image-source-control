<?php

namespace ISC\Tests\WPUnit\Media_Trash;

use ISC\Media_Trash\Media_Trash;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test MEDIA_TRASH activation via settings
 */
class Media_Trash_Activation_Test extends WPTestCase {

	protected function setUp(): void {
		parent::setUp();
		// Clean up options before each test
		delete_option( 'isc_options' );
	}

	protected function tearDown(): void {
		// Clean up after each test
		delete_option( 'isc_options' );
		parent::tearDown();
	}

	/**
	 * Test that MEDIA_TRASH is not defined when module is disabled
	 */
	public function test_media_trash_not_enabled_when_module_disabled() {
		// Set options with media_trash disabled
		update_option( 'isc_options', [
			'modules' => [ 'image_sources' ]
		] );

		// Media_Trash::is_enabled() should return false when module is not in the list
		$this->assertFalse(
			Media_Trash::is_enabled(),
			'Media Trash should not be enabled when module is not in modules array'
		);
	}

	/**
	 * Test that module can be enabled via settings
	 */
	public function test_media_trash_module_enabled_in_settings() {
		// Set options with media_trash enabled
		update_option( 'isc_options', [
			'modules' => [ 'image_sources', 'media_trash' ]
		] );

		// Check that the module is enabled
		$this->assertTrue(
			\ISC\Plugin::is_module_enabled( 'media_trash' ),
			'Media Trash module should be enabled when present in modules array'
		);
	}

	/**
	 * Test that MEDIA_TRASH constant is defined when module is enabled
	 */
	public function test_media_trash_constant_defined_when_enabled() {
		// Set options with media_trash enabled
		update_option( 'isc_options', [
			'modules' => [ 'media_trash' ]
		] );

		// Create new instance to trigger constant definition
		new Media_Trash();

		// Check if MEDIA_TRASH is defined
		$this->assertTrue(
			defined( 'MEDIA_TRASH' ),
			'MEDIA_TRASH constant should be defined when module is enabled'
		);

		$this->assertTrue(
			MEDIA_TRASH,
			'MEDIA_TRASH constant should be true when module is enabled'
		);
	}
}
