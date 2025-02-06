<?php

namespace ISC\Tests\Functional\Includes;

class Plugin_Is_Image_Sources_Module_Cest
{
	private $imageId;

	public function _before(\FunctionalTester $I)
	{
		// Prepare a test image with dynamic ID
		$this->imageId = $I->havePostInDatabase([
			                                        'post_title' => 'Test Image',
			                                        'guid' => 'https://example.com/test-image.jpg',
		                                        ]);

		// Add default source to image
		$I->havePostmetaInDatabase($this->imageId, 'isc_image_source', 'Test Author');
	}

	/**
	 * Test when module is enabled by default (no modules array in options)
	 * Markup should be added to the image
	 */
	public function test_module_enabled_by_default(\FunctionalTester $I)
	{
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		unset($existingOption['modules']); // Ensure no modules array exists
		$existingOption['display_type'] = ['overlay'];
		$existingOption['standard_source'] = 'custom_text';
		$existingOption['standard_source_text'] = '@ ISC';

		$I->haveOptionInDatabase('isc_options', $existingOption);

		$pageId = $I->havePageInDatabase([
			                                 'post_name' => 'test-page',
			                                 'post_content' => '<img src="https://example.com/test-image.jpg" />'
		                                 ]);

		$I->amOnPage('/test-page');
		$I->seeInSource('<span class="isc-source-text">Quelle: Test Author</span>');
	}

	/**
	 * Test when module is explicitly enabled
	 * Markup should be added to the image
	 */
	public function test_module_explicitly_enabled(\FunctionalTester $I)
	{
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		$existingOption['modules'] = ['image_sources'];
		$existingOption['display_type'] = ['overlay'];
		$existingOption['standard_source'] = 'custom_text';
		$existingOption['standard_source_text'] = '@ ISC';

		$I->haveOptionInDatabase('isc_options', $existingOption);

		$pageId = $I->havePageInDatabase([
			                                 'post_name' => 'test-page-enabled',
			                                 'post_content' => '<img src="https://example.com/test-image.jpg" />'
		                                 ]);

		$I->amOnPage('/test-page-enabled');
		$I->seeInSource('<span class="isc-source-text">Quelle: Test Author</span>');
	}

	/**
	 * Test when module is disabled
	 * no markup should be added to the image
	 */
	public function test_module_disabled(\FunctionalTester $I)
	{
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		$existingOption['modules'] = ['other_module']; // image_sources module not included
		$existingOption['display_type'] = ['overlay'];
		$existingOption['standard_source'] = 'custom_text';
		$existingOption['standard_source_text'] = '@ ISC';

		$I->haveOptionInDatabase('isc_options', $existingOption);

		$I->havePageInDatabase([
			                                 'post_name' => 'test-page-disabled',
			                                 'post_content' => '<img src="https://example.com/test-image.jpg" />'
		                                 ]);

		$I->amOnPage('/test-page-disabled');
		$I->seeInSource('src="https://example.com/test-image.jpg"'); // WP dynamically adds content to the IMG tag so we only check the src attribute
		$I->dontSeeInSource('class="isc-source"');
		$I->dontSeeInSource('isc-source-text');
	}
}