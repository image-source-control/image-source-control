<?php

namespace ISC\Tests\Acceptance\Pblc;

/**
 * Test the Global List option that limits displayed images based on source information.
 */
class Global_List_Included_Images_With_Sources_Cest {

	/**
	 * Array to store IDs of created posts.
	 *
	 * @var array
	 */
	private array $posts = [];

	/**
	 * Slug for the Global List page.
	 *
	 * @var string
	 */
	private string $global_list_slug = 'global-list-filtered-images';

	/**
	 * Set up the test environment before each test method.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function _before( \AcceptanceTester $I ) {
		$isc_options                                = $I->grabOptionFromDatabase( 'isc_options' );
		$isc_options['global_list_included_images'] = 'with_sources'; // Key option for this test
		$isc_options['standard_source']             = 'custom_text';    // Use custom text for standard source
		$isc_options['standard_source_text']        = 'Standard Copyright Text'; // The text to expect for standard sources
		$I->haveOptionInDatabase( 'isc_options', $isc_options );

		// Image 1: Explicit source, should be visible
		$image_1_id = $I->havePostInDatabase( [
			                                      'post_type'      => 'attachment',
			                                      'post_mime_type' => 'image/jpeg',
			                                      'post_title'     => 'Image With Explicit Source',
			                                      'post_status'    => 'inherit',
			                                      'guid'           => 'https://example.com/image-explicit.jpg'
		                                      ] );
		$I->havePostMetaInDatabase( $image_1_id, 'isc_image_source', 'Explicit Source Alpha' );

		// Image 2: Standard source (isc_image_source_own = '1'), should be visible
		$image_2_id = $I->havePostInDatabase( [
			                                      'post_type'      => 'attachment',
			                                      'post_mime_type' => 'image/jpeg',
			                                      'post_title'     => 'Image With Standard Source',
			                                      'post_status'    => 'inherit',
			                                      'guid'           => 'https://example.com/image-standard.jpg'
		                                      ] );
		$I->havePostMetaInDatabase( $image_2_id, 'isc_image_source_own', '1' );
		$I->havePostMetaInDatabase( $image_2_id, 'isc_image_source', '' ); // Ensure explicit source is empty

		// Image 3: No source information, should NOT be visible
		$image_3_id = $I->havePostInDatabase( [
			                                      'post_type'      => 'attachment',
			                                      'post_mime_type' => 'image/jpeg',
			                                      'post_title'     => 'Image With No Source',
			                                      'post_status'    => 'inherit',
			                                      'guid'           => 'https://example.com/image-no-source.jpg'
		                                      ] );

		// Image 4: Explicit source AND standard source flag, should be visible (due to explicit source)
		$image_4_id = $I->havePostInDatabase( [
			                                      'post_type'      => 'attachment',
			                                      'post_mime_type' => 'image/jpeg',
			                                      'post_title'     => 'Image Explicit And Standard',
			                                      'post_status'    => 'inherit',
			                                      'guid'           => 'https://example.com/image-explicit-standard.jpg'
		                                      ] );
		$I->havePostMetaInDatabase( $image_4_id, 'isc_image_source', 'Explicit Source Bravo' );
		$I->havePostMetaInDatabase( $image_4_id, 'isc_image_source_own', '1' );

		// 3. Create a post and embed all images
		$content = '<img src="https://example.com/image-explicit.jpg" alt="Image With Explicit Source" />';
		$content .= '<img src="https://example.com/image-standard.jpg" alt="Image With Standard Source" />';
		$content .= '<img src="https://example.com/image-no-source.jpg" alt="Image With No Source" />';
		$content .= '<img src="https://example.com/image-explicit-standard.jpg" alt="Image Explicit And Standard" />';

		$this->posts[] = $I->havePostInDatabase( [
			                                         'post_title'   => 'Test Post For Filtered Global List',
			                                         'post_content' => $content,
		                                         ] );

		// 4. Create the Global List page
		// Using 'included="all"' to ensure all attachments are initially considered by WP_Query
		$I->havePageInDatabase( [
			                        'post_name'    => $this->global_list_slug,
			                        'post_title'   => 'Global List With Filtered Images',
			                        'post_content' => '[isc_list_all]'
		                        ] );

		// 5. Visit the post to trigger indexing
		$this->open_all_posts( $I );
	}

	/**
	 * Test that the Global List only shows images with explicit or standard sources
	 * when the 'global_list_included_images' option is set to 'with_sources'.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_global_list_shows_only_images_with_sources( \AcceptanceTester $I ) {
		$I->amOnPage( '/' . $this->global_list_slug );

		// Assertions for visible images
		$I->see( 'Explicit Source Alpha' );   // From Image 1 (Explicit Source)
		$I->see( 'Standard Copyright Text' ); // From Image 2 (Standard Source, assuming Image_Sources::get_image_source_text_raw resolves it)
		$I->dontSee( 'Explicit Source Bravo' ); // From Image 4, also shows "Standard Copyright Text" due to standard source flag

		// Assertions for non-visible images
		$I->dontSee( 'Image With No Source' ); // Title of Image 3
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