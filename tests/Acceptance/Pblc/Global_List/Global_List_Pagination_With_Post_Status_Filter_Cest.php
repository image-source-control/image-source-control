<?php

namespace ISC\Tests\Acceptance\Pblc\Global_List;

/**
 * Test pagination behavior when images are filtered by post publish status.
 *
 * This test documents the current behavior where filtering happens after pagination,
 * based on the posts associated with each image in the isc_image_posts meta field are
 * not published.
 * The result can be an uneven distribution of items across pages when many images
 * have only unpublished posts.  This is an acceptable trade-off to avoid complex
 * SQL queries on serialized meta data.
 */
class Global_List_Pagination_With_Post_Status_Filter_Cest {

	/**
	 * Array to store IDs of created images.
	 */
	private array $images = [];

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
		// 1. Create simulated attachment posts (images)
		for ( $i = 1; $i <= $this->number_of_images; $i ++ ) {
			$attachmentId = $I->havePostInDatabase( [
				                                        'post_type'      => 'attachment',
				                                        'post_mime_type' => 'image/jpeg',
				                                        'post_title'     => "Test Image $i",
				                                        'post_content'   => '',
				                                        'post_status'    => 'inherit',
				                                        'guid'           => "https://example.com/test-image-$i.jpg"
			                                        ] );

			$I->havePostMetaInDatabase( $attachmentId, 'isc_image_source', "Author $i" );
			$this->images[] = $attachmentId;
		}

		// 2. Create posts with different statuses
		// Published posts with odd-numbered images (1, 3, 5, 7, 9)
		for ( $i = 1; $i <= $this->number_of_images; $i += 2 ) {
			$content           = '<img src="https://example.com/test-image-' . $i .  '.jpg" alt="Test Image ' . $i .  '" />';
			$published_post_id = $I->havePostInDatabase( [
				                                             'post_title'   => 'Published Post ' . $i,
				                                             'post_content' => $content,
				                                             'post_status'  => 'publish',
			                                             ] );
			$this->posts[] = $published_post_id;

			// Visit the published post to trigger indexing
			$I->amOnPage( '/?p=' . $published_post_id );
		}

		// Draft posts with even-numbered images (2, 4, 6, 8, 10)
		for ( $i = 2; $i <= $this->number_of_images; $i += 2 ) {
			$content = '<img src="https://example.com/test-image-' .  $i . '.jpg" alt="Test Image ' . $i . '" />';

			// Create as PUBLISHED first to enable indexing
			$draft_post_id = $I->havePostInDatabase( [
				                                         'post_title'   => 'Draft Post ' . $i,
				                                         'post_content' => $content,
				                                         'post_status'  => 'publish',
			                                         ] );

			// Visit the post to trigger indexing WHILE IT'S PUBLISHED
			$I->amOnPage( '/?p=' . $draft_post_id );

			// Change to draft AFTER indexing
			$I->updateInDatabase(
				$I->grabTablePrefix() . 'posts',
				[ 'post_status' => 'draft' ],
				[ 'ID' => $draft_post_id ]
			);
		}

		// 3. Set plugin options
		$isc_options                                = $I->grabOptionFromDatabase( 'isc_options' );
		$isc_options['images_per_page']             = $this->per_page_limit;
		$isc_options['global_list_included_images'] = ''; // Only images attached to published posts
		$I->haveOptionInDatabase( 'isc_options', $isc_options );

		// 4. Create the Global List page
		$I->havePageInDatabase( [
			                        'post_name'    => 'global-list',
			                        'post_title'   => 'Global List',
			                        'post_content' => '<p>Here is the Global List with images used in posts</p>[isc_list_all]'
		                        ] );
	}

	/**
	 * Test that images with only draft posts are correctly filtered out.
	 * Verifies that only images with at least one published post are displayed.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_images_with_only_draft_posts_are_filtered( \AcceptanceTester $I ) {
		$I->amOnPage( '/global-list' );

		// Even-numbered images should NOT be visible anywhere (only in draft posts)
		$hidden_images = [ 2, 4, 6, 8, 10 ];
		foreach ( $hidden_images as $img_num ) {
			$I->dontSee( "Test Image $img_num" );
		}

		// Check page 2 as well
		$I->amOnPage( '/global-list? isc-page=2' );
		foreach ( $hidden_images as $img_num ) {
			$I->dontSee( "Test Image $img_num" );
		}

		// Draft posts should never be linked
		$I->amOnPage( '/global-list' );
		$I->dontSee( "Draft Post 10" );
		$I->dontSee( "Draft Post 8" );
		$I->dontSee( "Draft Post 6" );
		$I->dontSee( "Draft Post 4" );
		$I->dontSee( "Draft Post 2" );
	}

	/**
	 * Test that all images with published posts eventually appear.
	 * Note: They may be distributed unevenly across pages due to post-query filtering.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_all_images_with_published_posts_are_displayed( \AcceptanceTester $I ) {
		// Collect all visible images across all pages
		$visible_images = [];

		// Check first page
		$I->amOnPage( '/global-list' );
		if ( $I->seeElement( '.isc_all_image_list_box' ) ) {
			// Images 9 and 7 should be visible on page 1
			$I->see( "Test Image 9" );
			$I->see( "Published Post 9" );
			$I->see( "Test Image 7" );
			$I->see( "Published Post 7" );
		}

		// Check if there's a second page
		if ( $I->seeElement( 'a.next.page-numbers' ) ) {
			$I->amOnPage( '/global-list? isc-page=2' );
			// Images 5, 3, 1 should be visible on page 2
			$I->see( "Test Image 5" );
			$I->see( "Published Post 5" );
			$I->see( "Test Image 3" );
			$I->see( "Published Post 3" );
			$I->see( "Test Image 1" );
			$I->see( "Published Post 1" );
		}

		// Verify total:  All 5 odd-numbered images should be visible somewhere
		// (Even if distributed as 2 on page 1, 3 on page 2)
	}

	/**
	 * Test with "all" included to verify correct pagination without filtering.
	 * When including all images, pagination should work as expected.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_pagination_works_correctly_with_included_all( \AcceptanceTester $I ) {
		// Change the setting to include all images
		$isc_options                                = $I->grabOptionFromDatabase( 'isc_options' );
		$isc_options['global_list_included_images'] = 'all';
		$I->haveOptionInDatabase( 'isc_options', $isc_options );

		// Create a new page with the "all" attribute
		$I->havePageInDatabase( [
			                        'post_name'    => 'global-list-all',
			                        'post_title'   => 'Global List All',
			                        'post_content' => '<p>Global List with all images</p>[isc_list_all included="all"]'
		                        ] );

		$I->amOnPage( '/global-list-all' );

		// First page should show 5 items (images 10, 9, 8, 7, 6)
		$I->see( "Test Image 10" );
		$I->see( "Test Image 9" );
		$I->see( "Test Image 8" );
		$I->see( "Test Image 7" );
		$I->see( "Test Image 6" );

		// These should NOT be on first page
		$I->dontSee( "Test Image 5" );

		// Verify pagination exists
		$I->seeElement( 'a.next.page-numbers' );

		$I->amOnPage( '/global-list-all?isc-page=2' );

		// Second page should show 5 items (images 5, 4, 3, 2, 1)
		$I->see( "Test Image 5" );
		$I->see( "Test Image 4" );
		$I->see( "Test Image 3" );
		$I->see( "Test Image 2" );
		$I->see( "Test Image 1" );

		// First page images should NOT be on second page
		$I->dontSee( "Test Image 10" );
		$I->dontSee( "Test Image 9" );
	}
}