<?php

namespace ISC\Tests\Acceptance\Pblc\Global_List;

/**
 * Test that only image attachments are included in the Global List when the option is enabled.
 * This tests the post_mime_type filter in the WP_Query.
 */
class Global_List_Images_Only_Filter_Cest {

	/**
	 * Array to store IDs of created attachments.
	 */
	private array $attachments = [];

	/**
	 * Number of items to display per page in the Global List.
	 */
	protected int $per_page_limit = 10;

	/**
	 * Set up the test environment before each test method.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function _before( \AcceptanceTester $I ) {
		// 1. Create image attachments (should be included)
		for ( $i = 1; $i <= 3; $i++ ) {
			$attachmentId = $I->havePostInDatabase( [
				                                        'post_type'      => 'attachment',
				                                        'post_mime_type' => 'image/jpeg',
				                                        'post_title'     => "Test Image $i",
				                                        'post_content'   => '',
				                                        'post_status'    => 'inherit',
				                                        'guid'           => "https://example.com/test-image-$i. jpg"
			                                        ] );

			$I->havePostMetaInDatabase( $attachmentId, 'isc_image_source', "Author $i" );
			$this->attachments['image'][] = $attachmentId;
		}

		// 2. Create PDF attachments (should be excluded)
		for ( $i = 1; $i <= 2; $i++ ) {
			$attachmentId = $I->havePostInDatabase( [
				                                        'post_type'      => 'attachment',
				                                        'post_mime_type' => 'application/pdf',
				                                        'post_title'     => "Test PDF $i",
				                                        'post_content'   => '',
				                                        'post_status'    => 'inherit',
				                                        'guid'           => "https://example.com/test-pdf-$i.pdf"
			                                        ] );

			$I->havePostMetaInDatabase( $attachmentId, 'isc_image_source', "PDF Author $i" );
			$this->attachments['pdf'][] = $attachmentId;
		}

		// 3. Create video attachments (should be excluded)
		for ( $i = 1; $i <= 2; $i++ ) {
			$attachmentId = $I->havePostInDatabase( [
				                                        'post_type'      => 'attachment',
				                                        'post_mime_type' => 'video/mp4',
				                                        'post_title'     => "Test Video $i",
				                                        'post_content'   => '',
				                                        'post_status'    => 'inherit',
				                                        'guid'           => "https://example.com/test-video-$i. mp4"
			                                        ] );

			$I->havePostMetaInDatabase( $attachmentId, 'isc_image_source', "Video Author $i" );
			$this->attachments['video'][] = $attachmentId;
		}

		// 4. Create a post that references all attachments
		$content = '';
		// Add images
		for ( $i = 1; $i <= 3; $i++ ) {
			$content .= '<img src="https://example.com/test-image-' . $i . '.jpg" alt="Test Image ' . $i . '" />';
		}
		// Add PDFs as links
		for ( $i = 1; $i <= 2; $i++ ) {
			$content .= '<a href="https://example.com/test-pdf-' . $i . '.pdf">PDF ' . $i . '</a>';
		}
		// Add videos
		for ( $i = 1; $i <= 2; $i++ ) {
			$content .= '<video src="https://example.com/test-video-' . $i . '.mp4"></video>';
		}

		$post_id = $I->havePostInDatabase( [
			                                   'post_title'   => 'Post with mixed media',
			                                   'post_content' => $content,
			                                   'post_status'  => 'publish',
		                                   ] );

		// 5. Visit the post to trigger indexing
		$I->amOnPage( '/?p=' . $post_id );

		// 6. Set plugin options
		$isc_options = $I->grabOptionFromDatabase( 'isc_options' );
		$isc_options['images_per_page']             = $this->per_page_limit;
		$isc_options['global_list_included_images'] = 'all';
		$isc_options['images_only']                 = true; // Enable images-only filter
		$I->haveOptionInDatabase( 'isc_options', $isc_options );

		// 7. Create the Global List page
		$I->havePageInDatabase( [
			                        'post_name'    => 'global-list',
			                        'post_title'   => 'Global List',
			                        'post_content' => '<p>Global List with all attachments</p>[isc_list_all included="all"]'
		                        ] );
	}

	/**
	 * Test that only images are displayed when images_only option is enabled.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_only_images_displayed_when_filter_enabled( \AcceptanceTester $I ) {
		$I->amOnPage( '/global-list' );

		// All 3 images should be visible
		for ( $i = 1; $i <= 3; $i++ ) {
			$I->see( "Test Image $i" );
			$I->see( "Author $i" );
		}

		// PDFs should NOT be visible
		for ( $i = 1; $i <= 2; $i++ ) {
			$I->dontSee( "Test PDF $i" );
			$I->dontSee( "PDF Author $i" );
		}

		// Videos should NOT be visible
		for ( $i = 1; $i <= 2; $i++ ) {
			$I->dontSee( "Test Video $i" );
			$I->dontSee( "Video Author $i" );
		}
	}

	/**
	 * Test that all attachments are displayed when images_only option is disabled.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_all_attachments_displayed_when_filter_disabled( \AcceptanceTester $I ) {
		// Disable images_only filter
		$isc_options                = $I->grabOptionFromDatabase( 'isc_options' );
		$isc_options['images_only'] = false;
		$I->haveOptionInDatabase( 'isc_options', $isc_options );

		// Create a new page to avoid caching
		$I->havePageInDatabase( [
			                        'post_name'    => 'global-list-all-types',
			                        'post_title'   => 'Global List All Types',
			                        'post_content' => '<p>Global List with all attachment types</p>[isc_list_all included="all"]'
		                        ] );

		$I->amOnPage( '/global-list-all-types' );

		// All 3 images should be visible
		for ( $i = 1; $i <= 3; $i++ ) {
			$I->see( "Test Image $i" );
		}

		// PDFs should be visible
		for ( $i = 1; $i <= 2; $i++ ) {
			$I->see( "Test PDF $i" );
		}

		// Videos should be visible
		for ( $i = 1; $i <= 2; $i++ ) {
			$I->see( "Test Video $i" );
		}
	}

	/**
	 * Test that images_only filter works with other filters (e.g., standard_source exclude).
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_images_only_combined_with_standard_source_exclude( \AcceptanceTester $I ) {
		// Create an image with standard source
		$standard_source_image = $I->havePostInDatabase( [
			                                                 'post_type'      => 'attachment',
			                                                 'post_mime_type' => 'image/png',
			                                                 'post_title'     => 'Standard Source Image',
			                                                 'post_content'   => '',
			                                                 'post_status'    => 'inherit',
			                                                 'guid'           => 'https://example.com/standard-image.png'
		                                                 ] );
		$I->havePostMetaInDatabase( $standard_source_image, 'isc_image_source_own', '1' );

		// Create a PDF with standard source
		$standard_source_pdf = $I->havePostInDatabase( [
			                                               'post_type'      => 'attachment',
			                                               'post_mime_type' => 'application/pdf',
			                                               'post_title'     => 'Standard Source PDF',
			                                               'post_content'   => '',
			                                               'post_status'    => 'inherit',
			                                               'guid'           => 'https://example.com/standard-pdf.pdf'
		                                               ] );
		$I->havePostMetaInDatabase( $standard_source_pdf, 'isc_image_source_own', '1' );

		// Add them to a post
		$post_id = $I->havePostInDatabase( [
			                                   'post_title'   => 'Post with standard sources',
			                                   'post_content' => '<img src="https://example.com/standard-image.png" /><a href="https://example.com/standard-pdf.pdf">PDF</a>',
			                                   'post_status'  => 'publish',
		                                   ] );
		$I->amOnPage( '/?p=' . $post_id );

		// Enable standard source exclusion
		$isc_options                    = $I->grabOptionFromDatabase( 'isc_options' );
		$isc_options['standard_source'] = 'exclude';
		$I->haveOptionInDatabase( 'isc_options', $isc_options );

		$I->amOnPage( '/global-list' );

		// Standard source image should NOT be visible (excluded by standard_source filter)
		$I->dontSee( 'Standard Source Image' );

		// Standard source PDF should NOT be visible (excluded by MIME type AND standard_source)
		$I->dontSee( 'Standard Source PDF' );

		// Regular images should still be visible
		$I->see( 'Test Image 1' );
	}

	/**
	 * Test correct count with mixed attachment types.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_correct_count_with_images_only_filter( \AcceptanceTester $I ) {
		$I->amOnPage( '/global-list' );

		// Should see exactly 3 rows (3 images)
		$I->seeNumberOfElements( 'tr', 3 );

		// Should NOT have pagination (only 3 items with per_page=10)
		$I->dontSeeElement( 'a.next.page-numbers' );
	}
}