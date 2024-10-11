<?php

namespace ISC\Tests\WPUnit\Includes;

use \ISC\Tests\WPUnit\WPTestCase;
/**
 * Testing the Unused_Images class
 */
class Unused_Images_Basic_Test extends WPTestCase {

	/**
	 * Attachment IDs
	 */
	protected $attachment_ids = [];

	/**
	 * Setup attachments
	 */
	public function setUpAttachments(): void {

		$attachment_id = $this->factory->post->create( [ 'post_type' => 'attachment', 'post_title' => 'Image 1 without any usage' ] );
		add_post_meta( $attachment_id, '_wp_attachment_metadata', [
			'file'     => '2024/01/image.jpg',
			'filesize' => 713072,
			'sizes'    => [
				'medium'       => [
					'file'     => '2024/01/image-300x300.jpg',
					'filesize' => 9934,
				],
				'large'        => [
					'file'     => '2024/01/image-768x1024.jpg',
					'filesize' => 180274,
				],
				'thumbnail'    => [
					'file'     => '2024/01/image-150x150.jpg',
					'filesize' => 7212,
				],
				'medium_large' => [
					'file'     => '2024/01/image-768x1024.jpg',
					'filesize' => 180274,
				],
				'1536x1536'    => [
					'file'     => '2024/01/image-1152x1536.jpg',
					'filesize' => 327388,
				]
				,
				'2048x2048'    => [
					'file'     => '2024/01/image-1536x2048.jpg',
					'filesize' => 470242,
				],
			],
			'sources'  => [ // not counted
				'image/jpeg' => [ 'filesize' => 713072 ],
			],
		] );
		$this->attachment_ids[] = $attachment_id;

		$attachment_id = $this->factory->post->create( [ 'post_type' => 'attachment', 'post_title' => 'Image 2 without any usage' ] );
		add_post_meta( $attachment_id, '_wp_attachment_metadata', [
			'file'  => '2024/02/image.jpg',
			'sizes' => [
				'medium'       => [
					'file' => '2024/02/image-300x300.jpg',
				],
				'large'        => [
					'file' => '2024/02/image-768x1024.jpg',
				],
				'thumbnail'    => [
					'file' => '2024/02/image-150x150.jpg',
				],
				'medium_large' => [
					'file' => '2024/02/image-768x1024.jpg',
				],
			],
		] );
		$this->attachment_ids[] = $attachment_id;

		// Attachment 3 has an empty array as isc_image_posts postmeta
		$attachment_id = $this->factory->post->create( [ 'post_type' => 'attachment', 'post_title' => 'Image 3 with array in isc_image_posts' ] );
		add_post_meta( $attachment_id, 'isc_image_posts', [] ); // stored as 'a:0:{}' in the DB
		add_post_meta( $attachment_id, '_wp_attachment_metadata', [
			'file'  => '2024/03/image.jpg',
			'filesize' => 713072,
			'sizes' => [
				'medium'       => [
					'file' => '2024/03/image-300x300.jpg',
					'filesize' => 9934,
				],
				'large'        => [
					'file' => '2024/03/image-768x1024.jpg',
					'filesize' => 180274,
				],
				'thumbnail'    => [
					'file' => '2024/03/image-150x150.jpg',
				],
				'medium_large' => [
					'file' => '2024/03/image-768x1024.jpg',
				],
			],
		] );
		$this->attachment_ids[] = $attachment_id;

		// Attachment 4 has an isc_image_posts postmeta entry relating it to post 123
		$attachment_id = $this->factory->post->create( [ 'post_type' => 'attachment', 'post_title' => 'Image 4 attached to Post 123' ] );
		add_post_meta( $attachment_id, 'isc_image_posts', 'a:1:{i:0;i:123;}', true );
		$this->attachment_ids[] = $attachment_id;

		// Attachment 5 has a _thumbnail_id postmeta entry to identify it as post thumbnail for post 123
		$attachment_id = $this->factory->post->create( [ 'post_type' => 'attachment', 'post_title' => 'Image 5 is featured image of Post 123' ] );
		add_post_meta( '123', '_thumbnail_id', $attachment_id );
		$this->attachment_ids[] = $attachment_id;
	}

	/**
	 * Test, if get_unused_attachments() returns the correct attachments
	 */
	public function test_get_unused_attachments() {
		$this->setUpAttachments();

		// Get all attachments that are not used
		$unused_attachments = \ISC\Unused_Images::get_unused_attachments();

		// get the attachment titles from the result
		$unused_attachments_ids = array_map( function( $attachment ) {
			return $attachment->post_title;
		}, $unused_attachments );

		// Check if the correct attachments are returned
		$this->assertEquals( [
			                     'Image 1 without any usage',
			                     'Image 2 without any usage',
			                     'Image 3 with array in isc_image_posts',
		                     ], $unused_attachments_ids );
	}

	/**
	 * Test, if get_unused_attachments() returns the correct attachments when filtered for unchecked
	 */
	public function test_no_attachments() {
		// Call parent setup without creating any attachments
		parent::setUp();

		// Get all attachments that are not used
		$unused_attachments = \ISC\Unused_Images::get_unused_attachments();

		// Check if the correct attachments are returned
		$this->assertEquals([], $unused_attachments);

		// Calculate attachment stats
		$attachment_stats = \ISC\Unused_Images::calculate_attachment_stats();

		// Check if the correct stats are returned
		$this->assertEquals([], $attachment_stats);
	}

	/**
	 * Test calculate_attachment_stats()
	 */
	public function test_calculate_attachment_stats() {
		$this->setUpAttachments();

		// Calculate attachment stats
		$attachment_stats = \ISC\Unused_Images::calculate_attachment_stats();

		// Check if the correct stats are returned
		$this->assertEquals( [
			                     'attachment_count' => 3,
			                     'files'            => 17,
			                     'filesize'         => 2791676,
		                     ], $attachment_stats );
	}

	/**
	 * Test analyze_unused_image() metadata
	 * standard metadata with subsizes and "file" and "filesize" keys
	 */
	public function test_analyze_unused_image_metadata() {
		$this->setUpAttachments();

		$image_information = \ISC\Unused_Images::analyze_unused_image( get_post_meta( $this->attachment_ids[0], '_wp_attachment_metadata', true ) );

		$this->assertEquals( [ 'files' => 7, 'total_size' => 1888396 ], $image_information );
	}

	/**
	 * Test analyze_unused_image() metadata without "filesize" keys
	 */
	public function test_analyze_unused_image_metadata_without_filesize() {
		$this->setUpAttachments();

		$image_information = \ISC\Unused_Images::analyze_unused_image( get_post_meta( $this->attachment_ids[1], '_wp_attachment_metadata', true ) );

		$this->assertEquals( [ 'files' => 5, 'total_size' => 0 ], $image_information );
	}

	/**
	 * Test analyze_unused_image() metadata with "file" keys and some "filesize" keys
	 */
	public function test_analyze_unused_image_metadata_with_some_filesize() {
		$this->setUpAttachments();

		$image_information = \ISC\Unused_Images::analyze_unused_image( get_post_meta( $this->attachment_ids[2], '_wp_attachment_metadata', true ) );

		$this->assertEquals( [ 'files' => 5, 'total_size' => 903280 ], $image_information );
	}

}