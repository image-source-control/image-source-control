<?php

namespace ISC\Tests\Acceptance\Pblc\Global_List;

/**
 * Test ISC Plugin Global List with posts that have no title
 */
class Global_List_Empty_Title_Cest {

	private $image_id;
	private $post_id;

	public function _before( \AcceptanceTester $I ) {
		// Create a test image
		$this->image_id = $I->havePostInDatabase( [
			'post_type'      => 'attachment',
			'post_mime_type' => 'image/jpeg',
			'post_title'     => 'Test Image',
			'post_content'   => '',
			'post_status'    => 'inherit',
			'guid'           => 'https://example.com/test-image.jpg'
		] );
		$I->havePostMetaInDatabase( $this->image_id, 'isc_image_source', 'Test Author' );

		// Create a post without a title that uses the image
		$this->post_id = $I->havePostInDatabase( [
			'post_title' => '',
			'post_content' => '<img src="https://example.com/test-image.jpg" alt="Test Image" />',
		] );

		// Add the Global List page
		$I->havePageInDatabase( [
			'ID'          => 123,
			'post_name'   => 'global-list',
			'post_title'  => 'Global List',
			'post_content' => '<p>Here is the Global List with all images</p>[isc_list_all]'
		] );
	}

	/**
	 * Test that posts without a title show the post ID in the Global List
	 */
	public function test_post_without_title_shows_id( \AcceptanceTester $I ) {
		// Open the post to trigger indexing
		$I->amOnPage( '/?p=' . $this->post_id );
		$I->seeResponseCodeIs( 200 );

		// Visit the Global List page
		$I->amOnPage( '/global-list' );
		$I->seeResponseCodeIs( 200 );

		// Check that the image appears in the Global List
		$I->see( 'Test Image' );
		$I->see( 'Test Author' );

		// Check that the post is referenced by its ID (e.g., #123) instead of empty text
		$I->see( '#' . $this->post_id );
	}
}
