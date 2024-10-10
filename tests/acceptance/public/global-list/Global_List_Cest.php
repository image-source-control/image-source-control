<?php

namespace ISC\Tests\Acceptance;

use AcceptanceTester;

/**
 * Test ISC Plugin Indexing of Images and Posts to show images in the Global List
 */
class Global_List_Cest {

	private $images;
	private $posts;
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
	}

	/**
	 * Test that opening the posts triggers indexing
	 * Also check for image author names in the content of each page
	 * – in the Per-page list which is enabled by default
	 * Check the Global List with default settings
	 */
	public function test_basic_indexing( \AcceptanceTester $I ) {
		// we need to go through all posts and while we are at it, check for the image author names at the bottom of the content
		foreach ( $this->posts as $index => $postId ) {
			$I->amOnPage( '/?p=' . $postId );

			// Check for image author names in the content for images present in the post
			for ( $j = 0; $j < min( $index + 1, $index + 1 ); $j++ ) {
				$I->see( "Author " . ($j + 1) );
			}
		}

		// switch to the Global List page
		$I->amOnPage( '/global-list' );
		$I->seeResponseCodeIs( 200 );

		// Check that all images and author names appear in the Global List
		foreach ( $this->images as $key => $imageId ) {
			// image title
			$I->see( "Test Image " . ( $key + 1 ) );
			// image author
			$I->see( "Author " . ( $key + 1 ) );
			// page with the image on
			$I->see( "Post " . ( $key + 1 ) );
		}

		// Check the number of links to posts; they are differently distributed, e.g., Post 5 has all images, so appears 5 times in the Global List
		$I->seeNumberOfElements( '//a[contains(text(), "Post 1")]', 1 );
		$I->seeNumberOfElements( '//a[contains(text(), "Post 2")]', 2 );
		$I->seeNumberOfElements( '//a[contains(text(), "Post 3")]', 3 );
		$I->seeNumberOfElements( '//a[contains(text(), "Post 4")]', 4 );
		$I->seeNumberOfElements( '//a[contains(text(), "Post 5")]', 5 );
	}
}
