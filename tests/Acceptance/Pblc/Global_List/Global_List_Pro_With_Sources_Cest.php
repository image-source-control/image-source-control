<?php

namespace ISC\Tests\Acceptance\Pblc\Global_List;

/**
 * Test Pro feature:  global_list_included_images = 'with_sources'
 * Only images with explicit sources OR standard source should be included.
 */
class Global_List_Pro_With_Sources_Cest {

	/**
	 * Total number of images to create.
	 */
	protected int $number_of_images = 12;

	/**
	 * Number of items to display per page in the Global List.
	 */
	protected int $per_page_limit = 4;

	/**
	 * Set up the test environment before each test method.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function _before( \AcceptanceTester $I ) {
		// 1. Create images with different source configurations
		// Images 1-4: Have explicit source
		for ( $i = 1; $i <= 4; $i++ ) {
			$attachmentId = $I->havePostInDatabase( [
				                                        'post_type'      => 'attachment',
				                                        'post_mime_type' => 'image/jpeg',
				                                        'post_title'     => "Test Image $i",
				                                        'post_content'   => '',
				                                        'post_status'    => 'inherit',
				                                        'guid'           => "https://example.com/test-image-$i. jpg"
			                                        ] );

			$I->havePostMetaInDatabase( $attachmentId, 'isc_image_source', "Author $i" );
			$this->images[] = $attachmentId;
		}

		// Images 5-8: Use standard source (isc_image_source_own = 1)
		for ( $i = 5; $i <= 8; $i++ ) {
			$attachmentId = $I->havePostInDatabase( [
				                                        'post_type'      => 'attachment',
				                                        'post_mime_type' => 'image/jpeg',
				                                        'post_title'     => "Test Image $i",
				                                        'post_content'   => '',
				                                        'post_status'    => 'inherit',
				                                        'guid'           => "https://example.com/test-image-$i.jpg"
			                                        ] );

			$I->havePostMetaInDatabase( $attachmentId, 'isc_image_source_own', '1' );
			$this->images[] = $attachmentId;
		}

		// Images 9-12: NO source (should be excluded)
		for ( $i = 9; $i <= 12; $i++ ) {
			$attachmentId = $I->havePostInDatabase( [
				                                        'post_type'      => 'attachment',
				                                        'post_mime_type' => 'image/jpeg',
				                                        'post_title'     => "Test Image $i",
				                                        'post_content'   => '',
				                                        'post_status'    => 'inherit',
				                                        'guid'           => "https://example.com/test-image-$i.jpg"
			                                        ] );
		}

		// 2. Create a post that contains all images so they get indexed
		$content = '';
		for ( $i = 1; $i <= $this->number_of_images; $i++ ) {
			$content .= '<img src="https://example.com/test-image-' . $i . '.jpg" alt="Test Image ' . $i . '" />';
		}
		$post_id = $I->havePostInDatabase( [
			                                   'post_title'   => 'Post with all images',
			                                   'post_content' => $content,
			                                   'post_status'  => 'publish',
		                                   ] );
		// 3. Visit the post to trigger indexing
		$I->amOnPage( '/? p=' . $post_id );

		// 4. Set plugin options (Pro feature)
		$isc_options = $I->grabOptionFromDatabase( 'isc_options' );
		$isc_options['images_per_page']             = $this->per_page_limit;
		$isc_options['global_list_included_images'] = 'with_sources'; // Pro feature
		$I->haveOptionInDatabase( 'isc_options', $isc_options );

		// 5. Create the Global List page
		$I->havePageInDatabase( [
			                        'post_name'    => 'global-list',
			                        'post_title'   => 'Global List',
			                        'post_content' => '<p>Here is the Global List with images that have sources</p>[isc_list_all]'
		                        ] );
	}

	/**
	 * Test that only images with sources are displayed.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_only_images_with_sources_are_displayed( \AcceptanceTester $I ) {
		$I->amOnPage( '/global-list' );

		// Images 5-8 (standard source) should be visible on that first page
		for ( $i = 5; $i <= 8; $i++ ) {
			$I->see( "Test Image $i" );
		}

		$I->dontSee( "Test Image 9" );

		$I->amOnPage( '/global-list?isc-page=2' );

		// Images 1-4 (explicit source) should be visible on the second page, since pagination is 4 per page
		for ( $i = 1; $i <= 4; $i++ ) {
			$I->see( "Test Image $i" );
			if ( $i <= 4 ) {
				$I->see( "Author $i" );
			}
		}

		$I->dontSee( "Test Image 9" );

		// no next page
		$I->dontSeeElement( 'a.next.page-numbers' );
	}

	/**
	 * Test pagination with 'with_sources' filter.
	 * 8 images with sources, 5 per page = 2 pages.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_pagination_with_sources_filter( \AcceptanceTester $I ) {
		$I->amOnPage( '/global-list' );

		// First page should show 4 items (images 8, 7, 6, 5)
		$I->see( "Test Image 8" );
		$I->see( "Test Image 7" );
		$I->see( "Test Image 6" );
		$I->see( "Test Image 5" );

		// These should be on page 2
		$I->dontSee( "Test Image 4" );
		$I->dontSee( "Test Image 3" );
		$I->dontSee( "Test Image 2" );
		$I->dontSee( "Test Image 1" );

		// Verify pagination exists
		$I->seeElement( 'a.next.page-numbers' );

		$I->amOnPage( '/global-list?isc-page=2' );

		// Second page should show 4 items (images 4, 3, 2, 1)
		$I->see( "Test Image 4" );
		$I->see( "Test Image 3" );
		$I->see( "Test Image 2" );
		$I->see( "Test Image 1" );

		// First page images should not be visible
		$I->dontSee( "Test Image 8" );
		$I->dontSee( "Test Image 7" );

		// No more pages
		$I->dontSeeElement( 'a.next.page-numbers' );
	}

	/**
	 * Test 'style=list' removes pagination.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_style_list_removes_pagination( \AcceptanceTester $I ) {
		// Create a new page with style="list"
		$I->havePageInDatabase( [
			                        'post_name'    => 'global-list-simple',
			                        'post_title'   => 'Global List Simple',
			                        'post_content' => '<p>Simple list</p>[isc_list_all style="list"]'
		                        ] );

		$I->amOnPage( '/global-list-simple' );

		// All 8 images with sources should be visible on one page
		for ( $i = 1; $i <= 8; $i++ ) {
			$I->see( "Test Image $i" );
		}

		// No pagination should exist
		$I->dontSeeElement( 'a.next.page-numbers' );
		$I->dontSeeElement( 'a.prev.page-numbers' );
		$I->dontSeeElement( '.isc-paginated-links' );
	}

	/**
	 * Test combination:  with_sources + standard_source exclude.
	 * Should show only images with explicit source (not standard source).
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_with_sources_and_exclude_standard_source( \AcceptanceTester $I ) {
		// Enable standard source exclusion
		$isc_options                    = $I->grabOptionFromDatabase( 'isc_options' );
		$isc_options['standard_source'] = 'exclude';
		$I->haveOptionInDatabase( 'isc_options', $isc_options );

		$I->amOnPage( '/global-list' );

		// Images 1-4 (explicit source) should be visible
		for ( $i = 1; $i <= 4; $i++ ) {
			$I->see( "Test Image $i" );
			$I->see( "Author $i" );
		}

		// Images 5-8 (standard source) should NOT be visible (excluded)
		for ( $i = 5; $i <= 8; $i++ ) {
			$I->dontSee( "Test Image $i" );
		}

		// Images 9-12 (no source) should NOT be visible
		for ( $i = 9; $i <= 12; $i++ ) {
			$I->dontSee( "Test Image $i" );
		}

		// Only 4 images, should be one page
		$I->dontSeeElement( 'a.next.page-numbers' );
	}

	/**
	 * Test combination: with_sources + included != 'all'.
	 * Should show only images that have sources AND are attached to posts.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_with_sources_and_attached_to_posts( \AcceptanceTester $I ) {
		// Change included to empty (not 'all')
		$isc_options                                = $I->grabOptionFromDatabase( 'isc_options' );
		$isc_options['global_list_included_images'] = 'with_sources';
		$I->haveOptionInDatabase( 'isc_options', $isc_options );

		// Create a new page without 'included="all"'
		$I->havePageInDatabase( [
			                        'post_name'    => 'global-list-attached',
			                        'post_title'   => 'Global List Attached',
			                        'post_content' => '<p>Only attached images with sources</p>[isc_list_all]'
		                        ] );

		$I->amOnPage( '/global-list-attached' );

		// First page:  images 8, 7, 6, 5 with sources should be visible
		$I->see( "Test Image 8" );
		$I->see( "Test Image 7" );
		$I->see( "Test Image 6" );
		$I->see( "Test Image 5" );

		// Pagination should exist
		$I->seeElement( 'a.next.page-numbers' );

		$I->amOnPage( '/global-list-attached?isc-page=2' );

		// Second page: images 4, 3, 2, 1 with sources should be visible
		$I->see( "Test Image 4" );
		$I->see( "Test Image 3" );
		$I->see( "Test Image 2" );
		$I->see( "Test Image 1" );

		// Check across both pages that images 9-12 without sources are NOT visible
		$I->amOnPage( '/global-list-attached' );
		for ( $i = 9; $i <= 12; $i++ ) {
			$I->dontSee( "Test Image $i" );
		}

		$I->amOnPage( '/global-list-attached?isc-page=2' );
		for ( $i = 9; $i <= 12; $i++ ) {
			$I->dontSee( "Test Image $i" );
		}
	}
}