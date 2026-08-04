<?php

namespace ISC\Tests\WPUnit\Includes;

use ISC\Compatibility;
use ISC\Settings;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test the compatibility checks and settings rendering.
 */
class Compatibility_Test extends WPTestCase {

	/**
	 * Test the Compatibility::check_wp_bakery() method returns no notice by default.
	 *
	 * @covers \ISC\Compatibility::check_wp_bakery
	 */
	public function test_check_wp_bakery_returns_null_when_plugin_is_not_available(): void {
		$this->assertNull( ( new Compatibility() )->check_wp_bakery() );
	}

	/**
	 * Test the Compatibility::get_notices() method returns a normalized WPBakery notice.
	 *
	 * @covers \ISC\Compatibility::get_notices
	 */
	public function test_get_notices_returns_wp_bakery_notice(): void {
		if ( ! defined( 'WPB_VC_VERSION' ) ) {
			define( 'WPB_VC_VERSION', 'test-version' );
		}

		$notices = ( new Compatibility() )->get_notices();

		$this->assertCount( 1, $notices );
		$this->assertSame( 'WPBakery Page Builder', $notices[0]['name'] );
		$this->assertSame( 'https://imagesourcecontrol.com/documentation/compatibility/#WPBakery_Page_Builder', $notices[0]['manual_url'] );
		$this->assertFalse( $notices[0]['show_pro_link'] );
	}

	/**
	 * Test the Compatibility::register_settings_section() method does not register a section without notices.
	 *
	 * @covers \ISC\Compatibility::register_settings_section
	 */
	public function test_register_settings_section_skips_empty_notices(): void {
		global $wp_settings_sections;

		$wp_settings_sections = [];

		( new Compatibility() )->register_settings_section();

		$this->assertArrayNotHasKey( 'isc_settings_page', $wp_settings_sections );
	}

	/**
	 * Test the Settings::render_settings_page() method renders the compatibility section below plugin options.
	 *
	 * @covers \ISC\Settings::render_settings_page
	 */
	public function test_render_settings_page_renders_compatibility_section_after_plugin_options(): void {
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! defined( 'WPB_VC_VERSION' ) ) {
			define( 'WPB_VC_VERSION', 'test-version' );
		}

		$wp_settings_sections = [];
		$wp_settings_fields   = [];

		add_settings_section( 'isc_settings_section_newsletter', 'Newsletter', '__return_false', 'isc_settings_page' );
		add_settings_section( 'isc_settings_section_plugin', 'Plugin options', '__return_false', 'isc_settings_page' );
		( new Compatibility() )->register_settings_section();
		add_settings_section( 'isc_settings_section_misc', 'Miscellaneous settings', '__return_false', 'isc_settings_page' );

		ob_start();
		( new Settings() )->render_settings_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'isc_settings_section_compatibility', $output );
		$this->assertGreaterThan(
			strpos( $output, 'isc_settings_section_plugin' ),
			strpos( $output, 'isc_settings_section_compatibility' )
		);
		$this->assertGreaterThan(
			strpos( $output, 'isc_settings_section_compatibility' ),
			strpos( $output, 'isc_settings_section_misc' )
		);
	}

}
