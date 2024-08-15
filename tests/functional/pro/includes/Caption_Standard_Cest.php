<?php

namespace ISC\Tests\Functional;

/**
 * Test ISC\Pro\WP_Caption
 */
class Caption_Standard_Cest {

	public function _before(\FunctionalTester $I) {
		// enable the overlay
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		$existingOption['display_type'] = ['overlay'];
		// Make sure we enable the "iptc" standard source
		$existingOption['standard_source'] = 'wp_caption';
		// Show the standard source if the image’s specific source is empty
		$existingOption['use_standard_source_by_default'] = 1;

		// Update the option in the database with the new array.
		$I->haveOptionInDatabase( 'isc_options', $existingOption );

		// prepare an image
		$I->havePostInDatabase( [
           'ID' => 123,
           'post_title' => 'Image One',
           'guid' => 'https://example.com/image-one.jpg',
           'post_excerpt' => 'This is the caption',
       ] );
	}

	/**
	 * Setup:
	 * - image has no source
	 * - image is set to use the standard source
	 *
	 * @param \FunctionalTester $I
	 *
	 * @return void
	 */
	public function test_image_standard_source_shows_wp_caption( \FunctionalTester $I ) {
		$I->havePageInDatabase( [
			'post_name'    => 'test-page',
			'post_content' => '<img src="https://example.com/image-one.jpg" />',
		] );

		// Go to the page.
		$I->amOnPage( '/test-page' );
		$I->seeInSource( '<span id="isc_attachment_123" class="isc-source "><img decoding="async" src="https://example.com/image-one.jpg" /><span class="isc-source-text">Quelle: This is the caption</span></span>' );
	}
}
