<?php

namespace ISC\Tests\Functional\Pro\Includes;

/**
 * Test ISC\Pro\IPTC
 */
class IPTC_Cest {

	public function _before(\FunctionalTester $I) {
		// enable the overlay
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		$existingOption['display_type'] = ['overlay'];
		// Make sure we enable the "iptc" standard source
		$existingOption['standard_source'] = 'iptc';
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
	}

	/**
	 * Setup:
	 * - image has no source
	 * - image is set to use the standard source
	 * - IPTC copyright is "IPTC Copyright" and credit is "IPTC Credit"
	 * => show the IPTC credit information: "IPTC Credit"
	 *
	 * @param \FunctionalTester $I
	 *
	 * @return void
	 */
	public function test_image_standard_source_shows_iptc_credit( \FunctionalTester $I ) {
		// insert image meta information with copyright and credit fields
		$I->havePostmetaInDatabase( 123, '_wp_attachment_metadata', [ 'image_meta' => [ 'credit' => 'IPTC Credit', 'copyright' => 'IPTC Copyright' ] ] );
		$I->havePageInDatabase( [
			'post_name'    => 'test-page',
			'post_content' => '<img src="https://example.com/image-one.jpg" />',
		] );

		// Go to the page.
		$I->amOnPage( '/test-page' );
		$I->seeInSource( '<span id="isc_attachment_123" class="isc-source "><img decoding="async" src="https://example.com/image-one.jpg" /><span class="isc-source-text">Quelle: IPTC Credit</span></span>' );
	}

	/**
	 * Setup:
	 * - image has no source
	 * - image is set to use the standard source
	 * - IPTC copyright is "IPTC Copyright" and credit is "IPTC Credit"
	 * - Copyright is selected as the IPTC tag to show instead of Credit
	 * => show the IPTC copyright information: "IPTC Copyright"
	 *
	 * @param \FunctionalTester $I
	 *
	 * @return void
	 */
	public function test_image_standard_source_shows_iptc_copyright( \FunctionalTester $I ) {
		// select "copyright" as the IPTC tag to show as standard source
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		$existingOption['standard_source_iptc_tag'] = ['copyright'];
		$I->haveOptionInDatabase( 'isc_options', $existingOption );

		$I->havePostmetaInDatabase( 123, '_wp_attachment_metadata', [ 'image_meta' => [ 'credit' => 'IPTC Credit', 'copyright' => 'IPTC Copyright' ] ] );
		$I->havePageInDatabase( [
			                        'post_name'    => 'test-page',
			                        'post_content' => '<img src="https://example.com/image-one.jpg" />',
		                        ] );

		// Go to the page.
		$I->amOnPage( '/test-page' );
		$I->seeInSource( '<span id="isc_attachment_123" class="isc-source "><img decoding="async" src="https://example.com/image-one.jpg" /><span class="isc-source-text">Quelle: IPTC Copyright</span></span>' );
	}

	/**
	 * Setup:
	 * - image has no source
	 * - image is set to use the standard source
	 * - IPTC copyright is "IPTC Copyright", credit is not set
	 * => show the IPTC copyright information: "IPTC Copyright"
	 *
	 * @param \FunctionalTester $I
	 *
	 * @return void
	 */
	public function test_image_standard_source_shows_iptc_copyright_fallback( \FunctionalTester $I ) {
		// insert image meta information with credit field
		$I->havePostmetaInDatabase( 123, '_wp_attachment_metadata', [ 'image_meta' => [ 'copyright' => 'IPTC Copyright' ] ] );
		$I->havePageInDatabase( [
			                        'post_name'    => 'test-page',
			                        'post_content' => '<img src="https://example.com/image-one.jpg" />',
		                        ] );

		// Go to the page.
		$I->amOnPage( '/test-page' );
		$I->seeInSource( '<span id="isc_attachment_123" class="isc-source "><img decoding="async" src="https://example.com/image-one.jpg" /><span class="isc-source-text">Quelle: IPTC Copyright</span></span>' );
	}

	/**
	 * Setup:
	 * - image has no source
	 * - image is NOT set to use the standard source
	 * - IPTC copyright is "IPTC Copyright" and credit is "IPTC Credit"
	 *  => show the IPTC credit information: "IPTC Credit"
	 */
	public function test_empty_image_source_shows_iptc_credit( \FunctionalTester $I ) {
		// insert image meta information with copyright and credit fields
		$I->havePostmetaInDatabase( 123, '_wp_attachment_metadata', [ 'image_meta' => [ 'credit' => 'IPTC Credit', 'copyright' => 'IPTC Copyright' ] ] );

		$I->havePageInDatabase( [
			'post_name'    => 'test-page',
			'post_content' => '<img src="https://example.com/image-one.jpg" />',
		] );

		// Go to the page.
		$I->amOnPage( '/test-page' );
		// the actual HTML output, including the data attribute and the overlay code at the beginning of the DIV container
		$I->seeInSource( '<span id="isc_attachment_123" class="isc-source "><img decoding="async" src="https://example.com/image-one.jpg" /><span class="isc-source-text">Quelle: IPTC Credit</span></span>' );
	}
}
