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
	 * Test the Settings::render_settings_page() method hides the compatibility box when no notices exist.
	 *
	 * @covers \ISC\Settings::render_settings_page
	 */
	public function test_render_settings_page_hides_compatibility_box_without_notices(): void {
		global $wp_settings_sections;

		$wp_settings_sections = [
			'isc_settings_page' => [
				'isc_settings_section_plugin' => [
					'id'       => 'isc_settings_section_plugin',
					'title'    => 'Plugin options',
					'callback' => '__return_false',
				],
			],
		];

		ob_start();
		( new Settings() )->render_settings_page();
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'isc_settings_section_compatibility', $output );
	}
}
