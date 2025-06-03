<?php

namespace ISC\Tests\Acceptance\Pblc\Global_List;

/**
 * Test the pagination feature of the Global List.
 */
class Global_List_Pagination_Cest {

	/**
	 * Array to store IDs of created images.
	 */
	private array $images = [];

	/**
	 * Array to store IDs of created posts.
	 *
	 * @var array
	 */
	private array $posts = [];

	/**
	 * Total number of images to create for testing pagination.
	 *
	 * @var int
	 */
	protected int $number_of_images = 5;

	/**
	 * Total number of posts to create, containing images for indexing.
	 *
	 * @var int
	 */
	protected int $number_of_posts = 5;

	/**
	 * Number of items to display per page in the Global List.
	 * This will be used for both shortcode attribute and plugin option tests.
	 *
	 * @var int
	 */
	protected $per_page_limit = 2;

	/**
	 * Set up the test environment before each test method.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function _before( \AcceptanceTester $I ) {
		// 1. Create simulated attachment posts (images) with source information.
		for ( $i = 1; $i <= $this->number_of_images; $i ++ ) {
			$attachmentId = $I->havePostInDatabase( [
				                                        'post_type'      => 'attachment',
				                                        'post_mime_type' => 'image/jpeg',
				                                        'post_title'     => "Test Image $i",
				                                        'post_content'   => '',
				                                        'post_status'    => 'inherit',
				                                        'guid'           => "https://example.com/test-image-$i.jpg"
			                                        ] );
			// Assign a unique image source to each image for easy identification in tests.
			$I->havePostMetaInDatabase( $attachmentId, 'isc_image_source', "Author $i" );
		}

		// 2. Create posts with specific image assignments.
		// These posts will contain the images, which is necessary for ISC's indexing.
		$this->posts = [];
		for ( $i = 1; $i <= $this->number_of_posts; $i ++ ) {
			$content = '';
			// Each post will embed images up to its index, e.g., Post 1 has Image 1, Post 2 has Image 1, 2, etc.
			for ( $j = 0; $j < min( $i, count( $this->images ) ); $j ++ ) {
				$content .= '<img src="https://example.com/test-image-' . ( $j + 1 ) . '.jpg" alt="Test Image ' . ( $j + 1 ) . '" />';
			}
			$this->posts[] = $I->havePostInDatabase( [
				                                         'post_title'   => 'Post ' . $i,
				                                         'post_content' => $content,
			                                         ] );
		}

		// 3. Set plugin options.
		$existingOption = $I->grabOptionFromDatabase( 'isc_options' );
		$existingOption['global_list_enabled'] = 1;
		// Set the global plugin option for images per page.
		$existingOption['images_per_page'] = $this->per_page_limit;
		$I->haveOptionInDatabase( 'isc_options', $existingOption );

		// 4. Add Global List pages (without hardcoded IDs to prevent conflicts).
		$I->havePageInDatabase( [
			                        'post_name'    => 'global-list-shortcode',
			                        'post_title'   => 'Global List Shortcode',
			                        'post_content' => '<p>Here is the Global List with all images (shortcode attribute)</p>[isc_list_all per_page="' . $this->per_page_limit . '" included="all"]'
		                        ] );

		$I->havePageInDatabase( [
			                        'post_name'    => 'custom-pagination-list',
			                        'post_title'   => 'Custom Pagination List',
			                        'post_content' => '<p>Custom pagination texts</p>[isc_list_all per_page="' . $this->per_page_limit . '" prev_text="&lt;&lt; Back" next_text="Forward &gt;&gt;" included="all"]'
		                        ] );

		$I->havePageInDatabase( [
			                        'post_name'    => 'global-list-option',
			                        'post_title'   => 'Global List Option',
			                        'post_content' => '<p>Here is the Global List with all images (plugin option)</p>[isc_list_all included="all"]'
		                        ] );

		// 5. Visit posts to create the index.
		// This is crucial for ISC to track image usages and populate the Global List.
		$this->open_all_posts( $I );
	}

	/**
	 * Test pagination behavior on the first page of the Global List (shortcode attribute).
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_pagination_on_first_page_shortcode( \AcceptanceTester $I ) {
		$I->amOnPage( '/global-list-shortcode' );

		// Verify that the first set of images is displayed correctly. It lists the latest images first, so we expect Authors 5 and 4 to be visible.
		$I->see( "Author 5" );
		$I->see( "Author 4" );
		// Assert that images from the next page are not visible.
		$I->dontSee( "Author 3" );

		// Verify the presence and state of pagination links.
		$I->seeElement( 'span.page-numbers.current' );
		$I->see( '2', 'a.page-numbers' );
		$I->see( 'Next »', 'a.next.page-numbers' );
		// The "Previous" link should not be present on the first page.
		$I->dontSeeElement( 'a.prev.page-numbers' );
	}

	/**
	 * Test pagination behavior on the second page of the Global List (shortcode attribute).
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_pagination_on_second_page_shortcode( \AcceptanceTester $I ) {
		$I->amOnPage( '/global-list-shortcode?isc-page=2' );

		// Verify that images specific to the second page are displayed.
		$I->see( "Author 3" );
		$I->see( "Author 2" );
		// Assert that images from the first and third pages are not visible.
		$I->dontSee( "Author 1" );
		$I->dontSee( "Author 4" );
		$I->dontSee( "Author 5" );

		// Verify the presence and state of pagination links.
		$I->see( '2', 'span.page-numbers.current' );
		$I->see( '1', 'a.page-numbers' );
		$I->see( '3', 'a.page-numbers' );

		// Both "Previous" and "Next" links should be present on an intermediate page.
		$I->see( '« Previous', 'a.prev.page-numbers', );
		$I->see( 'Next »', 'a.next.page-numbers' );
	}

	/**
	 * Test pagination behavior on the last page of the Global List (shortcode attribute).
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_pagination_on_last_page_shortcode( \AcceptanceTester $I ) {
		// Calculate the expected last page number.
		$last_page = (int) ceil( $this->number_of_images / $this->per_page_limit ); // 5 images / 2 per page = 3 pages
		$I->amOnPage( '/global-list-shortcode?isc-page=' . $last_page );

		// Verify that images specific to the last page are displayed.
		$I->see( "Author 1" );
		// Assert that images from the previous pages are not visible.
		$I->dontSee( "Author 5" );
		$I->dontSee( "Author 2" );

		// Verify the presence and state of pagination links.
		$I->see( (string) $last_page, 'span.page-numbers.current' );
		$I->see( '« Previous', 'a.prev.page-numbers' );
		// The "Next" link should not be present on the last page.
		$I->dontSeeElement( 'a.next.page-numbers' );
	}

	/**
	 * Test that custom previous and next texts are applied correctly via shortcode attributes.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_pagination_custom_texts( \AcceptanceTester $I ) {
		// Navigate to the page configured with custom pagination texts.
		$I->amOnPage( '/custom-pagination-list?isc-page=2' );

		// Assert that the custom texts are visible.
		$I->see( '<< Back' );
		$I->see( 'Forward >>' );
		// Assert that the default texts are not visible.
		$I->dontSee( '« Previous' );
		$I->dontSee( 'Next »' );
	}

	/**
	 * Test pagination behavior when controlled by the plugin option `images_per_page`.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_pagination_with_plugin_option( \AcceptanceTester $I ) {
		// Navigate to the page using the plugin option for pagination.
		$I->amOnPage( '/global-list-option' );

		// Verify that the first set of images is displayed correctly based on the plugin option.
		$I->see( "Author 5" );
		$I->see( "Author 4" );
		$I->dontSee( "Author 3" );

		// Verify pagination links on the first page.
		$I->see( '1', 'span.page-numbers.current' );
		$I->see( '2', 'a.page-numbers' );
		$I->see( 'Next »', 'a.next.page-numbers' );
		$I->dontSeeElement( 'a.prev.page-numbers' );

		// Navigate to the second page using the plugin option.
		$I->amOnPage( '/global-list-option?isc-page=2' );

		// Verify images on the second page.
		$I->see( "Author 3" );
		$I->see( "Author 2" );
		$I->dontSee( "Author 1" );
		$I->dontSee( "Author 4" );

		// Verify pagination links on the second page.
		$I->see( '2', 'span.page-numbers.current' );
		$I->see( '1', 'a.page-numbers' );
		$I->see( '3', 'a.page-numbers' );
		$I->see( '« Previous', 'a.prev.page-numbers' );
		$I->see( 'Next »', 'a.next.page-numbers' );

		// Navigate to the last page using the plugin option.
		$last_page = (int) ceil( $this->number_of_images / $this->per_page_limit ); // 5 images / 2 per page = 3 pages
		$I->amOnPage( '/global-list-option?isc-page=' . $last_page );

		// Verify images on the last page.
		$I->see( "Author 1" );
		$I->dontSee( "Author 2" );
		$I->dontSee( "Author 5" );

		// Verify pagination links on the last page.
		$I->see( (string) $last_page, 'span.page-numbers.current' );
		$I->see( '« Previous', 'a.prev.page-numbers' );
		$I->dontSeeElement( 'a.next.page-numbers' );
	}

	/**
	 * Helper to visit all created posts to ensure they are indexed by ISC.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	private function open_all_posts( \AcceptanceTester $I ) {
		foreach ( $this->posts as $postId ) {
			$I->amOnPage( '/?p=' . $postId );
		}
	}
}