<?php

namespace ISC\Tests\WPUnit\Model;

use \ISC\Tests\WPUnit\WPTestCase;
use \ISC_Model;
use \ISC_Storage_Model;

/**
 * Test if ISC_Model::get_image_by_url() works as expected.
 */
class Get_Image_By_Url_Test extends WPTestCase {

	public function setUp(): void {
		parent::setUp();

		$this->image_id = wp_insert_attachment( [
			'guid'      => 'https://example.com/wp-content/uploads/image.png',
			'post_type' => 'attachment',
		] );

		// add a _wp_attached_file meta value
		update_post_meta( $this->image_id, '_wp_attached_file', 'test.jpg' );

		// for testing a full URL, we need the actual upload directory
		$dir            = wp_get_upload_dir();
		$this->base_url = $dir['baseurl'];
	}

	/**
	 * Test if the function returns 0 for an empty URL
	 */
	public function test_empty_url() {
		$result = ISC_Model::get_image_by_url( '' );
		$this->assertEquals( 0, $result );
	}

	/**
	 * Test if the function returns 0 for an invalid URL
	 */
	public function test_invalid_url() {
		$result = ISC_Model::get_image_by_url( 'invalid_url' );
		$this->assertEquals( 0, $result );
	}

	/**
	 * Test if the function returns 0 for a URL with an invalid extension
	 */
	public function test_invalid_extension() {
		$result = ISC_Model::get_image_by_url( 'https://example.com/image.xyz' );
		$this->assertEquals( 0, $result );
	}

	/**
	 * Test if the function returns an ID when the URL is in storage
	 */
	public function test_image_in_storage() {
		$storage = new ISC_Storage_Model();
		$storage->update_post_id( 'https://example.com/test.jpg', 123 );

		$result = ISC_Model::get_image_by_url( 'https://example.com/test.jpg' );
		$this->assertEquals( 123, $result );
	}

	/**
	 * Test if the function returns 0 when the image is not found in storage or in the database
	 */
	public function test_image_not_found() {
		$result = ISC_Model::get_image_by_url( 'https://example.com/notfound.jpg' );
		$this->assertEquals( 0, $result );
	}

	/**
	 * Test if the function returns an ID using `attachment_url_to_postid`
	 */
	public function test_attachment_url_to_post_id() {
		$result = ISC_Model::get_image_by_url( $this->base_url . '/test.jpg' );
		$this->assertEquals( $this->image_id, $result );
	}

	/**
	 * Test if the function finds an image despite sizes in the image file
	 */
	public function test_with_image_sizes() {
		$result = ISC_Model::get_image_by_url( $this->base_url . '/test-300x200.jpg' );
		$this->assertEquals( $this->image_id, $result );
	}

	/**
	 * Test if the function finds an image despite the "scaled" keyword in the image file
	 */
	public function test_with_scaled_keyword() {
		$result = ISC_Model::get_image_by_url( $this->base_url . '/test-scaled.jpg' );
		$this->assertEquals( $this->image_id, $result );
	}

	/**
	 * Test if the function finds an image despite the "rotated" keyword in the image file
	 */
	public function test_with_rotated_keyword() {
		$result = ISC_Model::get_image_by_url( $this->base_url . '/test-rotated.jpg' );
		$this->assertEquals( $this->image_id, $result );
	}

	/**
	 * Test if the SQL query can locate an attachment based on the GUID.
	 */
	public function test_sql_query_by_guid() {
		$result = ISC_Model::get_image_by_url( 'https://example.com/wp-content/uploads/image.png' );
		$this->assertEquals( $this->image_id, $result );
	}

	/**
	 * Test if the SQL query can locate an attachment based on the GUID with HTTP.
	 */
	public function test_sql_query_by_guid_http() {
		$result = ISC_Model::get_image_by_url( 'http://example.com/wp-content/uploads/image.png' );
		$this->assertEquals( $this->image_id, $result );
	}

	/**
	 * Test if the SQL query returns 0 when no matching GUID is found.
	 */
	public function test_sql_query_no_match() {
		$result = ISC_Model::get_image_by_url( 'https://example.com/wp-content/uploads/nomatch.png' );
		$this->assertEquals( 0, $result );
	}

	/**
	 * Test if the SQL query is modified by the `isc_get_image_by_url_query` filter.
	 */
	public function test_sql_query_filter() {
		add_filter( 'isc_get_image_by_url_query', function( $query, $newurl ) {
			return "SELECT ID, guid FROM wp_posts WHERE post_type='attachment' AND guid = 'test' LIMIT 1";
		}, 10, 2 );

		$result = ISC_Model::get_image_by_url( 'https://example.com/wp-content/uploads/image.png' );
		$this->assertEquals( 0, $result ); // The modified query should not find any matching records
	}

	/**
	 * Test with image file names containing "DALL·E", which is an AI image generator from Open AI
	 * The "·" in the file name previously caused an issue since it is not a valid URL character, but WordPress accepts it, so we implemented a workaround
	 */
	public function test_dall_e() {
		$image_id = wp_insert_attachment( [
			'guid'      => 'https://example.com/wp-content/uploads/DALL·E.png',
			'post_type' => 'attachment',
		] );

		// add a _wp_attached_file meta value
		update_post_meta( $image_id, '_wp_attached_file', 'DALL·E.png' );

		$result = ISC_Model::get_image_by_url( 'https://example.com/wp-content/uploads/DALL·E.png' );
		$this->assertEquals( $image_id, $result );
	}
}
