<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Indexer\Admin;

use ISC\Pro\Indexer\Index_Run;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Tests for Index_Run::get_content_urls_batch()
 */
class Index_Run_Get_Content_Urls_Batch_Test extends WPTestCase {

	/**
	 * @var \ISC\Pro\Indexer\Index_Run
	 */
	protected $indexer_run;

	public function setUp(): void {
		parent::setUp();

		$this->indexer_run = new Index_Run();

		// Set a smaller URL batch size for testing purposes
		$reflection = new \ReflectionClass( $this->indexer_run );
		$property   = $reflection->getProperty( 'url_batch_size' );
		$property->setAccessible( true );
		$property->setValue( $this->indexer_run, 2 ); // Set batch size to 2 for tests
	}

	public function tearDown(): void {
		parent::tearDown();

		unregister_post_type( 'my_cpt' );
		unregister_post_type( 'private_cpt' );

		$this->indexer_run = null;
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
		$offset       = 0;
		$urls_batch_1 = $this->indexer_run->get_content_urls_batch( $offset, $batch_size );

		$this->assertCount( $batch_size, $urls_batch_1, 'First batch count incorrect' );
		$this->assertEquals( [
					                     [ 'id' => $post_ids[0], 'url' => get_permalink( $post_ids[0] ) ],
					                     [ 'id' => $post_ids[1], 'url' => get_permalink( $post_ids[1] ) ],
					             ], $urls_batch_1 );

		// --- Test second batch (offset 2) ---
		$offset       = $batch_size;
		$urls_batch_2 = $this->indexer_run->get_content_urls_batch( $offset, $batch_size );

		$this->assertCount( $batch_size, $urls_batch_2, 'Second batch count incorrect' );
		$this->assertEquals( [
					                     [ 'id' => $post_ids[2], 'url' => get_permalink( $post_ids[2] ) ],
					                     [ 'id' => $post_ids[3], 'url' => get_permalink( $post_ids[3] ) ],
					             ], $urls_batch_2 );

		// --- Test final batch (offset 4) ---
		$offset       = $batch_size * 2;
		$urls_batch_3 = $this->indexer_run->get_content_urls_batch( $offset, $batch_size );

		$this->assertCount( 1, $urls_batch_3, 'Final batch count incorrect' ); // Only 1 post left
		$this->assertEquals( [
					                     [ 'id' => $post_ids[4], 'url' => get_permalink( $post_ids[4] ) ],
					             ], $urls_batch_3 );

		// --- Test offset greater than total ---
		$offset       = $batch_size * 3; // Offset 6
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
		usort( $urls_batch, function ( $a, $b ) {
			return $a['id'] <=> $b['id'];
		} );
		usort( $expected_urls, function ( $a, $b ) {
			return $a['id'] <=> $b['id'];
		} );

		$this->assertEquals( $expected_urls, $urls_batch );
	}
}

