<?php

namespace ISC\Tests\Functional;

/**
 * Tests for ISC\Standard_Source to handle the standard source text
 */
class Standard_Source_Cest {

	public function _before(\FunctionalTester $I) {
		// enable the overlay
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		$existingOption['display_type'] = ['overlay'];
		// Make sure we enable the "custom_text" standard source with the text "@ ISC"
		$existingOption['standard_source'] = 'custom_text';
		$existingOption['standard_source_text'] = '@ ISC';

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
	 * - image is set to use the standard source
	 * => show the standard source text "@ ISC"
	 *
	 * @param \FunctionalTester $I
	 *
	 * @return void
	 */
	public function test_standard_custom_text( \FunctionalTester $I ) {
		// set the image to use the standard source
		$I->havePostmetaInDatabase( 123, 'isc_image_source_own', 1 );
		$I->havePageInDatabase( [
			'post_name'    => 'test-page',
			'post_content' => '<img src="https://example.com/image-one.jpg" />',
		] );

		// Go to the page.
		$I->amOnPage( '/test-page' );
		$I->seeInSource( '<span id="isc_attachment_123" class="isc-source "><img decoding="async" src="https://example.com/image-one.jpg" /><span class="isc-source-text">Quelle: @ ISC</span></span>' );
	}

	/**
	 * Setup:
	 * - image has no source
	 * - image is set to use the standard source
	 * => show the standard source text "@ ISC"
	 */
	public function test_standard_custom_text_no_source( \FunctionalTester $I ) {
		// set the image to use the standard source
		$I->havePostmetaInDatabase( 123, 'isc_image_source_own', 1 );
		// remove the explicit image source
		$I->dontHavePostMetaInDatabase( [ 'post_id' => 123, 'meta_key' => 'isc_image_source' ] );

		$I->havePageInDatabase( [
			'post_name'    => 'test-page',
			'post_content' => '<img src="https://example.com/image-one.jpg" />',
		] );

		// Go to the page.
		$I->amOnPage( '/test-page' );
		// the actual HTML output, including the data attribute and the overlay code at the beginning of the DIV container
		$I->seeInSource( '<span id="isc_attachment_123" class="isc-source "><img decoding="async" src="https://example.com/image-one.jpg" /><span class="isc-source-text">Quelle: @ ISC</span></span>' );
	}

	/**
	 * Test if the author name shows up if that is selected as the standard source
	 */
	public function test_standard_author_name( \FunctionalTester $I ) {
		// enable the author_name standard option
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		$existingOption['standard_source'] = 'author_name';
		$I->haveOptionInDatabase( 'isc_options', $existingOption );

		// set the image to use the standard source
		$I->havePostmetaInDatabase( 123, 'isc_image_source_own', 1 );
		$I->havePageInDatabase( [
			                        'post_name'    => 'test-page',
			                        'post_content' => '<img src="https://example.com/image-one.jpg" />',
		                        ] );

		// Go to the page.
		$I->amOnPage( '/test-page' );
		// our standard author name is "admin"
		$I->seeInSource( '<span id="isc_attachment_123" class="isc-source "><img decoding="async" src="https://example.com/image-one.jpg" /><span class="isc-source-text">Quelle: admin</span></span>' );
	}

	/**
	 * Test if excluding the image as a standard source removes the overlay completely
	 */
	public function test_standard_exclude( \FunctionalTester $I ) {
		// enable the exclude standard option
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		$existingOption['standard_source'] = 'exclude';
		$I->haveOptionInDatabase( 'isc_options', $existingOption );

		// set the image to use the standard source
		$I->havePostmetaInDatabase( 123, 'isc_image_source_own', 1 );
		$I->havePageInDatabase( [
			                        'post_name'    => 'test-page',
			                        'post_content' => '<img src="https://example.com/image-one.jpg" />',
		                        ] );

		// Go to the page.
		$I->amOnPage( '/test-page' );
		// our standard author name is "admin"
		$I->seeInSource( '<p><img decoding="async" src="https://example.com/image-one.jpg" /></p>' );
	}
}
