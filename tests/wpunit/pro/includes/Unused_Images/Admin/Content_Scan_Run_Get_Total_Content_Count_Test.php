<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Unused_Images\Admin;

use ISC\Pro\Unused_Images\Content_Scan_Run;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Tests for Index_Run::get_total_content_count()
 */
class Content_Scan_Run_Get_Total_Content_Count_Test extends WPTestCase {

	/**
	 * @var Content_Scan_Run
	 */
	protected $content_scan_run;

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->content_scan_run = new Content_Scan_Run();

		// Set a smaller URL batch size for testing purposes
		$reflection = new \ReflectionClass( $this->content_scan_run );
		$property   = $reflection->getProperty( 'url_batch_size' );
		$property->setAccessible( true );
		$property->setValue( $this->content_scan_run, 2 ); // Set batch size to 2 for tests
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
		$this->content_scan_run = null;
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
		$total_count = $this->content_scan_run->get_total_content_count();

		// Assert that the correct number of published public posts are counted
		$this->assertEquals( 3, $total_count );
	}
}

