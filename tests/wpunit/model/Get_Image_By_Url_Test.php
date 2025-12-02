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
	 *  The `-scaled` suffix is added by WordPress when a very large image is scaled down on upload
	 *  While `guid` doesn’t contain the suffix, `_wp_attached_file` does
	 */
	public function test_scaled_image() {
		$image_id = wp_insert_attachment( [
          'guid'      => 'http://isc.local/wp-content/uploads/image2.jpg',
          'post_type' => 'attachment'
      ] );

		// add a _wp_attached_file meta value
		update_post_meta( $image_id, '_wp_attached_file', 'image2-scaled.jpg' );

		// find both versions
		$result = ISC_Model::get_image_by_url( $this->base_url . '/image2.jpg' );
		$this->assertEquals( $image_id, $result );
		$result = ISC_Model::get_image_by_url( $this->base_url . '/image2-scaled.jpg' );
		$this->assertEquals( $image_id, $result );
	}

	/**
	 * Test if the function finds an image despite the "rotated" keyword in the image file
	 * The `-rotated` suffix is added by WordPress when an image is rotated on upload
	 * While `guid` doesn’t contain the suffix, `_wp_attached_file` does
	 */
	public function test_rotated_image() {
		$image_id = wp_insert_attachment( [
            'guid'      => 'http://isc.local/wp-content/uploads/image3.jpg',
			'post_type' => 'attachment'
        ] );

		// add a _wp_attached_file meta value
		update_post_meta( $image_id, '_wp_attached_file', 'image3-rotated.jpg' );

		// find both versions
		$result = ISC_Model::get_image_by_url( $this->base_url . '/image3.jpg' );
		$this->assertEquals( $image_id, $result );
		$result = ISC_Model::get_image_by_url( $this->base_url . '/image3-rotated.jpg' );
		$this->assertEquals( $image_id, $result );
	}

	/**
	 * Test if the function finds the "rotated" and resized version of an image
	 */
	public function test_rotated_resized_image() {
		$image_id = wp_insert_attachment( [
			                                  'guid'      => 'http://isc.local/wp-content/uploads/image4.jpg',
			                                  'post_type' => 'attachment'
		                                  ] );

		// add a _wp_attached_file meta value
		update_post_meta( $image_id, '_wp_attached_file', 'image4-rotated.jpg' );

		// find both versions
		$result = ISC_Model::get_image_by_url( $this->base_url . '/image4-300x200.jpg' );
		$this->assertEquals( $image_id, $result );
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

	/**
	 * Test cropped image with usage of a backup resized version
	 * When using the WordPress image editor to crop an image, the cropped version is overriding the original image.
	 * E.g.,
	 * - original image: https://example.com/wp-content/uploads/image.png
	 * - cropped image: https://example.com/wp-content/uploads/image-e123123123123.png
	 * The original resized versions are still available on the server to restore them.
	 * E.g.,
	 * - original resized version: https://example.com/wp-content/uploads/image-300x200.jpg
	 * While the newly cropped version also has resized versions:
	 * In the database, these old sizes are stored in _wp_attachment_backup_sizes
	 * E.g.,
	 * - cropped resized version: https://example.com/wp-content/uploads/image-e123123123123-300x200.jpg
	 *
	 * The old resized versions can theoretically still be used in the content.
	 * The challenge now is to trace them back to the cropped image.
	 *
	 * Luckily, the wp_posts.guid value still contains the URL of the original image instead of the cropped one.
	 *
	 * get_image_by_url() should be able to find the image ID also for the old resized versions.
	 */
	public function test_find_image_id_for_old_resized_images_after_cropping() {
		// if guid would be image-to-be-cropped-e1743778144168.png, then this test would fail, but WordPress keeps the original URL
		$image_id = wp_insert_attachment( [
			                                  'guid'      => 'https://example.com/wp-content/uploads/image-to-be-cropped.png',
			                                  'post_type' => 'attachment',
		                                  ] );

		// add a _wp_attached_file meta value with the cropped version
		update_post_meta( $image_id, '_wp_attached_file', 'image-to-be-cropped-e1743778144168.png' );

		// shouldn’t be relevant, but good to see the difference
		update_post_meta( $image_id, '_wp_attachment_metadata', [
			'width'    => 1190,
			'height'   => 535,
			'file'     => '2025/03/image-to-be-cropped-e1743778144168.png',
			'filesize' => 856338,
			'sizes'    => [
				'medium' => [
					'file'      => 'image-to-be-cropped-e1743778144168-300x135.png',
					'width'     => 300,
					'height'    => 135,
					'mime-type' => 'image/png',
					'filesize'  => 82755,
				],
				'large' => [
					'file'      => 'image-to-be-cropped-e1743778144168-1024x460.png',
					'width'     => 1024,
					'height'    => 460,
					'mime-type' => 'image/png',
					'filesize'  => 642430,
				],
				'thumbnail' => [
					'file'      => 'image-to-be-cropped-e1743778144168-150x150.png',
					'width'     => 150,
					'height'    => 150,
					'mime-type' => 'image/png',
					'filesize'  => 52461,
				],
			],
		] );

		// add a _wp_attachment_backup_sizes meta value
		update_post_meta( $image_id, '_wp_attachment_backup_sizes', [
			'full-orig' => [
				'width'    => 1344,
				'height'   => 768,
				'filesize' => 1105673,
				'file'     => 'image-to-be-cropped.png',
			],
			'thumbnail-orig' => [
				'file'      => 'image-to-be-cropped-150x150.png',
				'width'     => 150,
				'height'    => 150,
				'mime-type' => 'image/png',
				'filesize'  => 49640,
			],
			'medium-orig' => [
				'file'      => 'image-to-be-cropped-300x171.png',
				'width'     => 300,
				'height'    => 171,
				'mime-type' => 'image/png',
				'filesize'  => 97343,
			],
			'large-orig' => [
				'file'      => 'image-to-be-cropped-1024x585.png',
				'width'     => 1024,
				'height'    => 585,
				'mime-type' => 'image/png',
				'filesize'  => 770043,
			],
		] );

		$result = ISC_Model::get_image_by_url( 'https://example.com/wp-content/uploads/image-to-be-cropped-150x150.png' );
		$this->assertEquals( $image_id, $result, 'Image ID should be found for the old resized version' );
	}
}
