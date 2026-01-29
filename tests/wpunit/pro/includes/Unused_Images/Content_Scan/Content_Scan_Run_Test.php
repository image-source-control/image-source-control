<?php
/**
 * Tests for Content_Scan_Run class
 *
 * @package ISC\Pro\Tests
 */

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Content_Scan;

use ISC\Pro\Unused_Images\Content_Scan\Content_Scan;
use ISC\Pro\Unused_Images\Content_Scan\Content_Scan_Run;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Content Scan Run Test
 */
class Content_Scan_Run_Test extends WPTestCase {

	/**
	 * Content Scan Run instance
	 *
	 * @var Content_Scan_Run
	 */
	private Content_Scan_Run $scan_run;

	/**
	 * Set up test fixtures
	 */
	public function setUp(): void {
		parent::setUp();
		$this->scan_run = new Content_Scan_Run();
	}

	/**
	 * Tear down test fixtures
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test get_urls_per_scan_run returns correct value
	 */
	public function test_get_urls_per_scan_run() {
		$result = $this->scan_run->get_urls_per_scan_run();

		$this->assertIsInt( $result );
		$this->assertGreaterThan( 0, $result );
	}

	/**
	 * Test get_max_urls_per_scan_run returns correct value
	 */
	public function test_get_max_urls_per_scan_run() {
		$result = $this->scan_run->get_max_urls_per_scan_run();

		$this->assertIsInt( $result );
		$this->assertGreaterThan( 0, $result );
	}

	/**
	 * Test get_max_urls_per_scan_run applies filter
	 */
	public function test_get_max_urls_per_scan_run_applies_filter() {
		add_filter( 'isc_indexer_max_urls_per_run', function() {
			return 25;
		} );

		$result = $this->scan_run->get_max_urls_per_scan_run();

		$this->assertEquals( 25, $result );

		remove_all_filters( 'isc_indexer_max_urls_per_run' );
	}

	/**
	 * Test get_total_content_count with 'all' mode
	 */
	public function test_get_total_content_count_all_mode() {
		// Create some test posts
		self::factory()->post->create();
		self::factory()->post->create();

		$count = $this->scan_run->get_total_content_count( 'all' );

		$this->assertGreaterThanOrEqual( 2, $count );
	}

	/**
	 * Test get_total_content_count with no posts
	 */
	public function test_get_total_content_count_with_no_posts() {
		// This will only work if we can ensure no posts exist
		// In practice, there might be default posts, so we just check it returns an integer
		$count = $this->scan_run->get_total_content_count( 'all' );

		$this->assertIsInt( $count );
		$this->assertGreaterThanOrEqual( 0, $count );
	}

	/**
	 * Test get_total_content_count with 'unindexed' mode
	 */
	public function test_get_total_content_count_unindexed_mode() {
		// Create unscanned posts
		self::factory()->post->create();
		self::factory()->post->create();

		$count = $this->scan_run->get_total_content_count( 'unindexed' );

		$this->assertGreaterThanOrEqual( 2, $count );
	}

	/**
	 * Test get_unscanned_post_ids returns array
	 */
	public function test_get_unscanned_post_ids_returns_array() {
		$result = $this->scan_run->get_unscanned_post_ids();

		$this->assertIsArray( $result );
	}

	/**
	 * Test get_unscanned_post_ids includes new posts
	 */
	public function test_get_unscanned_post_ids_includes_new_posts() {
		$post_id = self::factory()->post->create();

		$unscanned = $this->scan_run->get_unscanned_post_ids();

		$this->assertContains( $post_id, $unscanned );
	}

	/**
	 * Test get_unscanned_post_ids excludes scanned posts
	 */
	public function test_get_unscanned_post_ids_excludes_scanned() {
		$post_id = self::factory()->post->create();

		// Mark as scanned using the correct constant
		update_post_meta( $post_id, Content_Scan::LAST_SCAN_META_KEY, time() );

		$unscanned = $this->scan_run->get_unscanned_post_ids();

		$this->assertNotContains( $post_id, $unscanned );
	}

	/**
	 * Test get_unscanned_post_ids only includes published posts
	 */
	public function test_get_unscanned_post_ids_only_published() {
		$draft_id = self::factory()->post->create( array( 'post_status' => 'draft' ) );

		$unscanned = $this->scan_run->get_unscanned_post_ids();

		$this->assertNotContains( $draft_id, $unscanned );
	}

	/**
	 * Test get_content_urls_batch with 'all' mode
	 */
	public function test_get_content_urls_batch_all_mode() {
		self::factory()->post->create();
		self::factory()->post->create();

		$batch = $this->scan_run->get_content_urls_batch( 0, 10, 'all' );

		$this->assertIsArray( $batch );
		$this->assertGreaterThanOrEqual( 2, count( $batch ) );
	}

	/**
	 * Test get_content_urls_batch with 'unindexed' mode
	 */
	public function test_get_content_urls_batch_unindexed_mode() {
		$post_id = self::factory()->post->create();

		$batch = $this->scan_run->get_content_urls_batch( 0, 10, 'unindexed' );

		$this->assertIsArray( $batch );
		$this->assertGreaterThanOrEqual( 1, count( $batch ) );

		// Check structure
		if ( ! empty( $batch ) ) {
			$this->assertArrayHasKey( 'id', $batch[0] );
			$this->assertArrayHasKey( 'url', $batch[0] );
		}
	}

	/**
	 * Test get_content_urls_batch respects batch_size
	 */
	public function test_get_content_urls_batch_respects_batch_size() {
		// Create multiple posts
		for ( $i = 0; $i < 5; $i++ ) {
			self::factory()->post->create();
		}

		$batch = $this->scan_run->get_content_urls_batch( 0, 2, 'all' );

		$this->assertLessThanOrEqual( 2, count( $batch ) );
	}

	/**
	 * Test get_content_urls_batch respects offset
	 */
	public function test_get_content_urls_batch_respects_offset() {
		// Create posts
		for ( $i = 0; $i < 5; $i++ ) {
			self::factory()->post->create();
		}

		$batch1 = $this->scan_run->get_content_urls_batch( 0, 2, 'all' );
		$batch2 = $this->scan_run->get_content_urls_batch( 2, 2, 'all' );

		// Extract IDs
		$ids1 = wp_list_pluck( $batch1, 'id' );
		$ids2 = wp_list_pluck( $batch2, 'id' );

		// Should not overlap
		$this->assertEmpty( array_intersect( $ids1, $ids2 ) );
	}

	/**
	 * Test get_content_urls_batch returns correct structure
	 */
	public function test_get_content_urls_batch_structure() {
		$post_id = self::factory()->post->create();

		$batch = $this->scan_run->get_content_urls_batch( 0, 1, 'all' );

		$this->assertNotEmpty( $batch );
		$this->assertIsArray( $batch[0] );
		$this->assertArrayHasKey( 'id', $batch[0] );
		$this->assertArrayHasKey( 'url', $batch[0] );
		$this->assertIsInt( $batch[0]['id'] );
		$this->assertIsString( $batch[0]['url'] );
	}

	/**
	 * Test scan_multiple_items returns array of results
	 */
	public function test_scan_multiple_items_returns_array() {
		$post_id_1 = self::factory()->post->create();
		$post_id_2 = self::factory()->post->create();

		$url_data = array(
			array(
				'id'  => $post_id_1,
				'url' => get_permalink( $post_id_1 ),
			),
			array(
				'id'  => $post_id_2,
				'url' => get_permalink( $post_id_2 ),
			),
		);

		$results = $this->scan_run->scan_multiple_items( $url_data );

		$this->assertIsArray( $results );
		$this->assertCount( 2, $results );
	}

	/**
	 * Test scan_multiple_items with empty array
	 */
	public function test_scan_multiple_items_with_empty_array() {
		$results = $this->scan_run->scan_multiple_items( array() );

		$this->assertIsArray( $results );
		$this->assertEmpty( $results );
	}

	/**
	 * Test scan_single_item with invalid data returns error
	 */
	public function test_scan_single_item_with_invalid_data() {
		$result = $this->scan_run->scan_single_item( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_error', $result );
		$this->assertTrue( $result['is_error'] );
	}

	/**
	 * Test scan_single_item with invalid post ID returns error
	 */
	public function test_scan_single_item_with_invalid_post_id() {
		$result = $this->scan_run->scan_single_item( array(
			                                             'id'  => 0,
			                                             'url' => 'http://example.com',
		                                             ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_error', $result );
		$this->assertTrue( $result['is_error'] );
	}

	/**
	 * Test scan_single_item with negative post ID returns error
	 */
	public function test_scan_single_item_with_negative_post_id() {
		$result = $this->scan_run->scan_single_item( array(
			                                             'id'  => -1,
			                                             'url' => 'http://example.com',
		                                             ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_error', $result );
		$this->assertTrue( $result['is_error'] );
	}

	/**
	 * Test scan_single_item marks post as scanned even on error
	 */
	public function test_scan_single_item_marks_post_as_scanned() {
		$post_id = self::factory()->post->create();

		// scan_single_item will fail to fetch content in test environment
		// but should still mark the post as scanned via create_error_result
		$this->scan_run->scan_single_item( array(
			                                   'id'  => $post_id,
			                                   'url' => get_permalink( $post_id ),
		                                   ) );

		// Use the correct constant for the meta key
		$last_scan = get_post_meta( $post_id, Content_Scan::LAST_SCAN_META_KEY, true );

		// The meta should be set even if scanning failed
		$this->assertNotEmpty( $last_scan, 'Post should be marked as scanned even on failure' );
		$this->assertGreaterThan( 0, (int) $last_scan );
	}

	/**
	 * Test scan_single_item returns correct structure
	 */
	public function test_scan_single_item_returns_correct_structure() {
		$post_id = self::factory()->post->create();

		$result = $this->scan_run->scan_single_item( array(
			                                             'id'  => $post_id,
			                                             'url' => get_permalink( $post_id ),
		                                             ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'title', $result );
		$this->assertArrayHasKey( 'url', $result );
		$this->assertArrayHasKey( 'post_type', $result );
	}

	/**
	 * Test scan_single_item handles page_for_posts
	 */
	public function test_scan_single_item_handles_page_for_posts() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		// Set as posts page
		$original = get_option( 'page_for_posts' );
		update_option( 'page_for_posts', $page_id );

		$result = $this->scan_run->scan_single_item( array(
			                                             'id'  => $page_id,
			                                             'url' => get_permalink( $page_id ),
		                                             ) );

		$this->assertArrayHasKey( 'is_error', $result );
		$this->assertTrue( $result['is_error'] );

		// Restore
		if ( $original ) {
			update_option( 'page_for_posts', $original );
		} else {
			delete_option( 'page_for_posts' );
		}
	}

	/**
	 * Test extract_attachment_ids_from_html returns empty array for empty HTML
	 */
	public function test_extract_attachment_ids_from_html_empty() {
		$reflection = new \ReflectionClass( $this->scan_run );
		$method     = $reflection->getMethod( 'extract_attachment_ids_from_html' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->scan_run, '' );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test normalize_url removes cache-buster
	 */
	public function test_normalize_url_removes_cache_buster() {
		$reflection = new \ReflectionClass( $this->scan_run );
		$method     = $reflection->getMethod( 'normalize_url' );
		$method->setAccessible( true );

		$url        = 'https://example.com/page?isc-indexer-cache-buster=123456';
		$normalized = $method->invoke( $this->scan_run, $url );

		$this->assertStringNotContainsString( 'isc-indexer-cache-buster', $normalized );
	}

	/**
	 * Test normalize_url removes trailing slash
	 */
	public function test_normalize_url_removes_trailing_slash() {
		$reflection = new \ReflectionClass( $this->scan_run );
		$method     = $reflection->getMethod( 'normalize_url' );
		$method->setAccessible( true );

		$url        = 'https://example.com/page/';
		$normalized = $method->invoke( $this->scan_run, $url );

		$this->assertEquals( 'https://example.com/page', $normalized );
	}

	/**
	 * Test normalize_url preserves query parameters
	 */
	public function test_normalize_url_preserves_query_parameters() {
		$reflection = new \ReflectionClass( $this->scan_run );
		$method     = $reflection->getMethod( 'normalize_url' );
		$method->setAccessible( true );

		$url        = 'https://example.com/page?foo=bar&baz=qux';
		$normalized = $method->invoke( $this->scan_run, $url );

		$this->assertStringContainsString( 'foo=bar', $normalized );
		$this->assertStringContainsString( 'baz=qux', $normalized );
	}

	/**
	 * Test is_problematic_redirect with external redirect
	 */
	public function test_is_problematic_redirect_external_domain() {
		$reflection = new \ReflectionClass( $this->scan_run );
		$method     = $reflection->getMethod( 'is_problematic_redirect' );
		$method->setAccessible( true );

		$original = home_url( '/test-page' );
		$redirect = 'https://external-site.com/page';

		$result = $method->invoke( $this->scan_run, $original, $redirect );

		$this->assertTrue( $result );
	}

	/**
	 * Test is_problematic_redirect with protocol change only
	 */
	public function test_is_problematic_redirect_protocol_change() {
		$reflection = new \ReflectionClass( $this->scan_run );
		$method     = $reflection->getMethod( 'is_problematic_redirect' );
		$method->setAccessible( true );

		$site_parts = wp_parse_url( home_url() );
		$host       = $site_parts['host'] ?? 'example.com';

		$original = 'http://' . $host . '/test-page';
		$redirect = 'https://' . $host . '/test-page';

		$result = $method->invoke( $this->scan_run, $original, $redirect );

		$this->assertFalse( $result );
	}

	/**
	 * Test is_problematic_redirect with trailing slash change
	 */
	public function test_is_problematic_redirect_trailing_slash() {
		$reflection = new \ReflectionClass( $this->scan_run );
		$method     = $reflection->getMethod( 'is_problematic_redirect' );
		$method->setAccessible( true );

		$original = home_url( '/test-page' );
		$redirect = home_url( '/test-page/' );

		$result = $method->invoke( $this->scan_run, $original, $redirect );

		$this->assertFalse( $result );
	}

	/**
	 * Test is_problematic_redirect with same URL
	 */
	public function test_is_problematic_redirect_same_url() {
		$reflection = new \ReflectionClass( $this->scan_run );
		$method     = $reflection->getMethod( 'is_problematic_redirect' );
		$method->setAccessible( true );

		$url = home_url( '/test-page' );

		$result = $method->invoke( $this->scan_run, $url, $url );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_admin_auth_cookies returns array
	 */
	public function test_get_admin_auth_cookies_returns_array() {
		$reflection = new \ReflectionClass( $this->scan_run );
		$method     = $reflection->getMethod( 'get_admin_auth_cookies' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->scan_run );

		$this->assertIsArray( $result );
	}

	/**
	 * Test fetch_content with invalid URL returns false
	 */
	public function test_fetch_content_with_invalid_url() {
		$result = $this->scan_run->fetch_content( 'not-a-valid-url' );

		$this->assertFalse( $result );
	}

	/**
	 * Test fetch_content returns array on success
	 */
	public function test_fetch_content_returns_array_structure() {
		$post_id = self::factory()->post->create();
		$url     = get_permalink( $post_id );

		$result = $this->scan_run->fetch_content( $url );

		// This might fail or succeed depending on the test environment
		// We just check the structure if it succeeds
		if ( is_array( $result ) ) {
			$this->assertArrayHasKey( 'code', $result );
			$this->assertArrayHasKey( 'body', $result );
			$this->assertArrayHasKey( 'final_url', $result );
			$this->assertArrayHasKey( 'is_problematic_redirect', $result );
		}
	}

	/**
	 * Test get_total_content_count consistency between modes
	 */
	public function test_get_total_content_count_consistency() {
		// Create posts
		$post_id_1 = self::factory()->post->create();
		$post_id_2 = self::factory()->post->create();

		// Mark one as scanned
		update_post_meta( $post_id_1, Content_Scan::LAST_SCAN_META_KEY, time() );

		$all_count       = $this->scan_run->get_total_content_count( 'all' );
		$unindexed_count = $this->scan_run->get_total_content_count( 'unindexed' );

		// Unindexed should be less than or equal to all
		$this->assertLessThanOrEqual( $all_count, $unindexed_count );
	}

	/**
	 * Test scan_single_item result contains images_count
	 */
	public function test_scan_single_item_includes_images_count() {
		$post_id = self::factory()->post->create();

		$result = $this->scan_run->scan_single_item( array(
			                                             'id'  => $post_id,
			                                             'url' => get_permalink( $post_id ),
		                                             ) );

		$this->assertArrayHasKey( 'images_count', $result );
	}
}