<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Admin;

use ISC\Pro\Indexer\Index_Run;
use ISC\Pro\Unused_Images\Content_Scan_Run;
use ISC\Tests\WPUnit\WPTestCase;
use ReflectionClass;

/**
 * Test class for Index_Run::is_problematic_redirect() method
 *
 * This class tests the is_problematic_redirect() private method which determines
 * whether a redirect should be flagged as problematic for indexing purposes.
 *
 * Acceptable redirects (not problematic):
 * - Protocol changes (http to https)
 * - Trailing slash additions/removals
 * - Same post ID destination
 *
 * Problematic redirects:
 * - External URLs (different domain)
 * - Different post IDs
 *
 * Related issue: https://github.com/image-source-control/image-source-control-pro/issues/312
 *
 * @package ISC\Pro\Indexer
 */
class Content_Scan_Run_Is_Problematic_Redirect_Test extends WPTestCase {

	/**
	 * @var Content_Scan_Run
	 */
	protected Content_Scan_Run $content_scan_run;

	/**
	 * @var \ReflectionMethod
	 */
	protected \ReflectionMethod $is_problematic_redirect_method;

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->content_scan_run = new Content_Scan_Run();

		// Use reflection to access private is_problematic_redirect method
		$reflection                           = new ReflectionClass( $this->content_scan_run );
		$this->is_problematic_redirect_method = $reflection->getMethod( 'is_problematic_redirect' );
		$this->is_problematic_redirect_method->setAccessible( true );
	}

	/**
	 * Helper method to call is_problematic_redirect via reflection.
	 *
	 * @param string $original_url Original URL.
	 * @param string $redirect_url Redirect URL.
	 *
	 * @return bool Whether the redirect is problematic.
	 */
	private function is_problematic_redirect( string $original_url, string $redirect_url ): bool {
		return $this->is_problematic_redirect_method->invoke( $this->content_scan_run, $original_url, $redirect_url );
	}

	/**
	 * Test redirect to external domain is problematic.
	 */
	public function test_external_domain_redirect_is_problematic(): void {
		$original_url = home_url( '/test/' );
		$redirect_url = 'https://external-site.com/test/';

		$this->assertTrue(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect to external domain should be problematic'
		);
	}

	/**
	 * Test redirect from http to https on same domain is not problematic.
	 */
	public function test_http_to_https_redirect_not_problematic(): void {
		// Mock home_url to return http
		add_filter( 'home_url', function() {
			return 'http://example.com';
		} );

		$original_url = 'http://example.com/test/';
		$redirect_url = 'https://example.com/test/';

		$this->assertFalse(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'HTTP to HTTPS redirect on same domain should not be problematic'
		);

		remove_all_filters( 'home_url' );
	}

	/**
	 * Test trailing slash addition is not problematic.
	 */
	public function test_trailing_slash_addition_not_problematic(): void {
		$original_url = home_url( '/test' );
		$redirect_url = home_url( '/test/' );

		$this->assertFalse(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Trailing slash addition should not be problematic'
		);
	}

	/**
	 * Test trailing slash removal is not problematic.
	 */
	public function test_trailing_slash_removal_not_problematic(): void {
		$original_url = home_url( '/test/' );
		$redirect_url = home_url( '/test' );

		$this->assertFalse(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Trailing slash removal should not be problematic'
		);
	}

	/**
	 * Test redirect to different path on same domain is problematic.
	 */
	public function test_different_path_redirect_is_problematic(): void {
		$original_url = home_url( '/test-one/' );
		$redirect_url = home_url( '/test-two/' );

		$this->assertTrue(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect to different path should be problematic'
		);
	}

	/**
	 * Test redirect between different query-based permalinks is problematic.
	 * Regression test for issue #312.
	 */
	public function test_different_query_based_permalinks_is_problematic(): void {
		// Create two posts
		$post1_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$post2_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$original_url = '/?p=' . $post1_id;
		$redirect_url = '/?p=' . $post2_id;

		$this->assertTrue(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect from ?p=4 to ?p=5 should be problematic (regression test for #312)'
		);
	}

	/**
	 * Test redirect to same post with different URL format is not problematic.
	 */
	public function test_same_post_different_url_format_not_problematic(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$original_url = '/?p=' . $post_id;
		$redirect_url = get_permalink( $post_id );

		$this->assertFalse(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect to same post with different URL format should not be problematic'
		);
	}

	/**
	 * Test redirect to same post ID with trailing slash is not problematic.
	 */
	public function test_same_post_with_trailing_slash_not_problematic(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$permalink    = get_permalink( $post_id );
		$original_url = rtrim( $permalink, '/' );
		$redirect_url = $permalink;

		$this->assertFalse(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect to same post with trailing slash should not be problematic'
		);
	}

	/**
	 * Test redirect between different posts with pretty permalinks is problematic.
	 */
	public function test_different_posts_pretty_permalinks_is_problematic(): void {
		$post1_id = $this->factory()->post->create( [
			                                            'post_status' => 'publish',
			                                            'post_name'   => 'test-post-one',
		                                            ] );
		$post2_id = $this->factory()->post->create( [
			                                            'post_status' => 'publish',
			                                            'post_name'   => 'test-post-two',
		                                            ] );

		$original_url = get_permalink( $post1_id );
		$redirect_url = get_permalink( $post2_id );

		$this->assertTrue(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect between different posts should be problematic'
		);
	}

	/**
	 * Test invalid original URL returns true (problematic).
	 */
	public function test_invalid_original_url_is_problematic(): void {
		$original_url = 'not-a-valid-url';
		$redirect_url = home_url( '/test/' );

		$this->assertTrue(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Invalid original URL should be treated as problematic'
		);
	}

	/**
	 * Test invalid redirect URL returns true (problematic).
	 */
	public function test_invalid_redirect_url_is_problematic(): void {
		$original_url = home_url( '/test/' );
		$redirect_url = 'not-a-valid-url';

		$this->assertTrue(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Invalid redirect URL should be treated as problematic'
		);
	}

	/**
	 * Test redirect to subdomain is problematic.
	 */
	public function test_subdomain_redirect_is_problematic(): void {
		$original_url = 'http://example.com/test/';
		$redirect_url = 'http://blog.example.com/test/';

		// Mock home_url
		add_filter( 'home_url', function() {
			return 'http://example.com';
		} );

		$this->assertTrue(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect to subdomain should be problematic'
		);

		remove_all_filters( 'home_url' );
	}

	/**
	 * Test redirect from subdomain back to main domain is problematic.
	 */
	public function test_subdomain_to_main_domain_is_problematic(): void {
		$original_url = home_url( '/test/' );  // Same as site
		$redirect_url = 'http://blog.example.com/test/';  // Different subdomain

		$this->assertTrue(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect to different subdomain should be problematic'
		);
	}

	/**
	 * Test redirect with port number change is problematic.
	 */
	public function test_port_change_is_problematic(): void {
		$original_url = 'http://example.com:8080/test/';
		$redirect_url = 'http://example.com:8081/test/';

		// Mock home_url
		add_filter( 'home_url', function() {
			return 'http://example.com:8080';
		} );

		$this->assertTrue(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect with port change should be problematic'
		);

		remove_all_filters( 'home_url' );
	}

	/**
	 * Test redirect with same port is not problematic (if same post).
	 */
	public function test_same_port_same_post_not_problematic(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		// Mock home_url with port
		add_filter( 'home_url', function() {
			return 'http://example.com:8080';
		} );

		$original_url = 'http://example.com:8080/?p=' . $post_id;
		$redirect_url = 'http://example.com:8080/?p=' . $post_id . '&foo=bar';

		$this->assertFalse(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect with same port and same post should not be problematic'
		);

		remove_all_filters( 'home_url' );
	}

	/**
	 * Test redirect to non-existent post is problematic.
	 */
	public function test_redirect_to_non_existent_post_is_problematic(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$original_url = get_permalink( $post_id );
		$redirect_url = home_url( '/non-existent-page/' );

		$this->assertTrue(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect to non-existent post should be problematic'
		);
	}

	/**
	 * Test redirect from non-existent to existing post is problematic.
	 */
	public function test_redirect_from_non_existent_post_is_problematic(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$original_url = home_url( '/non-existent-page/' );
		$redirect_url = get_permalink( $post_id );

		$this->assertTrue(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect from non-existent post should be problematic'
		);
	}

	/**
	 * Test redirect with query parameters to same post is not problematic.
	 */
	public function test_same_post_with_query_params_not_problematic(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$original_url = get_permalink( $post_id );
		$redirect_url = add_query_arg( 'utm_source', 'test', $original_url );

		$this->assertFalse(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect to same post with added query params should not be problematic'
		);
	}

	/**
	 * Test redirect with anchor/fragment is handled correctly.
	 */
	public function test_redirect_with_fragment_same_post_not_problematic(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$original_url = get_permalink( $post_id );
		$redirect_url = $original_url . '#section';

		$this->assertFalse(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect with fragment to same post should not be problematic'
		);
	}

	/**
	 * Test redirect to homepage is problematic (unless original was homepage).
	 */
	public function test_redirect_to_homepage_from_post_is_problematic(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$original_url = get_permalink( $post_id );
		$redirect_url = home_url( '/' );

		$this->assertTrue(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect from post to homepage should be problematic'
		);
	}

	/**
	 * Test redirect with URL-encoded characters.
	 */
	public function test_redirect_with_encoded_characters(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$original_url = get_permalink( $post_id );
		$redirect_url = add_query_arg( 'name', 'John Doe', $original_url );

		// Should not be problematic if it's the same post
		$this->assertFalse(
			$this->is_problematic_redirect( $original_url, $redirect_url ),
			'Redirect with URL-encoded params to same post should not be problematic'
		);
	}
}