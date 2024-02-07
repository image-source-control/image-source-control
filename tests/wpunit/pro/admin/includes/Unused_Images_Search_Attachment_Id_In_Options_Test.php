<?php

namespace ISC\Pro;

require_once dirname( __FILE__, 6 ) . '/pro/admin/includes/unused-images.php';

/**
 * Testing \ISC\Pro\Unused_Images::search_attachment_id_in_options()
 */
class Unused_Images_Search_Attachment_Id_In_Options_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Image IDs
	 * @var array
	 */
	protected $attachment_ids = [];

	public function setUp(): void {
		parent::setUp();

		// site logo attachment ID
		$attachment_id = rand( 10000, 99999 );
		$this->attachment_ids['site_logo'] = $attachment_id;

		update_option( 'site_logo', $attachment_id );

		// attachment ID as part of a serialized option
		$attachment_id = rand( 10000, 99999 );
		$this->attachment_ids['theme_mods_generatepress'] = $attachment_id;

		update_option( 'theme_mods_generatepress', 'a:3:{s:18:"custom_css_post_id";i:3137;s:11:"custom_logo";i:' . $attachment_id . ';s:18:"nav_menu_locations";a:1:{s:7:"primary";i:8;}}' );
	}

	/**
	 * Test the search_attachment_id_in_options() function to see if it returns a results for a plain option
	 */
	public function test_attachment_id_in_plain_options() {
		$unused_images = new Unused_Images();
		$result        = $unused_images->search_attachment_id_in_options( $this->attachment_ids['site_logo'] );

		// returns one result
		$this->assertCount( 1, $result );
		// the option name in the result is correct
		$this->assertEquals( 'site_logo', $result[0]->option_name );
	}

	/**
	 * Test the search_attachment_id_in_options() function to see if it returns a result for a serialized array
	 */
	public function test_attachment_id_in_serialized_options() {
		$unused_images = new Unused_Images();
		$result        = $unused_images->search_attachment_id_in_options( $this->attachment_ids['theme_mods_generatepress'] );

		// returns one result
		$this->assertCount( 1, $result );
		// the option name in the result is correct
		$this->assertEquals( 'theme_mods_generatepress', $result[0]->option_name );
	}

}
