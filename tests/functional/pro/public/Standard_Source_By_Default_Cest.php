<?php

namespace ISC\Tests\Functional;

/**
 * Test ISC_Pro_Public::use_standard_source_by_default
 */
class Standard_Source_By_Default_Cest {

	public function _before(\FunctionalTester $I) {
		// enable the overlay
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		$existingOption['display_type'] = ['overlay'];
		// Make sure we enable the "custom_text" standard source with the text "@ ISC"
		$existingOption['standard_source'] = 'custom_text';
		$existingOption['standard_source_text'] = '@ ISC';
		// Show the standard source if the image’s specific source is empty
		$existingOption['use_standard_source_by_default'] = 1;

		// Update the option in the database with the new array.
		$I->haveOptionInDatabase( 'isc_options', $existingOption );

		// prepare an image
		$I->havePostInDatabase( [
           'ID' => 123,
           'post_title' => 'Image One',
           'guid' => 'https://example.com/image-one.jpg',
       ] );
		// image has a source by default
		$I->havePostmetaInDatabase( 123, 'isc_image_source', 'Author A' );
	}

	/**
	 * Setup:
	 * - image has a source
	 * - image is NOT set to use the standard source
	 * => show the image source text: "Author A"
	 *
	 * @param \FunctionalTester $I
	 *
	 * @return void
	 */
	public function test_image_with_source( \FunctionalTester $I ) {
		$I->havePageInDatabase( [
			'post_name'    => 'test-page',
			'post_content' => '<img src="https://example.com/image-one.jpg" />',
		] );

		// Go to the page.
		$I->amOnPage( '/test-page' );
		$I->seeInSource( '<span id="isc_attachment_123" class="isc-source "><img decoding="async" src="https://example.com/image-one.jpg" /><span class="isc-source-text">Quelle: Author A</span></span>' );
	}

	/**
	 * Setup:
	 * - image has no source
	 * - image is NOT set to use the standard source
	 * => show the standard source text "@ ISC"
	 */
	public function test_use_standard_for_empty_image_source( \FunctionalTester $I ) {
		// remove the image source
		$I->dontHavePostMetaInDatabase( ['post_id' => 123, 'meta_key' => 'isc_image_source'] );

		$I->havePageInDatabase( [
			'post_name'    => 'test-page',
			'post_content' => '<img src="https://example.com/image-one.jpg" />',
		] );

		// Go to the page.
		$I->amOnPage( '/test-page' );
		// the actual HTML output, including the data attribute and the overlay code at the beginning of the DIV container
		$I->seeInSource( '<span id="isc_attachment_123" class="isc-source "><img decoding="async" src="https://example.com/image-one.jpg" /><span class="isc-source-text">Quelle: @ ISC</span></span>' );
	}
}
