<?php

namespace ISC\Tests\WPUnit\Pro\Admin\Includes;

use \ISC\Tests\WPUnit\WPTestCase;
use \ISC\Pro\Unused_Images;

require_once dirname( __FILE__, 6 ) . '/pro/admin/includes/unused-images.php';

/**
 * Testing \ISC\Pro\Unused_Images::search_attachment_id_in_content()
 */
class Unused_Images_Search_Attachment_Id_In_Content_Test extends WPTestCase {

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
	 * Prepare the test environment
	 */
	public function _before() {
		parent::_before();

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
	 * Test the search_attachment_id_in_content() function to see if it returns a results
	 * for an image ID with the appropriate format in the content
	 */
	public function test_attachment_id_in_content() {
		// enable the "ID in content" option
		$isc_options                            = \ISC_Class::get_instance()->get_isc_options();
		$isc_options['unused_images']['deep_checks'] = [ 'ID in content' ];
		update_option( 'isc_options', $isc_options );

		$unused_images = new Unused_Images();
		$result        = $unused_images->search_attachment_id_in_content( $this->attachment_id );

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
		$isc_options                            = \ISC_Class::get_instance()->get_isc_options();
		$isc_options['unused_images']['deep_checks'] = [];
		update_option( 'isc_options', $isc_options );

		$unused_images = new Unused_Images();
		$result        = $unused_images->search_attachment_id_in_content( $this->attachment_id );

		// returns one result
		$this->assertCount( 0, $result );
	}

	/**
	 * No result if the image ID does not have the right format
	 */
	public function test_attachment_id_in_content_wrong_pattern() {
		// enable the "ID in content" option
		$isc_options                            = \ISC_Class::get_instance()->get_isc_options();
		$isc_options['unused_images']['deep_checks'] = [ 'ID in content' ];
		update_option( 'isc_options', $isc_options );

		$attachment_id = rand( 10000, 99999 );

		// create the post
		$this->factory()->post->create(
			[
				'post_content' => 'Some content with unsupported="' . $attachment_id . '" in it',
			]
		);

		$unused_images = new Unused_Images();
		$result        = $unused_images->search_attachment_id_in_content( $attachment_id );

		// returns one result
		$this->assertCount( 0, $result );
	}
}
