<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Admin;

use ISC\Pro\Unused_Images\Content_Scan\Content_Scan_Run;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Test class for Content_Scan_Run::fetch_content() method
 *
 * This class tests the fetch_content() method which handles HTTP requests
 * to fetch and index content from URLs.
 *
 * @package ISC\Pro\Unused_Images
 */
class Content_Scan_Run_Fetch_Content_Test extends WPTestCase {

	/**
	 * @var Content_Scan_Run
	 */
	protected Content_Scan_Run $content_scan_run;

	/**
	 * @var array Storage for captured HTTP request details
	 */
	protected array $last_request_args = [];

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->content_scan_run       = new Content_Scan_Run();
		$this->last_request_args = [];
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Remove all filters we might have added
		remove_all_filters( 'pre_http_request' );

		// Clean up options
		delete_option( 'isc_options' );

		$this->last_request_args = [];
	}

	/**
	 * Test successful HTTP 200 response when "Any image URL" is enabled (no cache-buster, no cache headers).
	 */
	public function test_returns_successful_response_without_cache_buster(): void {
		// Enable "Any image URL" option
		update_option( 'isc_options', [
			'unused_images' => [
				'index_any_url' => true,
			],
		] );

		$test_url      = 'https://example.com/test-post/';
		$test_body     = '<html><body>Test content</body></html>';
		$captured_args = [];

		// Mock the HTTP request and capture the args
		add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( $test_body, &$captured_args ) {
			$captured_args = $args;

			return [
				'response'      => [ 'code' => 200 ],
				'body'          => $test_body,
				'http_response' => $this->create_mock_http_response( $url, 200 ),
			];
		}, 10, 3 );

		$result = $this->content_scan_run->fetch_content( $test_url );

		$this->assertIsArray( $result );
		$this->assertEquals( 200, $result['code'], 'Expected HTTP status code 200' );
		$this->assertEquals( $test_body, $result['body'], 'Expected response body to match' );
		$this->assertEquals( $test_url, $result['final_url'], 'Expected final URL to match requested URL without cache-buster' );
		$this->assertFalse( $result['is_problematic_redirect'], 'Expected no problematic redirect' );

		// Verify cache control headers are NOT present when "Any image URL" is enabled
		$this->assertArrayHasKey( 'headers', $captured_args );
		$this->assertArrayNotHasKey( 'Cache-Control', $captured_args['headers'], 'Cache-Control header should not be set when parsing frontend HTML' );
		$this->assertArrayNotHasKey( 'Pragma', $captured_args['headers'], 'Pragma header should not be set when parsing frontend HTML' );
		$this->assertArrayNotHasKey( 'Expires', $captured_args['headers'], 'Expires header should not be set when parsing frontend HTML' );
	}

	/**
	 * Test successful HTTP 200 response when "Any image URL" is disabled.
	 * This is the default option, so we are not setting it explicitly.
	 * We expect the cache-buster parameter to be added.
	 */
	public function test_returns_successful_response_with_cache_buster(): void {
		$test_url  = 'https://example.com/test-post/';
		$test_body = '<html><body>Test content</body></html>';

		// Mock the HTTP request
		add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( $test_body ) {
			return [
				'response'      => [ 'code' => 200 ],
				'body'          => $test_body,
				'http_response' => $this->create_mock_http_response( $url, 200 ),
			];
		}, 10, 3 );

		$result = $this->content_scan_run->fetch_content( $test_url );

		$this->assertIsArray( $result );
		$this->assertEquals( 200, $result['code'], 'Expected HTTP status code 200' );
		$this->assertEquals( $test_body, $result['body'], 'Expected response body to match' );

		// The final URL should include the cache-buster parameter
		$this->assertStringStartsWith( $test_url, $result['final_url'], 'Expected final URL to start with requested URL' );
		$this->assertStringContainsString( 'isc-indexer-cache-buster', $result['final_url'], 'Expected cache-buster parameter in final URL' );

		$this->assertFalse( $result['is_problematic_redirect'], 'Expected no problematic redirect' );
	}

	/**
	 * Test 404 response.
	 */
	public function test_returns_404_response(): void {
		$test_url = 'https://example.com/non-existent/';

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) {
			return [
				'response'      => [ 'code' => 404 ],
				'body'          => 'Not found',
				'http_response' => $this->create_mock_http_response( $url, 404 ),
			];
		},          10, 3 );

		$result = $this->content_scan_run->fetch_content( $test_url );

		$this->assertIsArray( $result );
		$this->assertEquals( 404, $result['code'] );
		$this->assertFalse( $result['is_problematic_redirect'] );
	}

	/**
	 * Test 500 server error response.
	 */
	public function test_returns_500_error_response(): void {
		$test_url = 'https://example.com/server-error/';

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) {
			return [
				'response'      => [ 'code' => 500 ],
				'body'          => 'Internal server error',
				'http_response' => $this->create_mock_http_response( $url, 500 ),
			];
		},          10, 3 );

		$result = $this->content_scan_run->fetch_content( $test_url );

		$this->assertIsArray( $result );
		$this->assertEquals( 500, $result['code'] );
	}

	/**
	 * Test WP_Error response returns false.
	 */
	public function test_returns_false_on_wp_error(): void {
		$test_url = 'https://example.com/error/';

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) {
			return new \WP_Error( 'http_request_failed', 'Connection timeout' );
		},          10, 3 );

		$result = $this->content_scan_run->fetch_content( $test_url );

		$this->assertFalse( $result );
	}

	/**
	 * Test HTTP request includes correct headers.
	 */
	public function test_includes_cache_control_headers(): void {
		$test_url      = 'https://example.com/test/';
		$captured_args = [];

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( &$captured_args ) {
			$captured_args = $args;

			return [
				'response'      => [ 'code' => 200 ],
				'body'          => 'test',
				'http_response' => $this->create_mock_http_response( $url, 200 ),
			];
		},          10, 3 );

		$this->content_scan_run->fetch_content( $test_url );

		// Verify cache control headers are present
		$this->assertArrayHasKey( 'headers', $captured_args );
		$this->assertArrayHasKey( 'Cache-Control', $captured_args['headers'] );
		$this->assertEquals( 'no-cache, no-store, must-revalidate', $captured_args['headers']['Cache-Control'] );
		$this->assertArrayHasKey( 'Pragma', $captured_args['headers'] );
		$this->assertEquals( 'no-cache', $captured_args['headers']['Pragma'] );
	}

	/**
	 * Test user agent is set correctly.
	 */
	public function test_sets_correct_user_agent(): void {
		$test_url      = 'https://example.com/test/';
		$captured_args = [];

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( &$captured_args ) {
			$captured_args = $args;

			return [
				'response'      => [ 'code' => 200 ],
				'body'          => 'test',
				'http_response' => $this->create_mock_http_response( $url, 200 ),
			];
		},          10, 3 );

		$this->content_scan_run->fetch_content( $test_url );

		$this->assertArrayHasKey( 'user-agent', $captured_args );
		$this->assertEquals( 'ISC Index Bot', $captured_args['user-agent'] );
	}

	/**
	 * Test timeout is set to 30 seconds.
	 */
	public function test_sets_30_second_timeout(): void {
		$test_url      = 'https://example.com/test/';
		$captured_args = [];

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( &$captured_args ) {
			$captured_args = $args;

			return [
				'response'      => [ 'code' => 200 ],
				'body'          => 'test',
				'http_response' => $this->create_mock_http_response( $url, 200 ),
			];
		},          10, 3 );

		$this->content_scan_run->fetch_content( $test_url );

		$this->assertArrayHasKey( 'timeout', $captured_args );
		$this->assertEquals( 30, $captured_args['timeout'] );
	}

	/**
	 * Test SSL verification is disabled when execute_as_admin is false.
	 */
	public function test_disables_ssl_verification_for_non_admin_requests(): void {
		$test_url      = 'https://example.com/test/';
		$captured_args = [];

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( &$captured_args ) {
			$captured_args = $args;

			return [
				'response'      => [ 'code' => 200 ],
				'body'          => 'test',
				'http_response' => $this->create_mock_http_response( $url, 200 ),
			];
		},          10, 3 );

		$this->content_scan_run->fetch_content( $test_url, false );

		$this->assertArrayHasKey( 'sslverify', $captured_args );
		$this->assertFalse( $captured_args['sslverify'] );
	}

	/**
	 * Test SSL verification is enabled when execute_as_admin is true.
	 */
	public function test_enables_ssl_verification_for_admin_requests(): void {
		$test_url      = home_url( '/test/' ); // Use home_url to match host
		$captured_args = [];

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( &$captured_args ) {
			$captured_args = $args;

			return [
				'response'      => [ 'code' => 200 ],
				'body'          => 'test',
				'http_response' => $this->create_mock_http_response( $url, 200 ),
			];
		},          10, 3 );

		$this->content_scan_run->fetch_content( $test_url, true );

		$this->assertArrayHasKey( 'sslverify', $captured_args );
		$this->assertTrue( $captured_args['sslverify'] );
	}

	/**
	 * Test that admin cookies are NOT sent to foreign hosts.
	 */
	public function test_refuses_admin_cookies_for_foreign_host(): void {
		$foreign_url = 'https://evil.com/test/';

		add_filter( 'pre_http_request', function() {
			$this->fail( 'Should not make HTTP request to foreign host' );
		},          10, 3 );

		$result = $this->content_scan_run->fetch_content( $foreign_url, true );

		$this->assertFalse( $result, 'Should refuse to send admin cookies to foreign host' );
	}

	/**
	 * Test that admin cookies are NOT sent when scheme mismatches.
	 */
	public function test_refuses_admin_cookies_for_scheme_mismatch(): void {
		// If site is http, try to fetch https (or vice versa)
		$site_parts      = wp_parse_url( home_url() );
		$opposite_scheme = ( $site_parts['scheme'] === 'http' ) ? 'https' : 'http';
		$mismatched_url  = $opposite_scheme . '://' . $site_parts['host'] . '/test/';

		add_filter( 'pre_http_request', function() {
			$this->fail( 'Should not make HTTP request with scheme mismatch' );
		},          10, 3 );

		$result = $this->content_scan_run->fetch_content( $mismatched_url, true );

		$this->assertFalse( $result, 'Should refuse to send admin cookies with scheme mismatch' );
	}

	/**
	 * Test redirect to same URL (just trailing slash) is not problematic.
	 */
	public function test_trailing_slash_redirect_not_problematic(): void {
		$original_url = 'https://example.com/test';
		$final_url    = 'https://example.com/test/';

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( $final_url ) {
			return [
				'response'      => [ 'code' => 200 ],
				'body'          => 'test',
				'http_response' => $this->create_mock_http_response( $final_url, 200 ),
			];
		},          10, 3 );

		$result = $this->content_scan_run->fetch_content( $original_url );

		$this->assertFalse( $result['is_problematic_redirect'], 'Trailing slash redirect should not be problematic' );
	}

	/**
	 * Test redirect to external domain is problematic.
	 */
	public function test_external_redirect_is_problematic(): void {
		$original_url = home_url( '/test/' );
		$external_url = 'https://external-site.com/different/';

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( $external_url ) {
			return [
				'response'      => [ 'code' => 200 ],
				'body'          => 'test',
				'http_response' => $this->create_mock_http_response( $external_url, 200 ),
			];
		},          10, 3 );

		$result = $this->content_scan_run->fetch_content( $original_url );

		$this->assertTrue( $result['is_problematic_redirect'], 'External redirect should be problematic' );
	}

	/**
	 * Test http to https redirect on same domain is not problematic.
	 */
	public function test_http_to_https_redirect_not_problematic(): void {
		// Mock home_url to return http version
		add_filter( 'home_url', function() {
			return 'http://example.com';
		} );

		$original_url = 'http://example.com/test/';  // Hardcode it
		$https_url    = 'https://example.com/test/';

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( $https_url ) {
			return [
				'response'      => [ 'code' => 200 ],
				'body'          => 'test',
				'http_response' => $this->create_mock_http_response( $https_url, 200 ),
			];
		}, 10, 3 );

		$result = $this->content_scan_run->fetch_content( $original_url );

		$this->assertFalse( $result['is_problematic_redirect'], 'HTTP to HTTPS redirect should not be problematic' );

		// Cleanup
		remove_all_filters( 'home_url' );
	}

	/**
	 * Test invalid URL returns false.
	 */
	public function test_invalid_url_returns_false(): void {
		$invalid_url = 'not-a-valid-url';

		$result = $this->content_scan_run->fetch_content( $invalid_url );

		$this->assertFalse( $result );
	}

	/**
	 * Test redirection limit is set to MAX_REDIRECTS (5).
	 */
	public function test_sets_max_redirects_to_five(): void {
		$test_url      = 'https://example.com/test/';
		$captured_args = [];

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( &$captured_args ) {
			$captured_args = $args;

			return [
				'response'      => [ 'code' => 200 ],
				'body'          => 'test',
				'http_response' => $this->create_mock_http_response( $url, 200 ),
			];
		},          10, 3 );

		$this->content_scan_run->fetch_content( $test_url );

		$this->assertArrayHasKey( 'redirection', $captured_args );
		$this->assertEquals( 5, $captured_args['redirection'] );
	}

	/**
	 * Test redirect with different post IDs is problematic.
	 */
	public function test_redirect_to_different_post_is_problematic(): void {
		// Create two posts
		$post1_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$post2_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$original_url = get_permalink( $post1_id );
		$redirect_url = get_permalink( $post2_id );

		// Use reflection to test normalize_url
		$reflection = new \ReflectionClass( $this->content_scan_run );
		$normalize_method = $reflection->getMethod( 'normalize_url' );
		$normalize_method->setAccessible( true );

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( $redirect_url ) {
			return [
				'response'      => [ 'code' => 200 ],
				'body'          => 'test',
				'http_response' => $this->create_mock_http_response( $redirect_url, 200 ),
			];
		}, 10, 3 );

		$result = $this->content_scan_run->fetch_content( $original_url );

		$this->assertTrue( $result['is_problematic_redirect'], 'Redirect to different post should be problematic' );
	}

	/**
	 * Test redirect to same post ID is not problematic.
	 */
	public function test_redirect_to_same_post_not_problematic(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$original_url = get_permalink( $post_id );
		// Simulate a redirect to the same post but different URL format
		$redirect_url = add_query_arg( 'foo', 'bar', $original_url );

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( $redirect_url ) {
			return [
				'response'      => [ 'code' => 200 ],
				'body'          => 'test',
				'http_response' => $this->create_mock_http_response( $redirect_url, 200 ),
			];
		}, 10, 3 );

		$result = $this->content_scan_run->fetch_content( $original_url );

		$this->assertFalse( $result['is_problematic_redirect'], 'Redirect to same post should not be problematic' );
	}

	/**
	 * Test redirect detection works with query-based permalinks.
	 * Regression test for issue #310.
	 */
	public function test_detects_redirect_between_query_based_permalinks(): void {
		// Simulate WordPress without pretty permalinks (using ?p=ID)
		$original_url = 'http://example.com/?p=4';
		$redirect_url = 'http://example.com/?p=5';

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( $redirect_url ) {
			return [
				'response'      => [ 'code' => 200 ],
				'body'          => 'test',
				'http_response' => $this->create_mock_http_response( $redirect_url, 200 ),
			];
		}, 10, 3 );

		$result = $this->content_scan_run->fetch_content( $original_url );

		// Before fix: is_problematic_redirect was empty/false
		// After fix: should be true (different posts)
		$this->assertTrue(
			$result['is_problematic_redirect'],
			'Redirect from ?p=4 to ?p=5 should be detected as problematic (regression test for #310)'
		);
	}

	/**
	 * Test URL normalization handles cache-buster removal in final URL comparison.
	 */
	public function test_cache_buster_removed_for_redirect_comparison(): void {
		$original_url = 'https://example.com/test/';
		$redirect_url_with_cache_buster = 'https://example.com/test/?isc-indexer-cache-buster=123456';

		add_filter( 'pre_http_request', function( $preempt, $args, $url ) use ( $redirect_url_with_cache_buster ) {
			return [
				'response'      => [ 'code' => 200 ],
				'body'          => 'test',
				'http_response' => $this->create_mock_http_response( $redirect_url_with_cache_buster, 200 ),
			];
		}, 10, 3 );

		$result = $this->content_scan_run->fetch_content( $original_url );

		// Should not be flagged as problematic since it's the same URL after normalization
		$this->assertFalse( $result['is_problematic_redirect'], 'Cache-buster should be normalized away' );
	}

	/**
	 * Helper method to create a mock HTTP response object.
	 *
	 * @param string $final_url   The final URL after redirects.
	 * @param int    $status_code The HTTP status code.
	 *
	 * @return object Mock HTTP response object.
	 */
	private function create_mock_http_response( string $final_url, int $status_code ): object {
		// Create a mock response object that mimics WordPress HTTP response structure
		$mock_response_object              = new \stdClass();
		$mock_response_object->url         = $final_url;
		$mock_response_object->status_code = $status_code;

		$mock_http_response = $this->createMock( \WP_HTTP_Requests_Response::class );
		$mock_http_response->method( 'get_response_object' )->willReturn( $mock_response_object );

		return $mock_http_response;
	}
}