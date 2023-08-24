<?php

namespace ISC\Tests\Functional;

/**
 * Test for ISC_Pro_Public::add_caption_from_isc_images_attribute() which converts a `data-isc-images` attribute into captions
 */
class Data_Images_Attribute_Cest {


	public function _before(\FunctionalTester $I) {
		// enable the overlay
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		$existingOption['display_type'] = ['overlay'];
		$I->haveOptionInDatabase( 'isc_options', $existingOption );

		// load image information
		// image 1
		$I->havePostmetaInDatabase(123, 'isc_image_source', 'Author A');
		$I->havePostmetaInDatabase(123, 'isc_image_source_url', 'https://author-a.com');
		$I->havePostmetaInDatabase(123, 'isc_image_license', 'Public Domain Mark 1.0');
		$I->havePostInDatabase([
			                       'ID' => 123,
			                       'post_title' => 'Image One',
			                       'guid' => 'https://example.com/image-one.jpg',
		                       ]);

		// image 2
		$I->havePostmetaInDatabase(234, 'isc_image_source', 'Author B');
		$I->havePostmetaInDatabase(234, 'isc_image_source_url', 'https://www.author-b.com');
		$I->havePostmetaInDatabase(234, 'isc_image_license', 'Public Domain Mark 1.0');
		$I->havePostInDatabase([
			                       'ID' => 234,
			                       'post_title' => 'Image Two',
			                       'guid' => 'https://example.com/image-two.png',
		                       ]);

		// image 3
		$I->havePostmetaInDatabase(345, 'isc_image_source', 'Author C');
		$I->havePostmetaInDatabase(345, 'isc_image_source_url', 'https://www.author-c.com');
		$I->havePostmetaInDatabase(345, 'isc_image_license', 'Public Domain Mark 1.0');
		$I->havePostInDatabase([
			                       'ID' => 345,
			                       'post_title' => 'Image Three',
			                       'guid' => 'https://example.com/image-three.png',
		                       ]);
	}

	/**
	 * When the `data-isc-images` attribute is placed on a <a> tag,
	 * the caption itself should not contain any links.
	 *
	 * @param \FunctionalTester $I
	 *
	 * @return void
	 */
	public function test_no_link_in_link( \FunctionalTester $I ) {
		$I->havePageInDatabase( [
			'post_name'    => 'test-page',
			'post_content' => <<<EOD
<a href="https://example.com" data-isc-images="123">Some Content</a>
<span data-isc-images="234">Some Content</span>
<a href="https://example.org" data-isc-images="345">More Content</a>
EOD,
		] );

		// Go to the page.
		$I->amOnPage( '/test-page' );
		// the actual HTML output, including the data attribute and the overlay code at the beginning of the DIV container
		$I->seeInSource( <<<EOD
<a href="https://example.com" data-isc-images="123"><span class="isc-source-text">Quelle: Author A</span>Some Content</a><br />
<span data-isc-images="234"><span class="isc-source-text">Quelle: <a href="https://www.author-b.com" target="_blank" rel="nofollow">Author B</a></span>Some Content</span><br />
<a href="https://example.org" data-isc-images="345"><span class="isc-source-text">Quelle: Author C</span>More Content</a>
EOD
		);
	}
}
