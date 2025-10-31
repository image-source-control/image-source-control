<?php

namespace ISC\Tests\Functional\Pro;

use FunctionalTester;

class Indexer_Public_Cest {

	/**
	 * Table name for the ISC index
	 */
	const INDEX_TABLE = 'isc_test_isc_index';

	/**
	 * Seconds in a day
	 */
	const DAY_IN_SECONDS = 86400;

	/**
	 * Setup before each test
	 */
	public function _before( FunctionalTester $I ) {
		// Enable the unused_images module and disable index_any_url to trigger frontend indexing
		$existing_option = $I->grabOptionFromDatabase( 'isc_options' );
		if ( ! is_array( $existing_option ) ) {
			$existing_option = [];
		}

		// Enable unused_images module
		if ( ! isset( $existing_option['modules'] ) ) {
			$existing_option['modules'] = [];
		}
		if ( ! in_array( 'unused_images', $existing_option['modules'] ) ) {
			$existing_option['modules'][] = 'unused_images';
		}

		// Disable index_any_url to enable frontend indexing (default behavior when not set)
		if ( ! isset( $existing_option['unused_images'] ) ) {
			$existing_option['unused_images'] = [];
		}
		$existing_option['unused_images']['index_any_url'] = false;

		$I->haveOptionInDatabase( 'isc_options', $existing_option );
	}

	/**
	 * Test that featured image is indexed when viewing a post
	 */
	public function test_featured_image_indexed_on_post_view( FunctionalTester $I ) {
		// Create an attachment as a post with post_type 'attachment'
		$attachment_id = $I->havePostInDatabase( [
			                                         'post_title' => 'Test Image',
			                                         'post_type' => 'attachment',
			                                         'guid' => 'https://example.com/test-image.jpg',
		                                         ] );

		// Create a post with a featured image
		$post_id = $I->havePostInDatabase( [
			                                   'post_name' => 'test-post-with-featured-image',
			                                   'post_title' => 'Test Post with Featured Image',
			                                   'post_status' => 'publish',
		                                   ] );
		$I->havePostmetaInDatabase( $post_id, '_thumbnail_id', $attachment_id );

		// Visit the post
		$I->amOnPage( '/test-post-with-featured-image' );

		// Check that the featured image was indexed in the custom index table
		$I->seeInDatabase( self::INDEX_TABLE, [
			'post_id' => $post_id,
			'attachment_id' => $attachment_id,
			'position' => 'thumbnail',
		] );
	}

	/**
	 * Test that no thumbnail entry is created when post has no featured image
	 */
	public function test_no_thumbnail_index_when_no_featured_image( FunctionalTester $I ) {
		// Create a post without featured image
		$post_id = $I->havePostInDatabase( [
			                                   'post_name' => 'test-post-without-featured-image',
			                                   'post_title' => 'Test Post without Featured Image',
			                                   'post_status' => 'publish',
		                                   ] );

		// Visit the post
		$I->amOnPage( '/test-post-without-featured-image' );

		// Check that no thumbnail entry exists
		$I->dontSeeInDatabase( self::INDEX_TABLE, [
			'post_id' => $post_id,
			'position' => 'thumbnail',
		] );
	}

	/**
	 * Test that content images are indexed when viewing a post
	 */
	public function test_content_images_indexed_on_post_view( FunctionalTester $I ) {
		// Create attachments as posts with post_type 'attachment'
		$attachment_id_1 = $I->havePostInDatabase( [
			                                           'post_title' => 'Test Image 1',
			                                           'post_type' => 'attachment',
			                                           'guid' => 'https://example.com/test-image-1.jpg',
		                                           ] );
		$attachment_id_2 = $I->havePostInDatabase( [
			                                           'post_title' => 'Test Image 2',
			                                           'post_type' => 'attachment',
			                                           'guid' => 'https://example.com/test-image-2.jpg',
		                                           ] );

		// Create a post with images in content using the GUIDs
		$content = '<p>Test content with images</p><img src="https://example.com/test-image-1.jpg" /><p>More content</p><img src="https://example.com/test-image-2.jpg" />';

		$post_id = $I->havePostInDatabase( [
			                                   'post_name' => 'test-post-with-content-images',
			                                   'post_title' => 'Test Post with Content Images',
			                                   'post_content' => $content,
			                                   'post_status' => 'publish',
		                                   ] );

		// Visit the post
		$I->amOnPage( '/test-post-with-content-images' );

		// Check that content images were indexed
		$I->seeInDatabase( self::INDEX_TABLE, [
			'post_id' => $post_id,
			'attachment_id' => $attachment_id_1,
			'position' => 'content',
		] );
		$I->seeInDatabase( self::INDEX_TABLE, [
			'post_id' => $post_id,
			'attachment_id' => $attachment_id_2,
			'position' => 'content',
		] );
	}

	/**
	 * Test that index is not updated when not expired
	 */
	public function test_index_not_updated_when_not_expired( FunctionalTester $I ) {
		// Set last index to current time
		$current_time = time();

		// Create a post
		$post_id = $I->havePostInDatabase( [
			                                   'post_name' => 'test-post-not-expired',
			                                   'post_title' => 'Test Post',
			                                   'post_status' => 'publish',
		                                   ] );
		$I->havePostmetaInDatabase( $post_id, 'isc_last_index', $current_time );

		// Wait at least 1 second to ensure time() would return a different value if updated
		sleep( 1 );

		// Visit the post
		$I->amOnPage( '/test-post-not-expired' );

		// Check that the timestamp hasn't changed
		$I->seePostMetaInDatabase( [
			                           'post_id' => $post_id,
			                           'meta_key' => 'isc_last_index',
			                           'meta_value' => $current_time,
		                           ] );
	}

	/**
	 * Test that index is updated when expired
	 */
	public function test_index_updated_when_expired( FunctionalTester $I ) {
		// Set last index to 8 days ago (expired)
		$eight_days_ago = time() - ( 8 * self::DAY_IN_SECONDS );

		// Create a post
		$post_id = $I->havePostInDatabase( [
			                                   'post_name' => 'test-post-expired',
			                                   'post_title' => 'Test Post',
			                                   'post_content' => '<p>Test content</p>',
			                                   'post_status' => 'publish',
		                                   ] );
		$I->havePostmetaInDatabase( $post_id, 'isc_last_index', $eight_days_ago );

		// Visit the post
		$I->amOnPage( '/test-post-expired' );

		// Check that the timestamp was updated (should be greater than 8 days ago)
		$new_timestamp = $I->grabPostMetaFromDatabase( $post_id, 'isc_last_index' );
		$I->assertGreaterThan( $eight_days_ago, $new_timestamp, 'Index timestamp should be updated when expired' );
	}

	/**
	 * Test that content index entries are removed when images are removed from content
	 */
	public function test_content_index_removed_when_images_removed( FunctionalTester $I ) {
		// Create attachments
		$attachment_id_1 = $I->havePostInDatabase( [
			                                           'post_title' => 'Test Image 1',
			                                           'post_type' => 'attachment',
			                                           'guid' => 'https://example.com/test-image-remove-1.jpg',
		                                           ] );
		$attachment_id_2 = $I->havePostInDatabase( [
			                                           'post_title' => 'Test Image 2',
			                                           'post_type' => 'attachment',
			                                           'guid' => 'https://example.com/test-image-remove-2.jpg',
		                                           ] );

		// Create a post with images in content
		$content_with_images = '<p>Test content with images</p><img src="https://example.com/test-image-remove-1.jpg" /><img src="https://example.com/test-image-remove-2.jpg" />';

		$post_id = $I->havePostInDatabase( [
			                                   'post_name' => 'test-post-images-removed',
			                                   'post_title' => 'Test Post Images Removed',
			                                   'post_content' => $content_with_images,
			                                   'post_status' => 'publish',
		                                   ] );

		// First visit: index the images
		$I->amOnPage( '/test-post-images-removed' );

		// Verify images were indexed
		$I->seeInDatabase( self::INDEX_TABLE, [
			'post_id' => $post_id,
			'attachment_id' => $attachment_id_1,
			'position' => 'content',
		] );
		$I->seeInDatabase( self::INDEX_TABLE, [
			'post_id' => $post_id,
			'attachment_id' => $attachment_id_2,
			'position' => 'content',
		] );

		// Update the post content to remove images
		$content_without_images = '<p>This is just text content without any images.</p>';
		$I->updateInDatabase( 'isc_test_posts',
		                      [ 'post_content' => $content_without_images ],
		                      [ 'ID' => $post_id ]
		);

		// Set the index to expired so it will re-index on next visit
		$eight_days_ago = time() - ( 8 * self::DAY_IN_SECONDS );
		$I->updateInDatabase( 'isc_test_postmeta',
		                      [ 'meta_value' => $eight_days_ago ],
		                      [ 'post_id' => $post_id, 'meta_key' => 'isc_last_index' ]
		);

		// Second visit: should remove the index entries
		$I->amOnPage( '/test-post-images-removed' );

		// Verify content entries were removed
		$I->dontSeeInDatabase( self::INDEX_TABLE, [
			'post_id' => $post_id,
			'position' => 'content',
		] );
	}

	/**
	 * Test that indexing does not run on archive pages
	 */
	public function test_indexing_skipped_on_archive_pages( FunctionalTester $I ) {
		// Create a post
		$post_id = $I->havePostInDatabase( [
			                                   'post_name' => 'test-post-in-archive',
			                                   'post_title' => 'Test Post in Archive',
			                                   'post_status' => 'publish',
		                                   ] );

		// Visit the blog archive
		$I->amOnPage( '/' );

		// Check that index timestamp was not set
		$I->dontSeePostMetaInDatabase( [
			                               'post_id' => $post_id,
			                               'meta_key' => 'isc_last_index',
		                               ] );
	}
}