<?php

namespace ISC\Tests\Functional\Pro\Includes\Compatibility;

class WP_Bakery_Cest {

	/**
	 * Path to the mu plugin
	 *
	 * @var
	 */
	protected $muPluginPath;

	public function _before(\FunctionalTester $I) {
		/**
		 * Tell ISC that WPBakery is enabled
		 * Since we cannot set constants directly here, we need to dynamically inject a mu-plugin with the constant that is later checked by ISC
		 */
		$this->muPluginPath = codecept_root_dir('../../../wp-content/mu-plugins/mu-plugin-wpbakery.php');
		if ( ! file_exists( $this->muPluginPath ) ) {
			file_put_contents( $this->muPluginPath, '<?php if ( ! defined("WPB_VC_VERSION" ) ) { define( "WPB_VC_VERSION", "7.0.0" ); }' );
		}

		// Enable the overlay for WP Bakery background images
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		$existingOption['overlay_included_advanced'] = ['wp_bakery_background_overlay'];
		$existingOption['display_type'] = ['overlay'];
		$I->haveOptionInDatabase('isc_options', $existingOption);

		// Prepare a page with specific ID and background image CSS
		$I->havePageInDatabase([
			                       'ID' => 456,
								   'post_name' => 'test-page',
			                       'post_title' => 'Test Page',
			                       'post_content' => '<div class="vc_custom_12345"></div>'
		                       ]);

		// Insert post meta with background image URL and ID
		$css = '.vc_custom_12345{background-image: url("https://example.com/image.jpg?id=789");}';
		$I->havePostmetaInDatabase( 456, '_wpb_shortcodes_custom_css', $css );

		// Prepare image and its caption
		$I->havePostInDatabase([
			                       'ID' => 789,
			                       'post_title' => 'Test Image',
			                       'post_type' => 'attachment'
		                       ]);
		$I->havePostmetaInDatabase(789, 'isc_image_source', 'Test Source');
	}

	/**
	 * Remove the mu plugin again
	 *
	 * @return void
	 */
	public function _after(\FunctionalTester $I) {
		// Delete the mu-plugin after the test
		if ( file_exists( $this->muPluginPath ) ) {
			unlink( $this->muPluginPath );
		}
	}

	/**
	 * Test that the source text is added for background images if overlay is enabled.
	 */
	public function test_add_source_text_for_background_image_css(\FunctionalTester $I) {
		// Go to the test page
		$I->amOnPage('/test-page');
		$I->seeInSource('<div class="vc_custom_12345 isc-source" data-isc-images="789"><span class="isc-source-text">Quelle: Test Source</span></div>');
	}

	/**
	 * Test the behavior when no image ID is found in the post meta.
	 */
	public function test_no_image_id_found(\FunctionalTester $I) {
		// Remove the post meta for the image ID
		$I->dontHavePostMetaInDatabase(['post_id' => 456, 'meta_key' => '_wpb_shortcodes_custom_css']);

		// Go to the page
		$I->amOnPage('/test-page');
		// Ensure no data-isc-images attribute is added
		$I->dontSeeInSource('data-isc-images');
	}
}
