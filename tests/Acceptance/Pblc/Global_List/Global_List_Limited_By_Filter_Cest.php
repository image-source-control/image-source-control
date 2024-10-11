<?php

namespace ISC\Tests\Acceptance\Pblc\Global_List;

/**
 * Test if limiting the index of posts per image to 2 is reflected in the Global List
 */
class Global_List_Limited_By_Filter_Cest {

	private $images;
	private $posts;
	private $muPluginPath;

	protected $number_of_images = 5;
	protected $number_of_posts = 5;

	public function _before( \AcceptanceTester $I ) {
		// Create 5 simulated images using havePostInDatabase
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

		/**
		 * Add the filter to limit the number of posts per image to 2
		 */
		$this->muPluginPath = codecept_root_dir( '../../../wp-content/mu-plugins/mu-plugin-post-limit.php' );
		if ( ! file_exists( $this->muPluginPath ) ) {
			file_put_contents( $this->muPluginPath, '<?php add_filter("isc_image_posts_meta_limit", function( $limit ) { return 2; });' );
		}
	}

	/**
	 * The Global list should show only the first 2 posts for each image
	 */
	public function test_indexing_with_filter( \AcceptanceTester $I ) {
		// we need to go through all posts and while we are at it, check for the image author names at the bottom of the content
		foreach ( $this->posts as $index => $postId ) {
			$I->amOnPage( '/?p=' . $postId );
		}

		// switch to the Global List page
		$I->amOnPage( '/global-list' );

		// Check the number of links to posts; they are differently distributed, e.g., Post 5 has all images, so appears 5 times in the Global List
		$I->seeNumberOfElements( '//a[contains(text(), "Post 1")]', 1 );
		$I->seeNumberOfElements( '//a[contains(text(), "Post 2")]', 2 );
		$I->seeNumberOfElements( '//a[contains(text(), "Post 3")]', 2 );
		$I->seeNumberOfElements( '//a[contains(text(), "Post 4")]', 2 );
		$I->seeNumberOfElements( '//a[contains(text(), "Post 5")]', 2 );
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
