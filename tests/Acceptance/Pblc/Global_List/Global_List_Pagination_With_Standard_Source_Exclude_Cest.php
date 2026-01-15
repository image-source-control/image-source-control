<?php

namespace ISC\Tests\Acceptance\Pblc\Global_List;

/**
 * Test pagination with standard source exclusion.
 * Verifies that pages display the correct number of items when some images are filtered out.
 */
class Global_List_Pagination_With_Standard_Source_Exclude_Cest {

	/**
	 * Array to store IDs of created posts.
	 */
	private array $posts = [];

	/**
	 * Total number of images to create.
	 */
	protected int $number_of_images = 10;

	/**
	 * Number of items to display per page in the Global List.
	 */
	protected int $per_page_limit = 5;

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

			// For images 2, 4, 6, 8, 10: Mark them as using standard source (will be excluded)
			if ( $i % 2 === 0 ) {
				$I->havePostMetaInDatabase( $attachmentId, 'isc_image_source_own', 1 );
			} else {
				// For odd-numbered images:  Give them explicit sources (will be included)
				$I->havePostMetaInDatabase( $attachmentId, 'isc_image_source', "Author $i" );
			}
		}

		// 2. Create a post that contains all images so they get indexed
		$content = '';
		for ( $i = 1; $i <= $this->number_of_images; $i ++ ) {
			$content .= '<img src="https://example.com/test-image-' . $i . '.jpg" alt="Test Image ' . $i . '" />';
		}
		$this->posts[] = $I->havePostInDatabase( [
			                                         'post_title'   => 'Post with all images',
			                                         'post_content' => $content,
		                                         ] );

		// 3. Set plugin options to exclude standard source images
		$isc_options                                = $I->grabOptionFromDatabase( 'isc_options' );
		$isc_options['images_per_page']             = $this->per_page_limit;
		$isc_options['standard_source']             = 'exclude';
		$isc_options['global_list_included_images'] = 'all';
		$I->haveOptionInDatabase( 'isc_options', $isc_options );

		// 4. Create the Global List page
		$I->havePageInDatabase( [
			                        'post_name'    => 'global-list',
			                        'post_title'   => 'Global List',
			                        'post_content' => '<p>Here is the Global List with all images</p>[isc_list_all]'
		                        ] );

		// 5. Visit the post to trigger indexing
		$I->amOnPage( '/?p=' . $this->posts[0] );
	}

	/**
	 * Test that the first page displays five items.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_first_page_shows_five_items( \AcceptanceTester $I ) {
		$I->amOnPage( '/global-list' );

		// All 5 odd-numbered images should be visible on page 1
		$I->see( "Test Image 9" );
		$I->see( "Author 9" );

		$I->see( "Test Image 7" );
		$I->see( "Author 7" );

		$I->see( "Test Image 5" );
		$I->see( "Author 5" );

		$I->see( "Test Image 3" );
		$I->see( "Author 3" );

		$I->see( "Test Image 1" );
		$I->see( "Author 1" );

		// Even-numbered images should not be visible (filtered by standard source exclusion)
		$I->dontSee( "Test Image 10" );
		$I->dontSee( "Test Image 8" );
		$I->dontSee( "Test Image 6" );
		$I->dontSee( "Test Image 4" );
		$I->dontSee( "Test Image 2" );
	}

	/**
	 * Test that only one page exists when five items fit on one page.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_only_one_page_exists( \AcceptanceTester $I ) {
		$I->amOnPage( '/global-list' );

		// With 5 images after filtering and per_page=5, there should be no pagination
		$I->dontSeeElement( 'a.next.page-numbers' );
		$I->dontSeeElement( 'a.prev.page-numbers' );
	}

	/**
	 * Test that the second page does not exist or is empty.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_second_page_is_empty( \AcceptanceTester $I ) {
		$I->amOnPage( '/global-list?isc-page=2' );

		// Images should be on page 1, not page 2
		$I->dontSee( "Test Image 5" );
		$I->dontSee( "Test Image 3" );
		$I->dontSee( "Test Image 1" );
	}

	/**
	 * Test that exactly five rows are displayed.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_five_rows_displayed( \AcceptanceTester $I ) {
		$I->amOnPage( '/global-list' );

		// Count table rows - should be exactly 5
		$I->seeNumberOfElements( 'tr', 5 );
	}
}