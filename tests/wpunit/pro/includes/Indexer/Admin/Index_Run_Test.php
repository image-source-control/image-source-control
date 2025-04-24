<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Indexer\Admin;

use ISC\Tests\WPUnit\WPTestCase;
use ISC\Pro\Indexer\Index_Run;

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

		// Optionally, set a smaller batch size for testing purposes
		// This requires accessing a private property, which can be done via reflection
		$reflection = new \ReflectionClass( $this->indexer_run );
		$property   = $reflection->getProperty( 'batch_size' );
		$property->setAccessible( true );
		$property->setValue( $this->indexer_run, 2 ); // Set batch size to 2 for tests

		// Explicitly delete all posts to ensure a clean slate for this test
		// This is a more robust way to guarantee no published public posts exist
		$all_post_ids = get_posts( [
			                           'post_type'      => 'any',
			                           'post_status'    => 'any',
			                           'posts_per_page' => -1,
			                           'fields'         => 'ids',
		                           ] );
		foreach ( $all_post_ids as $post_id ) {
			wp_delete_post( $post_id, true ); // Use true to force delete
		}
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
	 * Test that get_all_content_urls returns published public posts.
	 */
	public function test_get_all_content_urls_returns_published_public_posts(): void {
		// Create some posts of different types and statuses
		$published_post_id = $this->factory()->post->create( [
			                                                     'post_title'   => 'Published Post Title',
			                                                     'post_status'  => 'publish',
			                                                     'post_type'    => 'post',
			                                                     'post_content' => 'Some content.',
		                                                     ] );
		$published_page_id = $this->factory()->post->create( [
			                                                     'post_title'   => 'Published Page Title',
			                                                     'post_status'  => 'publish',
			                                                     'post_type'    => 'page',
			                                                     'post_content' => 'Some page content.',
		                                                     ] );
		$draft_post_id     = $this->factory()->post->create( [
			                                                     'post_title'  => 'Draft Post Title',
			                                                     'post_status' => 'draft',
			                                                     'post_type'   => 'post',
		                                                     ] );
		$private_post_id   = $this->factory()->post->create( [
			                                                     'post_title'  => 'Private Post Title',
			                                                     'post_status' => 'private',
			                                                     'post_type'   => 'post',
		                                                     ] );
		// Assuming 'my_cpt' is a public custom post type
		register_post_type( 'my_cpt', [ 'public' => true, 'show_ui' => true ] );
		$published_cpt_id = $this->factory()->post->create( [
			                                                    'post_title'  => 'Published CPT Title',
			                                                    'post_status' => 'publish',
			                                                    'post_type'   => 'my_cpt',
		                                                    ] );
		// Assuming 'private_cpt' is a non-public custom post type
		register_post_type( 'private_cpt', [ 'public' => false, 'show_ui' => false ] );
		$published_private_cpt_id = $this->factory()->post->create( [
			                                                            'post_title'  => 'Published Private CPT Title',
			                                                            'post_status' => 'publish',
			                                                            'post_type'   => 'private_cpt',
		                                                            ] );


		// Get the content URLs
		$urls = $this->indexer_run->get_all_content_urls();

		// Assert that the correct number of URLs are returned (published public posts only)
		// We created 3 published public posts (1 post, 1 page, 1 my_cpt)
		$this->assertCount( 3, $urls );

		// Assert that the returned array contains the expected URLs and IDs
		$expected_urls = [
			[ 'id' => $published_post_id, 'url' => get_permalink( $published_post_id ) ],
			[ 'id' => $published_page_id, 'url' => get_permalink( $published_page_id ) ],
			[ 'id' => $published_cpt_id, 'url' => get_permalink( $published_cpt_id ) ],
		];

		// Sort both arrays by ID to ensure consistent comparison order
		usort( $urls, function( $a, $b ) {
			return $a['id'] <=> $b['id'];
		} );
		usort( $expected_urls, function( $a, $b ) {
			return $a['id'] <=> $b['id'];
		} );

		$this->assertEquals( $expected_urls, $urls );

		// Assert that draft and private posts are not included
		$this->assertNotContains( [ 'id' => $draft_post_id, 'url' => get_permalink( $draft_post_id ) ], $urls );
		$this->assertNotContains( [ 'id' => $private_post_id, 'url' => get_permalink( $private_post_id ) ], $urls );
		$this->assertNotContains( [ 'id' => $published_private_cpt_id, 'url' => get_permalink( $published_private_cpt_id ) ], $urls );
	}

	/**
	 * Test that the isc_indexer_all_content_urls filter is applied.
	 */
	public function test_get_all_content_urls_filter_is_applied(): void {
		// Create some posts
		$post_id_1 = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$post_id_2 = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		// Define a filter function that adds a dummy URL
		$filter_func = function( $urls ) use ( $post_id_1 ) {
			// Remove post_id_2 and add a dummy URL
			$filtered_urls = array_filter( $urls, function( $url_data ) use ( $post_id_1 ) {
				return $url_data['id'] === $post_id_1;
			} );
			$filtered_urls[] = [ 'id' => 999, 'url' => 'http://example.com/external-url' ];
			return array_values( $filtered_urls ); // Re-index array
		};

		// Add the filter
		add_filter( 'isc_indexer_all_content_urls', $filter_func );

		// Get the content URLs
		$urls = $this->indexer_run->get_all_content_urls();

		// Remove the filter
		remove_filter( 'isc_indexer_all_content_urls', $filter_func );

		// Assert that the filtered URLs are returned
		$expected_urls = [
			[ 'id' => $post_id_1, 'url' => get_permalink( $post_id_1 ) ],
			[ 'id' => 999, 'url' => 'http://example.com/external-url' ],
		];

		// Sort both arrays by ID to ensure consistent comparison order
		usort( $urls, function( $a, $b ) {
			return $a['id'] <=> $b['id'];
		} );
		usort( $expected_urls, function( $a, $b ) {
			return $a['id'] <=> $b['id'];
		} );

		$this->assertEquals( $expected_urls, $urls );
	}

	/**
	 * Test that index_batch processes URLs and returns correct progress information.
	 *
	 * Note: This test focuses on the iteration and progress calculation logic.
	 * Testing the side effects of fetch_content (i.e., that indexing actually happens)
	 * is better suited for Functional Tests.
	 * We will need to mock or control the behavior of fetch_content and Index_Table
	 * for a true unit test of index_batch's logic flow.
	 */
	public function test_index_batch_processes_urls_and_returns_correct_progress(): void {
		// Create several posts to simulate content
		$post_ids = $this->factory()->post->create_many( 5, [ 'post_status' => 'publish' ] );
		$total_posts = count( $post_ids );

		// Get the full list of URLs that get_all_content_urls would return
		// Call the public method directly on the instance
		$all_urls = $this->indexer_run->get_all_content_urls();
		$this->assertCount( $total_posts, $all_urls, 'Setup failed: get_all_content_urls did not return expected posts' );


		// Ensure the batch size is set to 2 for this test (set in setUp)
		$reflection = new \ReflectionClass( $this->indexer_run );
		$property   = $reflection->getProperty( 'batch_size' );
		$property->setAccessible( true );
		$batch_size = $property->getValue( $this->indexer_run ); // Get the value set in setUp

		// --- Test first batch (offset 0) ---
		$offset = 0;
		$result = $this->indexer_run->index_batch( $offset );

		$this->assertEquals( $batch_size, $result['processed'], 'First batch processed count incorrect' );
		$this->assertEquals( $total_posts, $result['total'], 'First batch total count incorrect' );
		// Calculate expected percentage carefully, handling division by zero if total_posts is 0
		$expected_percentage_first = ( $total_posts > 0 ) ? min( 100, round( ( $batch_size / $total_posts ) * 100 ) ) : 100;
		$this->assertEquals( $expected_percentage_first, $result['percentage'], 'First batch percentage incorrect' );
		$this->assertFalse( $result['complete'], 'First batch should not be complete' );
		$this->assertCount( $batch_size, $result['processed_items'], 'First batch processed items count incorrect' );

		// Check the processed items structure and data for the first batch
		$expected_first_batch_items = array_slice($all_urls, $offset, $batch_size);
		foreach ($expected_first_batch_items as &$item) {
			// Simulate the data added by index_batch
			$item['title'] = get_the_title($item['id']);
			$item['post_type'] = get_post_type($item['id']);
			// We are not testing Index_Table interaction here, assume 0 images for simplicity
			$item['images_count'] = 0; // This would need mocking Index_Table for a real test
		}
		// Sort both arrays by ID to ensure consistent comparison order
		usort( $result['processed_items'], function( $a, $b ) {
			return $a['id'] <=> $b['id'];
		} );
		usort( $expected_first_batch_items, function( $a, $b ) {
			return $a['id'] <=> $b['id'];
		} );
		$this->assertEquals( $expected_first_batch_items, $result['processed_items'], 'First batch processed items data incorrect' );


		// --- Test second batch (offset 2) ---
		$offset = $batch_size;
		$result = $this->indexer_run->index_batch( $offset );

		$expected_processed_second = min( $total_posts, $batch_size * 2 );
		$this->assertEquals( $expected_processed_second, $result['processed'], 'Second batch processed count incorrect' );
		$this->assertEquals( $total_posts, $result['total'], 'Second batch total count incorrect' );
		$expected_percentage_second = ( $total_posts > 0 ) ? min( 100, round( ( $expected_processed_second / $total_posts ) * 100 ) ) : 100;
		$this->assertEquals( $expected_percentage_second, $result['percentage'], 'Second batch percentage incorrect' );
		$this->assertFalse( $result['complete'], 'Second batch should not be complete' );
		$this->assertCount( $batch_size, $result['processed_items'], 'Second batch processed items count incorrect' );

		// Check the processed items structure and data for the second batch
		$expected_second_batch_items = array_slice($all_urls, $offset, $batch_size);
		foreach ($expected_second_batch_items as &$item) {
			$item['title'] = get_the_title($item['id']);
			$item['post_type'] = get_post_type($item['id']);
			$item['images_count'] = 0; // Assuming 0 images
		}
		// Sort both arrays by ID to ensure consistent comparison order
		usort( $result['processed_items'], function( $a, $b ) {
			return $a['id'] <=> $b['id'];
		} );
		usort( $expected_second_batch_items, function( $a, $b ) {
			return $a['id'] <=> $b['id'];
		} );
		$this->assertEquals( $expected_second_batch_items, $result['processed_items'], 'Second batch processed items data incorrect' );


		// --- Test final batch (offset 4) ---
		$offset = $batch_size * 2;
		$result = $this->indexer_run->index_batch( $offset );

		$expected_processed_final = min( $total_posts, $batch_size * 2 + count(array_slice($all_urls, $offset, $batch_size)) );
		$this->assertEquals( $expected_processed_final, $result['processed'], 'Final batch processed count incorrect' );
		$this->assertEquals( $total_posts, $result['total'], 'Final batch total count incorrect' );
		$expected_percentage_final = ( $total_posts > 0 ) ? min( 100, round( ( $expected_processed_final / $total_posts ) * 100 ) ) : 100;
		$this->assertEquals( $expected_percentage_final, $result['percentage'], 'Final batch percentage incorrect' );
		$this->assertTrue( $result['complete'], 'Final batch should be complete' );
		$this->assertCount( count(array_slice($all_urls, $offset, $batch_size)), $result['processed_items'], 'Final batch processed items count incorrect' ); // 1 item

		// Check the processed items structure and data for the final batch
		$expected_final_batch_items = array_slice($all_urls, $offset, $batch_size);
		foreach ($expected_final_batch_items as &$item) {
			$item['title'] = get_the_title($item['id']);
			$item['post_type'] = get_post_type($item['id']);
			$item['images_count'] = 0; // Assuming 0 images
		}
		// Sort both arrays by ID to ensure consistent comparison order
		usort( $result['processed_items'], function( $a, $b ) {
			return $a['id'] <=> $b['id'];
		} );
		usort( $expected_final_batch_items, function( $a, $b ) {
			return $a['id'] <=> $b['id'];
		} );
		$this->assertEquals( $expected_final_batch_items, $result['processed_items'], 'Final batch processed items data incorrect' );
	}

	/**
	 * Test that index_batch handles an empty list of URLs.
	 */
	public function test_index_batch_handles_empty_urls(): void {
		// Verify that get_all_content_urls returns an empty array in this state
		$urls = $this->indexer_run->get_all_content_urls();
		$this->assertCount( 0, $urls, 'get_all_content_urls should return empty when no published public posts exist' );

		// Call index_batch with offset 0
		$result = $this->indexer_run->index_batch( 0 );
		$this->assertEquals( [], $result, 'Index batch should return empty result for empty URLs' );
	}

	/**
	 * Test that index_batch handles offset greater than total.
	 */
	public function test_index_batch_handles_offset_greater_than_total(): void {
		// Create some posts
		$this->factory()->post->create_many( 3, [ 'post_status' => 'publish' ] );
		$total_posts = 3; // Assuming batch size is 2 from setUp

		// Call index_batch with an offset greater than the total
		$offset = 5;
		$result = $this->indexer_run->index_batch( $offset );

		$this->assertEquals( $total_posts, $result['processed'], 'Offset greater than total processed count incorrect' );
		$this->assertEquals( $total_posts, $result['total'], 'Offset greater than total total count incorrect' );
		$this->assertEquals( 100, $result['percentage'], 'Offset greater than total percentage incorrect' );
		$this->assertTrue( $result['complete'], 'Offset greater than total should be complete' );
		$this->assertCount( 0, $result['processed_items'], 'Offset greater than total processed items count incorrect' );
	}
}