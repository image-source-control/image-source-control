<?php

namespace ISC\Tests\Acceptance;

use AcceptanceTester;

/**
 * Test if an image outside the main content appears in the Global List if the appropriate option is set
 */
class Global_List_Image_Outside_Content_Cest {

	private $images;
	private $posts;
	private $outsideImage;

	private $muPluginPath;

	protected $number_of_images = 5;
	protected $number_of_posts = 5;

	public function _before( \AcceptanceTester $I ) {
		// Create 2 simulated images using havePostInDatabase
		$this->images = [];
		for ( $i = 1; $i <= $this->number_of_images; $i ++ ) {
			$attachmentId   = $I->havePostInDatabase( [
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

		// Create 5 posts with specific image assignments using havePostInDatabase
		$this->posts = [];
		for ( $i = 1; $i <= $this->number_of_posts; $i ++ ) {
			$content = '';
			for ( $j = 0; $j < min( $i, count( $this->images ) ); $j ++ ) {
				$content .= '<img src="https://example.com/test-image-' . ( $j + 1 ) . '.jpg" alt="Test Image" />';
			}
			$this->posts[] = $I->havePostInDatabase( [
				                                         'post_title' => 'Post ' . $i,
				                                         'post_content' => $content,
			                                         ] );
		}

		// Add the Global List page
		$I->havePageInDatabase( [
			                       'ID'          => 123,
			                       'post_name'   => 'global-list',
			                       'post_title'  => 'Global List',
			                       'post_content' => '<p>Here is the Global List with all images</p>[isc_list_all]'
		                       ] );

		// Add one simulated image outside any post content using havePostInDatabase and display it using a hook
		$this->outsideImage = $I->havePostInDatabase( [
			                                              'post_type'      => 'attachment',
			                                              'post_mime_type' => 'image/jpeg',
			                                              'post_title'     => 'Test Image Outside',
			                                              'post_content'   => '',
			                                              'post_status'    => 'inherit',
			                                              'guid'           => 'https://example.com/test-image-outside.jpg'
		                                              ] );
		$I->havePostMetaInDatabase( $this->outsideImage, 'isc_image_source', "Author Outside" );

		/**
		 * Inject an image into the footer
		 * Since we cannot use add_action() here, we need to dynamically inject a mu-plugin with that code
		 */
		$this->muPluginPath = codecept_root_dir( '../../../wp-content/mu-plugins/mu-plugin-add-footer.php' );
		if ( ! file_exists( $this->muPluginPath ) ) {
			file_put_contents( $this->muPluginPath, '<?php add_action("wp_footer", function() { echo "<img src=\'https://example.com/test-image-outside.jpg\' alt=\'Test Image Outside\' />"; });' );
		}
	}

	/**
	 * Without changing the options, the Global List should not include the image outside the content
	 */
	public function test_global_list_no_outside_image( \AcceptanceTester $I ) {
		$this->open_all_posts( $I );

		// switch to the Global List page
		$I->amOnPage( '/global-list' );

		$this->check_global_list_post_links( $I );
	}

	/**
	 * Is the outside image indexed when the option "Global List > Index images outside the content" is enabled?
	 */
	public function test_global_list_outside_indexing( \AcceptanceTester $I ) {
		/**
		 * Enable the option to index also images outside the content
		 */
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		$existingOption['global_list_indexed_images'] = 1;
		$I->haveOptionInDatabase( 'isc_options', $existingOption );

		$this->open_all_posts( $I );

		// switch to the Global List page
		$I->amOnPage( '/global-list' );
		$I->see( "Author Outside" );

		// Check the number of links to posts; they are differently distributed, e.g., Post 5 has all images plus the outside image, so appears 6 times in the Global List
		$this->check_global_list_post_links( $I, 1 );
	}

	/**
	 * Is the outside image indexed when the option "Per-page list > Images on the whole page" is enabled?
	 */
	public function test_per_page_list_whole_page( \AcceptanceTester $I ) {
		/**
		 * Enable the option to index also images outside the content
		 */
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		$existingOption['list_included_images'] = 'body_img';
		$I->haveOptionInDatabase( 'isc_options', $existingOption );

		$this->open_all_posts( $I );

		// switch to the Global List page
		$I->amOnPage( '/global-list' );
		$I->see( "Author Outside" );

		// Check the number of links to posts; they are differently distributed, e.g., Post 5 has all images plus the outside image, so appears 6 times in the Global List
		$this->check_global_list_post_links( $I, 1 );
	}

	/**
	 * Is the outside image indexed when the option "Per-page list > Any image URL" is enabled?
	 */
	public function test_per_page_list_any_image_url( \AcceptanceTester $I ) {
		/**
		 * Enable the option to index also images outside the content
		 */
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		$existingOption['list_included_images'] = 'body_urls';
		$I->haveOptionInDatabase( 'isc_options', $existingOption );

		$this->open_all_posts( $I );

		// switch to the Global List page
		$I->amOnPage( '/global-list' );
		$I->see( "Author Outside" );

		$this->check_global_list_post_links( $I, 1 );
	}

	/**
	 * Is the outside image indexed when the option "Overlay > Images on the whole page" is enabled?
	 */
	public function test_overlay_whole_page( \AcceptanceTester $I ) {
		/**
		 * Enable the option to index also images outside the content
		 */
		$existingOption = $I->grabOptionFromDatabase('isc_options');
		$existingOption['overlay_included_images'] = 'body_img';
		$I->haveOptionInDatabase( 'isc_options', $existingOption );

		$this->open_all_posts( $I );

		// switch to the Global List page
		$I->amOnPage( '/global-list' );
		$I->see( "Author Outside" );

		$this->check_global_list_post_links( $I, 1 );
	}

	/**
	 * Index the posts by visiting them
	 */
	private function open_all_posts( \AcceptanceTester $I ) {
		// we need to go through all posts
		foreach ( $this->posts as $postId ) {
			$I->amOnPage( '/?p=' . $postId );
		}
	}

	/**
	 * Check the number of links to posts in the Global List
	 *
	 * @param \AcceptanceTester $I      the tester
	 * @param int               $offset the offset for the number of links
	 */
	private function check_global_list_post_links( \AcceptanceTester $I, int $offset = 0 ) {
		// Check the number of links to posts; they are differently distributed, e.g., Post 5 has all images plus the outside image, so appears 6 times in the Global List
		$I->seeNumberOfElements( '//a[contains(text(), "Post 1")]', 1 + $offset );
		$I->seeNumberOfElements( '//a[contains(text(), "Post 2")]', 2 + $offset );
		$I->seeNumberOfElements( '//a[contains(text(), "Post 3")]', 3 + $offset );
		$I->seeNumberOfElements( '//a[contains(text(), "Post 4")]', 4 + $offset );
		$I->seeNumberOfElements( '//a[contains(text(), "Post 5")]', 5 + $offset );
	}

	/**
	 * Remove the mu plugin again
	 *
	 * @return void
	 */
	public function _after(\AcceptanceTester $I) {
		// Delete the mu-plugin after the test
		if ( file_exists( $this->muPluginPath ) ) {
			unlink( $this->muPluginPath );
		}
	}
}
