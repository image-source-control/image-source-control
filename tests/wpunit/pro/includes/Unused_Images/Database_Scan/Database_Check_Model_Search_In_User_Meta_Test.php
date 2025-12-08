<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Database_Scan;

use ISC\Pro\Unused_Images\Database_Scan\Database_Check_Model;
use ISC\Tests\WPUnit\WPTestCase;

class Database_Check_Model_Search_In_User_Meta_Test extends WPTestCase {

	/**
	 * User ID for testing
	 * @var int
	 */
	protected $user_id;

	public function setUp(): void {
		parent::setUp();

		$this->user_id = $this->factory->user->create( [
			                                         'user_email' => 'user@example.com',
			                                         'user_login' => 'user1',
		                                         ] );
	}

	/**
	 * Test the search_filepath_in_user_meta() function to see if it returns the only image within user meta.
	 */
	public function test_file_path_in_user_meta() {
		update_user_meta( $this->user_id, 'profile_picture', 'https://example.com/image-four.png' );

		$result = ( new Database_Check_Model() )->search_filepath_in_user_meta( 'image-four' );

		$this->assertCount( 1, $result );
		$actual_object = $result[0];
		$this->assertEquals( 'profile_picture', $actual_object->meta_key );
		$this->assertEquals( $this->user_id, $actual_object->user_id );
	}

	/**
	 * Test the search_filepath_in_user_meta() function returns no result for an unmatched string.
	 */
	public function test_file_path_in_user_meta_no_result() {
		update_user_meta( $this->user_id, 'profile_picture', 'https://example.com/image-xyz.png' );

		$result = ( new Database_Check_Model() )->search_filepath_in_user_meta( 'image-four' );

		$this->assertCount( 0, $result );
	}
}