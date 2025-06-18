<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images;

use ISC\Options;
use ISC\Pro\Unused_Images\Database_Check_Model;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing \ISC\Pro\Unused_Images\Database_Check_Model::search_attachment_id_in_content()
 */
class Database_Check_Model_Search_Attachment_Id_In_Content_Test extends WPTestCase {

	/**
	 * Attachment ID
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Post ID
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Prepare the test environment before each test method.
	 */
	public function setUp(): void {
		parent::setUp();

		// main image ID
		$this->attachment_id = rand( 10000, 99999 );

		// create the post
		$this->post_id = $this->factory()->post->create(
			[
				'post_content' => 'Some content with image="' . $this->attachment_id . '" in it',
			]
		);
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		delete_option( 'isc_options' );

		parent::tearDown();
	}

	/**
	 * Test the search_attachment_id_in_content() function to see if it returns a results
	 * for an image ID with the appropriate format in the content
	 */
	public function test_attachment_id_in_content() {
		// enable the "ID in content" option
		$isc_options                                 = Options::get_options();
		$isc_options['unused_images']['deep_checks'] = [ 'ID in content' ];
		update_option( 'isc_options', $isc_options );

		$result = ( new Database_Check_Model() )->search_attachment_id_in_content( $this->attachment_id );

		// returns one result
		$this->assertCount( 1, $result );
		// the post ID in the result is correct
		$this->assertEquals( $this->post_id, $result[0]->ID );
	}

	/**
	 * Don’t return a result if the appropriate option isn’t enabled
	 */
	public function test_attachment_id_in_content_not_enabled() {
		// disable the "ID in content" option
		$isc_options                                 = Options::get_options();
		$isc_options['unused_images']['deep_checks'] = [];
		update_option( 'isc_options', $isc_options );

		$result = ( new Database_Check_Model() )->search_attachment_id_in_content( $this->attachment_id );

		// returns one result
		$this->assertCount( 0, $result );
	}

	/**
	 * No result if the image ID does not have the right format
	 */
	public function test_attachment_id_in_content_wrong_pattern() {
		// enable the "ID in content" option
		$isc_options                                 = Options::get_options();
		$isc_options['unused_images']['deep_checks'] = [ 'ID in content' ];
		update_option( 'isc_options', $isc_options );

		$attachment_id = rand( 10000, 99999 );

		// create the post
		$this->factory()->post->create(
			[
				'post_content' => 'Some content with unsupported="' . $attachment_id . '" in it',
			]
		);

		$result = ( new Database_Check_Model() )->search_attachment_id_in_content( $attachment_id );

		// returns one result
		$this->assertCount( 0, $result );
	}
}
