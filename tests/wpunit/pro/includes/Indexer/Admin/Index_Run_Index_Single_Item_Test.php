<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Indexer\Admin;

use ISC\Pro\Indexer\Index_Run;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test class for Index_Run::index_single_item() method.
 *
 * This class comprehensively tests the index_single_item() method which is responsible
 * for processing a single URL and extracting image data.
 *
 * @package ISC\Pro\Indexer
 */
class Index_Run_Index_Single_Item_Test extends WPTestCase {

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
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		$this->indexer_run = null;
	}

	/**
	 * Test that index_single_item processes a single item and returns correct data structure.
	 *
	 * Note: In test environments, HTTP requests may fail (404). This test validates
	 * that the method returns the expected structure regardless of success or failure.
	 */
	public function test_returns_expected_structure_with_valid_post(): void {
		// Create a published post
		$post_id  = $this->factory()->post->create( [
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
	 * Test with empty array input.
	 */
	public function test_handles_empty_array_input(): void {
		$result = $this->indexer_run->index_single_item( [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_error', $result );
		$this->assertEquals( 1, $result['is_error'] );
		$this->assertArrayHasKey( 'images_count', $result );
		$this->assertStringContainsString( 'Invalid URL data provided', $result['images_count'] );
		$this->assertEquals( 0, $result['id'] );
	}

	/**
	 * Test with missing 'id' field.
	 */
	public function test_handles_missing_id_field(): void {
		$result = $this->indexer_run->index_single_item( [ 'url' => 'https://example.com' ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_error', $result );
		$this->assertEquals( 1, $result['is_error'] );
		$this->assertArrayHasKey( 'images_count', $result );
		$this->assertStringContainsString( 'Invalid URL data provided', $result['images_count'] );
		$this->assertEquals( 0, $result['id'] );
	}

	/**
	 * Test with missing 'url' field.
	 */
	public function test_handles_missing_url_field(): void {
		$result = $this->indexer_run->index_single_item( [ 'id' => 123 ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_error', $result );
		$this->assertEquals( 1, $result['is_error'] );
		$this->assertArrayHasKey( 'images_count', $result );
		$this->assertStringContainsString( 'Invalid URL data provided', $result['images_count'] );
		$this->assertEquals( 0, $result['id'] );
	}

	/**
	 * Test with invalid post ID (negative).
	 */
	public function test_handles_negative_post_id(): void {
		$result = $this->indexer_run->index_single_item( [ 'id' => - 5, 'url' => 'https://example.com' ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_error', $result );
		$this->assertEquals( 1, $result['is_error'] );
		$this->assertArrayHasKey( 'images_count', $result );
		$this->assertStringContainsString( 'Invalid post ID', $result['images_count'] );
		$this->assertEquals( - 5, $result['id'] );
	}

	/**
	 * Test with post ID of zero.
	 */
	public function test_handles_zero_post_id(): void {
		$result = $this->indexer_run->index_single_item( [ 'id' => 0, 'url' => 'https://example.com' ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_error', $result );
		$this->assertEquals( 1, $result['is_error'] );
		$this->assertStringContainsString( 'Invalid URL data provided', $result['images_count'] );
	}

	/**
	 * Test with the blog page (page_for_posts).
	 */
	public function test_handles_blog_page_correctly(): void {
		// Create a page and set it as the blog page
		$page_id = $this->factory()->post->create( [
			                                           'post_type'   => 'page',
			                                           'post_status' => 'publish',
			                                           'post_title'  => 'Blog Page',
		                                           ] );

		update_option( 'page_for_posts', $page_id );

		$result = $this->indexer_run->index_single_item( [
			                                                 'id'  => $page_id,
			                                                 'url' => get_permalink( $page_id ),
		                                                 ] );

		// Should return an error indicating "No content"
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_error', $result );
		$this->assertEquals( 1, $result['is_error'] );
		$this->assertArrayHasKey( 'images_count', $result );
		$this->assertStringContainsString( 'No content', $result['images_count'] );

		// Clean up
		delete_option( 'page_for_posts' );
	}

	/**
	 * Test with different post types.
	 */
	public function test_works_with_different_post_types(): void {
		// Test with page
		$page_id = $this->factory()->post->create( [
			                                           'post_type'   => 'page',
			                                           'post_status' => 'publish',
			                                           'post_title'  => 'Test Page',
		                                           ] );

		$result = $this->indexer_run->index_single_item( [
			                                                 'id'  => $page_id,
			                                                 'url' => get_permalink( $page_id ),
		                                                 ] );

		$this->assertIsArray( $result );
		$this->assertEquals( $page_id, $result['id'] );
		$this->assertEquals( 'page', $result['post_type'] );
	}

	/**
	 * Test that post is marked as indexed after processing.
	 */
	public function test_marks_post_as_indexed(): void {
		$post_id = $this->factory()->post->create( [
			                                           'post_status' => 'publish',
		                                           ] );

		// Verify post is not marked as indexed initially
		$initial_meta = get_post_meta( $post_id, 'isc_last_index', true );
		$this->assertEmpty( $initial_meta );

		// Record time before indexing
		$time_before = time();

		// Index the post
		$this->indexer_run->index_single_item( [
			                                       'id'  => $post_id,
			                                       'url' => get_permalink( $post_id ),
		                                       ] );

		// Record time after indexing
		$time_after = time();

		// Verify post is marked as indexed with a timestamp
		$indexed_timestamp = get_post_meta( $post_id, 'isc_last_index', true );
		$this->assertNotEmpty( $indexed_timestamp, 'Post should be marked as indexed after processing' );
		$this->assertIsNumeric( $indexed_timestamp, 'isc_last_index should be a numeric timestamp' );

		// Verify timestamp is within reasonable range (between before and after + small buffer)
		$this->assertGreaterThanOrEqual( $time_before, $indexed_timestamp, 'Timestamp should not be before indexing started' );
		$this->assertLessThanOrEqual( $time_after + 1, $indexed_timestamp, 'Timestamp should not be significantly after indexing completed' );
	}

	/**
	 * Test with start_timestamp parameter.
	 */
	public function test_accepts_start_timestamp_parameter(): void {
		$post_id = $this->factory()->post->create( [
			                                           'post_status' => 'publish',
		                                           ] );

		$timestamp = time();

		// Should not throw an error
		$result = $this->indexer_run->index_single_item(
			[
				'id'  => $post_id,
				'url' => get_permalink( $post_id ),
			],
			false,
			$timestamp
		);

		$this->assertIsArray( $result );
		$this->assertEquals( $post_id, $result['id'] );
	}

	/**
	 * Test with execute_as_admin parameter set to true.
	 */
	public function test_accepts_execute_as_admin_parameter(): void {
		$post_id = $this->factory()->post->create( [
			                                           'post_status' => 'publish',
		                                           ] );

		// Should not throw an error
		$result = $this->indexer_run->index_single_item(
			[
				'id'  => $post_id,
				'url' => get_permalink( $post_id ),
			],
			true
		);

		$this->assertIsArray( $result );
		$this->assertEquals( $post_id, $result['id'] );
	}

	/**
	 * Test that isc_last_index is not set when the URL is missing.
	 */
	public function test_does_not_set_isc_last_index_on_error(): void {
		$post_id = $this->factory()->post->create([
			                                          'post_status' => 'publish',
		                                          ]);

		$this->indexer_run->index_single_item([
			                                      'id'  => $post_id,
			                                      'url' => '',
		                                      ]);

		$meta = get_post_meta($post_id, 'isc_last_index', true);
		$this->assertEmpty($meta, 'isc_last_index should not be set on missing URL');
	}
}