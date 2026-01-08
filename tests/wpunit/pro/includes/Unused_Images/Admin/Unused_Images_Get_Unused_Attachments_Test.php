<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Admin;

use ISC\Pro\Unused_Images\Content_Scan\Content_Scan_Table;
use ISC\Pro\Unused_Images\Admin\Unused_Images;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing \ISC\Pro\Unused_Images::get_unused_attachments()
 */
class Unused_Images_Get_Unused_Attachments_Test extends WPTestCase {

	private array $attachment_ids = [];

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Set up the attachments
		$this->setUpAttachments();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		global $post;

		// Explicitly delete the option set in tests
		delete_option( 'isc_options' );

		// Clean up meta for the current global post if it exists
		if ( isset( $post->ID ) ) {
			delete_post_meta( $post->ID, \ISC\Image_Sources\Post_Meta\Post_Images_Meta::META_KEY );
			delete_post_meta( $post->ID, \ISC\Indexer::BEFORE_UPDATE_META_KEY );
		}

		parent::tearDown();
	}

	/**
	 * Set the images_only setting.
	 *
	 * @param bool $enabled
	 */
	private function setImagesOnly( bool $enabled ): void {
		$options = \ISC\Plugin::get_options();
		$options['images_only'] = $enabled;
		update_option( 'isc_options', $options );
	}

	/**
	 * Set up a base set of attachments used across tests.
	 */
	private function setUpAttachments(): void {
		$this->attachment_ids[] = $this->createAttachmentWithMime( 'image/jpeg', 'Unused Image 1' );
		$this->attachment_ids[] = $this->createAttachmentWithMime( 'image/jpeg', 'Unused Image 2' );
		$this->attachment_ids[] = $this->createAttachmentWithMime( 'image/jpeg', 'Unused Image 3' );
	}

	/**
	 * Create an attachment with optional title and mime type.
	 *
	 * @param string $mime
	 * @param string|null $title
	 * @return int
	 */
	private function createAttachmentWithMime( string $mime, string $title = null ): int {
		$args = [
			'post_mime_type' => $mime,
			'post_type'      => 'attachment',
		];
		if ( $title ) {
			$args['post_title'] = $title;
		}

		return $this->factory->attachment->create( $args );
	}

	/**
	 * Test that get_unused_attachments returns the expected number of attachments in the default state
	 */
	public function test_get_unused_attachments() {
		$unused_attachments = Unused_Images::get_unused_attachments();
		$this->assertCount( 3, $unused_attachments, 'Expected 3 unused attachments' );

		foreach ( $this->attachment_ids as $attachment_id ) {
			$this->assertContains( $attachment_id, array_map('intval', wp_list_pluck( $unused_attachments, 'ID')), 'Attachment ID not found in unused attachments' );
		}
	}

	/**
	 * Test that get_unused_attachments returns an empty array when no attachments are present
	 */
	public function test_image_used_as_featured_image_is_excluded() {
		$this->assertCount( 3, Unused_Images::get_unused_attachments(), 'Expected 3 unused attachments' );

		// add the first image as a featured image to a post
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, '_thumbnail_id', $this->attachment_ids[0] );

		// check that the image is now excluded from the unused attachments
		$ids = array_map( 'intval', wp_list_pluck( Unused_Images::get_unused_attachments(), 'ID' ) );
		$this->assertCount( 2, $ids, 'Expected 2 unused attachments' );
		$this->assertNotContains( $this->attachment_ids[0], $ids, 'Image used as featured image should be excluded');
	}

	/**
	 * Test that get_unused_attachments returns the expected number of attachments when filtered for unchecked
	 */
	public function test_filter_unchecked() {
		$unused_attachments = Unused_Images::get_unused_attachments();
		$this->assertCount( 3, $unused_attachments, 'Expected 3 attachments before adding isc_possible_usages' );

		add_post_meta( $this->attachment_ids[0], 'isc_possible_usages', '', true );

		$unchecked_attachments = Unused_Images::get_unused_attachments( [ 'filter' => 'unchecked' ] );
		$this->assertCount( 2, $unchecked_attachments, 'Expected 2 unchecked attachments after marking one as checked' );
	}

	/**
	 * Test that get_unused_attachments returns the expected number of attachments when filtered for unused
	 * "unused" means that the image was checked and the isc_possible_usages post meta exists as an empty array
	 */
	public function test_filter_unused() {
		$unused_attachments = Unused_Images::get_unused_attachments( [ 'filter' => 'unused' ] );
		$this->assertCount( 0, $unused_attachments, 'Expected no attachments as "unused" before adding isc_possible_usages' );

		add_post_meta( $this->attachment_ids[0], 'isc_possible_usages', [], true );

		$results = Unused_Images::get_unused_attachments( [ 'filter' => 'unused' ] );
		$this->assertCount( 1, $results, 'Only 1 image should have a:0:{} usage and no other uses' );
	}

	/**
	 * Test the index table
	 *
	 * @return void
	 */
	public function test_index_table() {
		$unused_attachments = Unused_Images::get_unused_attachments();
		$this->assertCount( 3, $unused_attachments, 'Expected 3 attachments before adding something to the index table' );

		$index_table = new Content_Scan_Table();
		$index_table->insert_or_update( 123, $this->attachment_ids[0], 'content' );
		$index_table->insert_or_update( 123, $this->attachment_ids[1], 'content' );

		$results = Unused_Images::get_unused_attachments();
		$this->assertCount( 1, $results, 'Only 1 image is unused' );

		// Iterate through the attachment IDs to check if they are in the array of unused attachments
		$ids = array_map( 'intval', wp_list_pluck( Unused_Images::get_unused_attachments(), 'ID' ) );
		// Corrected assertions based on the expected outcome (only $this->attachment_ids[2] should be unused)
		$this->assertNotContains( $this->attachment_ids[0], $ids, 'Image 1 should be used');
		$this->assertNotContains( $this->attachment_ids[1], $ids, 'Image 2 should be used');
		$this->assertContains( $this->attachment_ids[2], $ids, 'Image 3 should be unused');
	}


	/**
	 * Test that get_unused_attachments returns the expected number of attachments, including PDFs, when images_only is not set
	 */
	public function test_images_only_disabled_includes_all_mime_types() {
		$this->assertCount( 3, Unused_Images::get_unused_attachments(), '3 images are unused at this point' );

		$doc_id   = $this->createAttachmentWithMime( 'application/pdf' );

		$results = Unused_Images::get_unused_attachments();
		$ids = array_map( 'intval', wp_list_pluck( $results, 'ID') );

		$this->assertCount( 4, $results, '4 media files are unused at this point' );
		$this->assertContains( $doc_id, $ids );
	}

	/**
	 * Test that get_unused_attachments returns the expected number of attachments when images_only is enabled
	 */
	public function test_images_only_enabled_excludes_non_images() {
		$this->setImagesOnly( true );

		$this->assertCount( 3, Unused_Images::get_unused_attachments(), '3 images are unused at this point' );

		$image_id = $this->createAttachmentWithMime( 'image/webp' );
		$doc_id = $this->createAttachmentWithMime( 'application/pdf' );

		$this->assertCount( 4, Unused_Images::get_unused_attachments(), '4 images are unused at this point. Other types are ignored' );

		$results = Unused_Images::get_unused_attachments();
		$ids = array_map( 'intval', wp_list_pluck($results, 'ID') );

		$this->assertContains( $image_id, $ids );
		$this->assertNotContains( $doc_id, $ids );
	}

	/**
	 * Test that get_unused_attachments can handle a specific attachment ID
	 */
	public function test_get_unused_attachments_with_attachment_id() {
		$attachment_id = $this->attachment_ids[0];

		$unused_attachments = Unused_Images::get_unused_attachments( [ 'attachment_id' => $attachment_id ] );
		$this->assertCount( 1, $unused_attachments, 'Expected 1 unused attachment' );

		$ids = array_map( 'intval', wp_list_pluck( $unused_attachments, 'ID' ) );
		$this->assertContains( $attachment_id, $ids, 'Attachment ID not found in unused attachments' );
	}

	/**
	 * Test that get_unused_attachments can handle a specific attachment ID if that attachment is used
	 * When attachment_id is set, it should override the other filters and also return used attachments
	 */
	public function test_get_unused_attachments_with_used_attachment_id() {
		$attachment_id = $this->attachment_ids[0];

		// add the image as a featured image to a post
		$post_id = $this->factory->post->create();
		add_post_meta( $post_id, '_thumbnail_id', $attachment_id );

		$unused_attachments = Unused_Images::get_unused_attachments( [ 'attachment_id' => $attachment_id ] );
		$this->assertCount( 1, $unused_attachments, 'Expected 1 attachment because the attachment_id parameter overrides other filters' );

		$ids = array_map( 'intval', wp_list_pluck( $unused_attachments, 'ID' ) );
		$this->assertContains( $attachment_id, $ids, 'Attachment ID should be found when specifically requested');
	}

	/**
	 * Test that the site icon is excluded from the unused attachments
	 */
	public function test_get_unused_attachments_excludes_site_icon() {
		update_option( 'site_icon', $this->attachment_ids[0] );

		$unused_attachments = Unused_Images::get_unused_attachments();

		$returned_ids = array_map( 'intval', wp_list_pluck( $unused_attachments, 'ID' ) );

		$this->assertNotContains( $this->attachment_ids[0], $returned_ids, 'site_icon should not be among unused images' );
		$this->assertContains( $this->attachment_ids[1], $returned_ids, 'Image 2 should be among unused images' );
		$this->assertCount( 2, $returned_ids, 'Only 2 images should be considered unused' );
	}
}