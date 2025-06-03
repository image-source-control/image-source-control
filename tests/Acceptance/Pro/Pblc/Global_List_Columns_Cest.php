<?php

namespace ISC\Tests\Acceptance\Pro\Pblc;

/**
 * Test the visibility of columns in the Global List based on plugin options.
 */
class Global_List_Columns_Cest {

	/**
	 * Array to store IDs of created image attachments.
	 *
	 * @var array
	 */
	private array $images = [];

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
	private string $global_list_slug = 'global-list-columns-test';

	/**
	 * Default set of columns expected to be visible.
	 * Note: 'thumbnail' is controlled by a separate option 'thumbnail_in_list'.
	 * This is not necessarily the same as the default set by the plugin, which is tested in test_default_columns_visible())
	 *
	 * @var array
	 */
	private array $default_columns = [ 'attachment_id', 'title', 'posts', 'source' ];

	/**
	 * Set up the test environment before each test method.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function _before( \AcceptanceTester $I ) {
		// 1. Create a sample image with source and a post using it.
		$image_id = $I->havePostInDatabase( [
			                                    'post_type'      => 'attachment',
			                                    'post_mime_type' => 'image/jpeg',
			                                    'post_title'     => 'Sample Image for Columns Test',
												'post_status'    => 'inherit',
			                                    'guid'           => 'https://example.com/sample-columns.jpg'
		                                    ] );
		$I->havePostMetaInDatabase( $image_id, 'isc_image_source', 'Sample Source Text' );
		$this->images[] = $image_id;

		$post_content  = '<img src="https://example.com/sample-columns.jpg" alt="Sample Image" />';
		$this->posts[] = $I->havePostInDatabase( [
			                                         'post_title'   => 'Post for Columns Test',
			                                         'post_content' => $post_content,
		                                         ] );

		// 2. Create the Global List page.
		$I->havePageInDatabase( [
			                        'post_name'    => $this->global_list_slug,
			                        'post_title'   => 'Global List Columns Test Page',
			                        'post_content' => '[isc_list_all]'
		                        ] );

		// 3. Visit the post to trigger indexing.
		$this->open_all_posts( $I );
	}

	/**
	 * Helper to set the 'global_list_included_data' and 'thumbnail_in_list' options.
	 *
	 * @param \AcceptanceTester $I                 The acceptance tester instance.
	 * @param array|null        $included_data     Array of column keys to include, or null for default.
	 * @param bool              $thumbnail_in_list Whether to show the thumbnail column.
	 */
	private function set_global_list_options( \AcceptanceTester $I, ?array $included_data, bool $thumbnail_in_list = true ) {
		$isc_options                        = $I->grabOptionFromDatabase( 'isc_options' );
		$isc_options['thumbnail_in_list']   = $thumbnail_in_list;

		if ( $included_data !== null ) {
			$isc_options['global_list_included_data'] = $included_data;
		}
		$I->haveOptionInDatabase( 'isc_options', $isc_options );
	}

	/**
	 * Test default column visibility (all columns enabled).
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_default_columns_visible( \AcceptanceTester $I ) {
		// not using the default data here
		$this->set_global_list_options( $I, null );
		$I->amOnPage( '/' . $this->global_list_slug );

		$I->see( 'Thumbnail', 'th' );
		$I->see( 'Attachment ID', 'th' );
		$I->see( 'Title', 'th' );
		$I->see( 'Attached to', 'th' );
		$I->see( 'Source', 'th' );

		// Check for actual data presence to confirm columns are not just headers
		$I->seeElement( '//div[contains(@class, "isc_all_image_list_box")]//tbody/tr/td/img' ); // Thumbnail img
		$I->see( (string) $this->images[0], '//div[contains(@class, "isc_all_image_list_box")]//tbody/tr/td' ); // Attachment ID
		$I->see( 'Sample Image for Columns Test', '//div[contains(@class, "isc_all_image_list_box")]//tbody/tr/td' ); // Title
		$I->see( 'Post for Columns Test', '//div[contains(@class, "isc_all_image_list_box")]//tbody/tr/td/ul/li/a' ); // Attached to
		$I->see( 'Sample Source Text', '//div[contains(@class, "isc_all_image_list_box")]//tbody/tr/td' ); // Source
	}

	/**
	 * Test when the Thumbnail column is disabled.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_thumbnail_column_disabled( \AcceptanceTester $I ) {
		$this->set_global_list_options( $I, $this->default_columns, false ); // thumbnail_in_list = false
		$I->amOnPage( '/' . $this->global_list_slug );

		$I->dontSee( 'Thumbnail', 'th' );
		$I->dontSeeElement( '//div[contains(@class, "isc_all_image_list_box")]//tbody/tr/td/img' ); // No thumbnail img

		// Ensure other columns are still present
		$I->see( 'Attachment ID', 'th' );
		$I->see( 'Title', 'th' );
		$I->see( 'Attached to', 'th' );
		$I->see( 'Source', 'th' );
	}

	/**
	 * Test when the Attachment ID column is disabled.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_attachment_id_column_disabled( \AcceptanceTester $I ) {
		$columns_without_id = array_filter( $this->default_columns, fn( $col ) => $col !== 'attachment_id' );
		$this->set_global_list_options( $I, $columns_without_id, true );
		$I->amOnPage( '/' . $this->global_list_slug );

		$I->dontSee( 'Attachment ID', 'th' );
		// Check that the ID itself is not present as cell data
		$I->dontSee( (string) $this->images[0], '//div[contains(@class, "isc_all_image_list_box")]//tbody/tr/td[count(preceding-sibling::td)=1 and count(following-sibling::td)=3]' );

		$I->see( 'Thumbnail', 'th' );
		$I->see( 'Title', 'th' );
		$I->see( 'Attached to', 'th' );
		$I->see( 'Source', 'th' );
	}

	/**
	 * Test when the Title column is disabled.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_title_column_disabled( \AcceptanceTester $I ) {
		$columns_without_title = array_filter( $this->default_columns, fn( $col ) => $col !== 'title' );
		$this->set_global_list_options( $I, $columns_without_title, true );
		$I->amOnPage( '/' . $this->global_list_slug );

		$I->dontSee( 'Title', 'th' );
		$I->dontSee( 'Sample Image for Columns Test', '//div[contains(@class, "isc_all_image_list_box")]//tbody/tr/td[count(preceding-sibling::td)=2 and count(following-sibling::td)=2]' );

		$I->see( 'Thumbnail', 'th' );
		$I->see( 'Attachment ID', 'th' );
		$I->see( 'Attached to', 'th' );
		$I->see( 'Source', 'th' );
	}

	/**
	 * Test when the Attached to column is disabled.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_attached_to_column_disabled( \AcceptanceTester $I ) {
		$columns_without_posts = array_filter( $this->default_columns, fn( $col ) => $col !== 'posts' );
		$this->set_global_list_options( $I, $columns_without_posts, true );
		$I->amOnPage( '/' . $this->global_list_slug );

		$I->dontSee( 'Attached to', 'th' );
		$I->dontSee( 'Post for Columns Test', '//div[contains(@class, "isc_all_image_list_box")]//tbody/tr/td/ul/li/a' );

		$I->see( 'Thumbnail', 'th' );
		$I->see( 'Attachment ID', 'th' );
		$I->see( 'Title', 'th' );
		$I->see( 'Source', 'th' );
	}

	/**
	 * Test when the Source column is disabled.
	 *
	 * @param \AcceptanceTester $I The acceptance tester instance.
	 */
	public function test_source_column_disabled( \AcceptanceTester $I ) {
		$columns_without_source = array_filter( $this->default_columns, fn( $col ) => $col !== 'source' );
		$this->set_global_list_options( $I, $columns_without_source, true );
		$I->amOnPage( '/' . $this->global_list_slug );

		$I->dontSee( 'Source', 'th' );
		$I->dontSee( 'Sample Source Text', '//div[contains(@class, "isc_all_image_list_box")]//tbody/tr/td[count(preceding-sibling::td)=4]' );

		$I->see( 'Thumbnail', 'th' );
		$I->see( 'Attachment ID', 'th' );
		$I->see( 'Title', 'th' );
		$I->see( 'Attached to', 'th' );
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