<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Admin;

use ISC\Pro\Unused_Images\Content_Scan\Content_Scan_Run;
use ISC\Tests\WPUnit\WPTestCase;
use ReflectionClass;

/**
 * Test class for Index_Run::normalize_url() method
 *
 * This class tests the normalize_url() private method which is responsible for:
 * - Removing cache-buster parameters
 * - Preserving other query parameters
 * - Normalizing trailing slashes
 * - Handling edge cases in URL structure
 *
 * Related issue: https://github.com/image-source-control/image-source-control-pro/issues/310
 *
 * @package ISC\Pro\Indexer
 */
class Content_Scan_Run_Normalize_Url_Test extends WPTestCase {

	/**
	 * @var Content_Scan_Run
	 */
	protected Content_Scan_Run $content_scan_run;

	/**
	 * @var \ReflectionMethod
	 */
	protected \ReflectionMethod $normalize_method;

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->content_scan_run = new Content_Scan_Run();

		// Use reflection to access private normalize_url method
		$reflection             = new ReflectionClass( $this->content_scan_run );
		$this->normalize_method = $reflection->getMethod( 'normalize_url' );
		$this->normalize_method->setAccessible( true );
	}

	/**
	 * Helper method to call normalize_url via reflection.
	 *
	 * @param string $url URL to normalize.
	 *
	 * @return string Normalized URL.
	 */
	private function normalize( string $url ): string {
		return $this->normalize_method->invoke( $this->content_scan_run, $url );
	}

	/**
	 * Test that cache-buster parameter is removed.
	 */
	public function test_removes_cache_buster_parameter(): void {
		$url      = 'http://example.com/test/?isc-indexer-cache-buster=123456';
		$expected = 'http://example.com/test';

		$this->assertEquals( $expected, $this->normalize( $url ) );
	}

	/**
	 * Test that cache-buster is removed while preserving other query parameters.
	 */
	public function test_removes_cache_buster_but_preserves_other_params(): void {
		$url      = 'http://example.com/test/?foo=bar&isc-indexer-cache-buster=123456&baz=qux';
		$expected = 'http://example.com/test?foo=bar&baz=qux';

		$this->assertEquals( $expected, $this->normalize( $url ) );
	}

	/**
	 * Test that query-based permalinks are preserved.
	 * Related to issue #310.
	 */
	public function test_preserves_query_based_permalink(): void {
		$url      = 'http://example.com/?p=5';
		$expected = 'http://example.com?p=5';

		$this->assertEquals( $expected, $this->normalize( $url ) );
	}

	/**
	 * Test that query-based permalinks with cache-buster work correctly.
	 * Related to issue #310.
	 */
	public function test_preserves_query_permalink_removes_cache_buster(): void {
		$url      = 'http://example.com/?p=5&isc-indexer-cache-buster=123456';
		$expected = 'http://example.com?p=5';

		$this->assertEquals( $expected, $this->normalize( $url ) );
	}

	/**
	 * Test that trailing slash is removed from path.
	 */
	public function test_removes_trailing_slash(): void {
		$url      = 'http://example.com/test/';
		$expected = 'http://example.com/test';

		$this->assertEquals( $expected, $this->normalize( $url ) );
	}

	/**
	 * Test that trailing slash is removed even with query parameters.
	 */
	public function test_removes_trailing_slash_with_query_params(): void {
		$url      = 'http://example.com/test/?foo=bar';
		$expected = 'http://example.com/test?foo=bar';

		$this->assertEquals( $expected, $this->normalize( $url ) );
	}

	/**
	 * Test URL without path.
	 */
	public function test_normalizes_root_url(): void {
		$url      = 'http://example.com/';
		$expected = 'http://example.com';

		$this->assertEquals( $expected, $this->normalize( $url ) );
	}

	/**
	 * Test URL with port number.
	 */
	public function test_preserves_port_number(): void {
		$url      = 'http://example.com:8080/test/';
		$expected = 'http://example.com:8080/test';

		$this->assertEquals( $expected, $this->normalize( $url ) );
	}

	/**
	 * Test HTTPS URLs.
	 */
	public function test_preserves_https_scheme(): void {
		$url      = 'https://example.com/test/';
		$expected = 'https://example.com/test';

		$this->assertEquals( $expected, $this->normalize( $url ) );
	}

	/**
	 * Test multiple query parameters.
	 */
	public function test_preserves_multiple_query_parameters(): void {
		$url      = 'http://example.com/test/?foo=bar&baz=qux&hello=world';
		$expected = 'http://example.com/test?foo=bar&baz=qux&hello=world';

		$this->assertEquals( $expected, $this->normalize( $url ) );
	}

	/**
	 * Test URL with fragment (should be removed for comparison).
	 */
	public function test_removes_fragment(): void {
		$url      = 'http://example.com/test/#section';
		$expected = 'http://example.com/test';

		$this->assertEquals( $expected, $this->normalize( $url ) );
	}

	/**
	 * Test URL with fragment and query parameters.
	 */
	public function test_preserves_query_removes_fragment(): void {
		$url      = 'http://example.com/test/?foo=bar#section';
		$expected = 'http://example.com/test?foo=bar';

		$this->assertEquals( $expected, $this->normalize( $url ) );
	}

	/**
	 * Test that two URLs differing only by trailing slash normalize to same value.
	 */
	public function test_normalizes_trailing_slash_differences(): void {
		$url1 = 'http://example.com/test';
		$url2 = 'http://example.com/test/';

		$this->assertEquals( $this->normalize( $url1 ), $this->normalize( $url2 ) );
	}

	/**
	 * Test that two URLs differing only by cache-buster normalize to same value.
	 */
	public function test_normalizes_cache_buster_differences(): void {
		$url1 = 'http://example.com/test/?isc-indexer-cache-buster=111';
		$url2 = 'http://example.com/test/?isc-indexer-cache-buster=222';

		$this->assertEquals( $this->normalize( $url1 ), $this->normalize( $url2 ) );
	}

	/**
	 * Test that URLs with different query params (non-cache-buster) remain different.
	 * Related to issue #310.
	 */
	public function test_different_query_params_remain_different(): void {
		$url1 = 'http://example.com/?p=4';
		$url2 = 'http://example.com/?p=5';

		$this->assertNotEquals( $this->normalize( $url1 ), $this->normalize( $url2 ) );
	}

	/**
	 * Test URL-encoded query parameters are preserved.
	 */
	public function test_preserves_encoded_query_parameters(): void {
		$url = 'http://example.com/test/?name=John%20Doe&email=test%40example.com';
		$result = $this->normalize( $url );

		// WordPress converts %20 to + (both are valid URL encodings for spaces)
		$this->assertStringContainsString( 'name=John', $result );
		$this->assertStringContainsString( 'email=test%40example.com', $result );
		$this->assertStringStartsWith( 'http://example.com/test?', $result );
	}

	/**
	 * Test invalid URL returns as-is.
	 */
	public function test_returns_invalid_url_unchanged(): void {
		$url = 'not-a-valid-url';

		$this->assertEquals( $url, $this->normalize( $url ) );
	}

	/**
	 * Test empty query string (just "?").
	 */
	public function test_handles_empty_query_string(): void {
		$url      = 'http://example.com/test/?';
		$expected = 'http://example.com/test';

		$this->assertEquals( $expected, $this->normalize( $url ) );
	}

	/**
	 * Test subdomain URLs.
	 */
	public function test_preserves_subdomain(): void {
		$url      = 'http://blog.example.com/test/?foo=bar';
		$expected = 'http://blog.example.com/test?foo=bar';

		$this->assertEquals( $expected, $this->normalize( $url ) );
	}

	/**
	 * Test deep path URLs.
	 */
	public function test_handles_deep_paths(): void {
		$url      = 'http://example.com/category/subcategory/post-name/?foo=bar';
		$expected = 'http://example.com/category/subcategory/post-name?foo=bar';

		$this->assertEquals( $expected, $this->normalize( $url ) );
	}
}