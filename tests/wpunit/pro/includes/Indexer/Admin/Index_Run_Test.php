<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Indexer\Admin;

use ISC\Pro\Indexer\Index_Run;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Class Index_Run_Test
 *
 * @package ISC\Pro\Indexer
 */
class Index_Run_Test extends WPTestCase {

	/**
	 * @var \ISC\Pro\Indexer\Index_Run
	 */
	protected $indexer_run;

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->indexer_run = new Index_Run();

		// Set a smaller URL batch size for testing purposes
		// This requires accessing a private property via reflection
		$reflection = new \ReflectionClass( $this->indexer_run );
		$property   = $reflection->getProperty( 'url_batch_size' );
		$property->setAccessible( true );
		$property->setValue( $this->indexer_run, 2 ); // Set batch size to 2 for tests
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Unregister custom post types created in tests to prevent state leakage
		unregister_post_type( 'my_cpt' );
		unregister_post_type( 'private_cpt' );

		// Reset the indexer_run instance
		$this->indexer_run = null;
	}

	/**
	 * Test that get_total_content_count returns the correct count of published public posts.
	 */
	public function test_get_total_content_count_returns_correct_count(): void {
		// Create some posts of different types and statuses
		$this->factory()->post->create( [ 'post_status' => 'publish', 'post_type' => 'post' ] ); // +1
		$this->factory()->post->create( [ 'post_status' => 'publish', 'post_type' => 'page' ] ); // +1
		$this->factory()->post->create( [ 'post_status' => 'draft', 'post_type' => 'post' ] ); // ignored
		$this->factory()->post->create( [ 'post_status' => 'private', 'post_type' => 'post' ] ); // ignored

		// Assuming 'my_cpt' is a public custom post type
		register_post_type( 'my_cpt', [ 'public' => true, 'show_ui' => true ] );
		$this->factory()->post->create( [ 'post_status' => 'publish', 'post_type' => 'my_cpt' ] ); // +1
		$this->factory()->post->create( [ 'post_status' => 'draft', 'post_type' => 'my_cpt' ] ); // ignored

		// Assuming 'private_cpt' is a non-public custom post type
		register_post_type( 'private_cpt', [ 'public' => false, 'show_ui' => false ] );
		$this->factory()->post->create( [ 'post_status' => 'publish', 'post_type' => 'private_cpt' ] ); // ignored

		// Get the total count
		$total_count = $this->indexer_run->get_total_content_count();

		// Assert that the correct number of published public posts are counted
		$this->assertEquals( 3, $total_count );
	}

	/**
	 * Test that get_content_urls_batch returns the correct batch of URLs.
	 */
	public function test_get_content_urls_batch_returns_correct_batch(): void {
		// Create several published posts
		$post_ids = $this->factory()->post->create_many( 5, [ 'post_status' => 'publish' ] );

		// Get the URL batch size set in setUp
		$reflection = new \ReflectionClass( $this->indexer_run );
		$property   = $reflection->getProperty( 'url_batch_size' );
		$property->setAccessible( true );
		$batch_size = $property->getValue( $this->indexer_run ); // Should be 2

		// --- Test first batch (offset 0) ---
		$offset = 0;
		$urls_batch_1 = $this->indexer_run->get_content_urls_batch( $offset, $batch_size );

		$this->assertCount( $batch_size, $urls_batch_1, 'First batch count incorrect' );
		$this->assertEquals( [
			                     [ 'id' => $post_ids[0], 'url' => get_permalink( $post_ids[0] ) ],
			                     [ 'id' => $post_ids[1], 'url' => get_permalink( $post_ids[1] ) ],
		                     ], $urls_batch_1 );

		// --- Test second batch (offset 2) ---
		$offset = $batch_size;
		$urls_batch_2 = $this->indexer_run->get_content_urls_batch( $offset, $batch_size );

		$this->assertCount( $batch_size, $urls_batch_2, 'Second batch count incorrect' );
		$this->assertEquals( [
			                     [ 'id' => $post_ids[2], 'url' => get_permalink( $post_ids[2] ) ],
			                     [ 'id' => $post_ids[3], 'url' => get_permalink( $post_ids[3] ) ],
		                     ], $urls_batch_2 );

		// --- Test final batch (offset 4) ---
		$offset = $batch_size * 2;
		$urls_batch_3 = $this->indexer_run->get_content_urls_batch( $offset, $batch_size );

		$this->assertCount( 1, $urls_batch_3, 'Final batch count incorrect' ); // Only 1 post left
		$this->assertEquals( [
			                     [ 'id' => $post_ids[4], 'url' => get_permalink( $post_ids[4] ) ],
		                     ], $urls_batch_3 );

		// --- Test offset greater than total ---
		$offset = $batch_size * 3; // Offset 6
		$urls_batch_4 = $this->indexer_run->get_content_urls_batch( $offset, $batch_size );

		$this->assertCount( 0, $urls_batch_4, 'Offset greater than total should return empty batch' );
		$this->assertEquals( [], $urls_batch_4 );
	}

	/**
	 * Test that get_content_urls_batch respects post status and type.
	 */
	public function test_get_content_urls_batch_respects_post_status_and_type(): void {
		// Create posts with different statuses and types
		$published_post_id = $this->factory()->post->create( [ 'post_status' => 'publish', 'post_type' => 'post' ] );
		$draft_post_id     = $this->factory()->post->create( [ 'post_status' => 'draft', 'post_type' => 'post' ] );
		$published_page_id = $this->factory()->post->create( [ 'post_status' => 'publish', 'post_type' => 'page' ] );

		register_post_type( 'my_cpt', [ 'public' => true, 'show_ui' => true ] );
		$published_cpt_id = $this->factory()->post->create( [ 'post_status' => 'publish', 'post_type' => 'my_cpt' ] );

		register_post_type( 'private_cpt', [ 'public' => false, 'show_ui' => false ] );
		$published_private_cpt_id = $this->factory()->post->create( [ 'post_status' => 'publish', 'post_type' => 'private_cpt' ] );

		// Get a batch (batch size 2 from setUp)
		$urls_batch = $this->indexer_run->get_content_urls_batch( 0, 10 ); // Use a larger batch size to get all

		// Assert that only published public posts are included
		$expected_urls = [
			[ 'id' => $published_post_id, 'url' => get_permalink( $published_post_id ) ],
			[ 'id' => $published_page_id, 'url' => get_permalink( $published_page_id ) ],
			[ 'id' => $published_cpt_id, 'url' => get_permalink( $published_cpt_id ) ],
		];

		// Sort both arrays by ID for consistent comparison
		usort( $urls_batch, function( $a, $b ) {
			return $a['id'] <=> $b['id'];
		} );
		usort( $expected_urls, function( $a, $b ) {
			return $a['id'] <=> $b['id'];
		} );

		$this->assertEquals( $expected_urls, $urls_batch );
	}

	/**
	 * Test that index_single_item processes a single item and returns correct data structure.
	 *
	 * Note: In test environments, HTTP requests may fail (404). This test validates
	 * that the method returns the expected structure regardless of success or failure.
	 */
	public function test_index_single_item_returns_processed_item_data(): void {
		// Create a published post
		$post_id = $this->factory()->post->create( [
			                                           'post_title'   => 'Single Item Post',
			                                           'post_status'  => 'publish',
			                                           'post_type'    => 'post',
			                                           'post_content' => 'Content with an image.',
		                                           ] );
		$post_url = get_permalink( $post_id );

		$url_data = [
			'id'  => $post_id,
			'url' => $post_url,
		];

		// Call index_single_item
		$result = $this->indexer_run->index_single_item( $url_data );

		// Assert the structure of the returned array
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'title', $result );
		$this->assertArrayHasKey( 'url', $result );
		$this->assertArrayHasKey( 'post_type', $result );
		$this->assertArrayHasKey( 'images_count', $result );

		// Assert the basic post data
		$this->assertEquals( $post_id, $result['id'] );
		$this->assertEquals( 'Single Item Post', $result['title'] );
		$this->assertEquals( $post_url, $result['url'] );
		$this->assertEquals( 'post', $result['post_type'] );

		// Verify images_count is either an integer or an error string
		$this->assertTrue(
			is_int( $result['images_count'] ) || is_string( $result['images_count'] ),
			'images_count should be either an integer or an error string'
		);

		// If is_error flag is present and set to 1, images_count should be a string
		if ( isset( $result['is_error'] ) && 1 === $result['is_error'] ) {
			$this->assertIsString( $result['images_count'] );
		}
	}

	/**
	 * Test that index_single_item handles invalid input data.
	 */
	public function test_index_single_item_handles_invalid_data(): void {
		// Test with empty array
		$result_empty = $this->indexer_run->index_single_item( [] );
		$this->assertIsArray( $result_empty );
		$this->assertArrayHasKey( 'is_error', $result_empty );
		$this->assertEquals( 1, $result_empty['is_error'] );
		$this->assertArrayHasKey( 'images_count', $result_empty );
		$this->assertStringContainsString( 'Invalid URL data provided', $result_empty['images_count'] );
		$this->assertEquals( 0, $result_empty['id'] );

		// Test with missing 'id'
		$result_missing_id = $this->indexer_run->index_single_item( [ 'url' => 'http://example.com' ] );
		$this->assertIsArray( $result_missing_id );
		$this->assertArrayHasKey( 'is_error', $result_missing_id );
		$this->assertEquals( 1, $result_missing_id['is_error'] );
		$this->assertArrayHasKey( 'images_count', $result_missing_id );
		$this->assertStringContainsString( 'Invalid URL data provided', $result_missing_id['images_count'] );
		$this->assertEquals( 0, $result_missing_id['id'] );

		// Test with missing 'url'
		$result_missing_url = $this->indexer_run->index_single_item( [ 'id' => 123 ] );
		$this->assertIsArray( $result_missing_url );
		$this->assertArrayHasKey( 'is_error', $result_missing_url );
		$this->assertEquals( 1, $result_missing_url['is_error'] );
		$this->assertArrayHasKey( 'images_count', $result_missing_url );
		$this->assertStringContainsString( 'Invalid URL data provided', $result_missing_url['images_count'] );
		$this->assertEquals( 0, $result_missing_url['id'] );

		// Test with invalid post ID (<= 0)
		$result_negative_id = $this->indexer_run->index_single_item( [ 'id' => -5, 'url' => 'http://example.com' ] );
		$this->assertIsArray( $result_negative_id );
		$this->assertArrayHasKey( 'is_error', $result_negative_id );
		$this->assertEquals( 1, $result_negative_id['is_error'] );
		$this->assertArrayHasKey( 'images_count', $result_negative_id );
		$this->assertStringContainsString( 'Invalid post ID', $result_negative_id['images_count'] );
		$this->assertEquals( -5, $result_negative_id['id'] );
	}
}