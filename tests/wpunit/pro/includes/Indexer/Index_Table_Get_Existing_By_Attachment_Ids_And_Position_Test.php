<?php

namespace ISC\Tests\WPUnit\Pro\Includes\Indexer;

use ISC\Pro\Indexer\Index_Table;
use ISC\Tests\WPUnit\WPTestCase;

/**
 * Testing the Index_Table::get_existing_by_attachment_ids_and_position() method
 */
class Index_Table_Get_Existing_By_Attachment_Ids_And_Position_Test extends WPTestCase {

	/**
	 * @var Index_Table
	 */
	private Index_Table $index_table;

	/**
	 * @var int
	 */
	private $post_id_1;

	/**
	 * @var int
	 */
	private $post_id_2;

	/**
	 * @var int
	 */
	private $attachment_id_1;

	/**
	 * @var int
	 */
	private $attachment_id_2;

	/**
	 * @var int
	 */
	private $attachment_id_3;

	/**
	 * @var int
	 */
	private $attachment_id_4;

	public function setUp(): void {
		parent::setUp();

		$this->index_table = new Index_Table();

		// Create test posts and attachments
		$this->post_id_1       = $this->factory()->post->create();
		$this->post_id_2       = $this->factory()->post->create();
		$this->attachment_id_1 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->attachment_id_2 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->attachment_id_3 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
		$this->attachment_id_4 = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
	}

	public function tearDown(): void {
		parent::tearDown();

		// Clear the table after each test
		$this->index_table->clear_all();

		// Reset the static cache
		Index_Table::reset_oldest_entry_date_cache();
	}

	/**
	 * Test basic functionality - returns existing attachments with specified position
	 */
	public function test_returns_existing_attachments_with_position() {
		// Insert entries with 'content' position
		$this->index_table->insert_or_update( $this->post_id_1, $this->attachment_id_1, 'content' );
		$this->index_table->insert_or_update( $this->post_id_1, $this->attachment_id_2, 'content' );
		// Insert entry with different position
		$this->index_table->insert_or_update( $this->post_id_1, $this->attachment_id_3, 'thumbnail' );

		$result = $this->index_table->get_existing_by_attachment_ids_and_position(
			[ $this->attachment_id_1, $this->attachment_id_2, $this->attachment_id_3 ],
			'content'
		);

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
		$this->assertContains( $this->attachment_id_1, $result );
		$this->assertContains( $this->attachment_id_2, $result );
		$this->assertNotContains( $this->attachment_id_3, $result, 'Should not include attachment with different position' );
	}

	/**
	 * Test with empty attachment IDs array returns empty array
	 */
	public function test_empty_attachment_ids_returns_empty_array() {
		$result = $this->index_table->get_existing_by_attachment_ids_and_position( [], 'content' );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test with invalid position returns empty array
	 */
	public function test_invalid_position_returns_empty_array() {
		$this->index_table->insert_or_update( $this->post_id_1, $this->attachment_id_1, 'content' );

		$result = $this->index_table->get_existing_by_attachment_ids_and_position(
			[ $this->attachment_id_1 ],
			'invalid_position'
		);

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test filters out invalid attachment IDs (0, negative)
	 */
	public function test_filters_out_invalid_attachment_ids() {
		$this->index_table->insert_or_update( $this->post_id_1, $this->attachment_id_1, 'content' );

		$result = $this->index_table->get_existing_by_attachment_ids_and_position(
			[ $this->attachment_id_1, 0, - 1, - 99 ],
			'content'
		);

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertContains( $this->attachment_id_1, $result );
	}

	/**
	 * Test returns empty array when all attachment IDs are invalid
	 */
	public function test_all_invalid_attachment_ids_returns_empty_array() {
		$result = $this->index_table->get_existing_by_attachment_ids_and_position(
			[ 0, - 1, - 99 ],
			'content'
		);

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test returns empty array when none of the attachments exist
	 */
	public function test_no_matching_attachments_returns_empty_array() {
		$this->index_table->insert_or_update( $this->post_id_1, $this->attachment_id_1, 'content' );

		// Check for attachments that don't exist in the index
		$result = $this->index_table->get_existing_by_attachment_ids_and_position(
			[ $this->attachment_id_2, $this->attachment_id_3 ],
			'content'
		);

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test partial matches - some attachments exist, some don't
	 */
	public function test_returns_partial_matches() {
		$this->index_table->insert_or_update( $this->post_id_1, $this->attachment_id_1, 'content' );
		$this->index_table->insert_or_update( $this->post_id_1, $this->attachment_id_3, 'content' );

		$result = $this->index_table->get_existing_by_attachment_ids_and_position(
			[ $this->attachment_id_1, $this->attachment_id_2, $this->attachment_id_3, $this->attachment_id_4 ],
			'content'
		);

		$this->assertCount( 2, $result );
		$this->assertContains( $this->attachment_id_1, $result );
		$this->assertContains( $this->attachment_id_3, $result );
		$this->assertNotContains( $this->attachment_id_2, $result );
		$this->assertNotContains( $this->attachment_id_4, $result );
	}

	/**
	 * Test that same attachment across multiple posts is only returned once (DISTINCT)
	 */
	public function test_returns_distinct_attachment_ids() {
		// Same attachment used in multiple posts
		$this->index_table->insert_or_update( $this->post_id_1, $this->attachment_id_1, 'content' );
		$this->index_table->insert_or_update( $this->post_id_2, $this->attachment_id_1, 'content' );

		$result = $this->index_table->get_existing_by_attachment_ids_and_position(
			[ $this->attachment_id_1 ],
			'content'
		);

		$this->assertCount( 1, $result, 'Should return distinct attachment IDs only' );
		$this->assertContains( $this->attachment_id_1, $result );
	}

	/**
	 * Test that same attachment with multiple positions only matches the specified position
	 */
	public function test_same_attachment_multiple_positions() {
		// Same attachment with different positions on same post
		$this->index_table->insert_or_update( $this->post_id_1, $this->attachment_id_1, 'content' );
		$this->index_table->insert_or_update( $this->post_id_1, $this->attachment_id_1, 'thumbnail' );

		// Check for 'content' position
		$result_content = $this->index_table->get_existing_by_attachment_ids_and_position(
			[ $this->attachment_id_1 ],
			'content'
		);

		$this->assertCount( 1, $result_content );
		$this->assertContains( $this->attachment_id_1, $result_content );

		// Check for 'thumbnail' position
		$result_thumbnail = $this->index_table->get_existing_by_attachment_ids_and_position(
			[ $this->attachment_id_1 ],
			'thumbnail'
		);

		$this->assertCount( 1, $result_thumbnail );
		$this->assertContains( $this->attachment_id_1, $result_thumbnail );

		// Check for 'head' position (doesn't exist)
		$result_head = $this->index_table->get_existing_by_attachment_ids_and_position(
			[ $this->attachment_id_1 ],
			'head'
		);

		$this->assertEmpty( $result_head );
	}

	/**
	 * Test timestamp filtering - only returns entries updated since timestamp
	 */
	public function test_timestamp_filter_returns_recent_entries_only() {
		$current_time = time();

		// Insert old entry (2 hours ago)
		$old_timestamp = $current_time - 7200;
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_1,
			'content',
			$this->index_table->get_last_checked( $old_timestamp )
		);

		// Insert recent entry (30 minutes ago)
		$recent_timestamp = $current_time - 1800;
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_2,
			'content',
			$this->index_table->get_last_checked( $recent_timestamp )
		);

		// Filter to get only entries updated in the last hour
		$one_hour_ago = $current_time - 3600;
		$result = $this->index_table->get_existing_by_attachment_ids_and_position(
			[ $this->attachment_id_1, $this->attachment_id_2 ],
			'content',
			$one_hour_ago
		);

		$this->assertCount( 1, $result, 'Should only return recent entry' );
		$this->assertContains( $this->attachment_id_2, $result );
		$this->assertNotContains( $this->attachment_id_1, $result, 'Should not include old entry' );
	}

	/**
	 * Test timestamp filtering with zero timestamp includes all entries
	 */
	public function test_zero_timestamp_includes_all_entries() {
		$current_time = time();

		// Insert old entry
		$old_timestamp = $current_time - 7200;
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_1,
			'content',
			$old_timestamp
		);

		// Insert recent entry
		$recent_timestamp = $current_time - 1800;
		$this->index_table->insert_or_update(
			$this->post_id_1,
			$this->attachment_id_2,
			'content',
			$recent_timestamp
		);

		// No timestamp filter (default 0)
		$result = $this->index_table->get_existing_by_attachment_ids_and_position(
			[ $this->attachment_id_1, $this->attachment_id_2 ],
			'content',
			0
		);

		$this->assertCount( 2, $result, 'Should return all entries when timestamp is 0' );
		$this->assertContains( $this->attachment_id_1, $result );
		$this->assertContains( $this->attachment_id_2, $result );
	}

	/**
	 * Test timestamp filtering with negative timestamp is treated as 0
	 */
	public function test_negative_timestamp_includes_all_entries() {
		$this->index_table->insert_or_update( $this->post_id_1, $this->attachment_id_1, 'content' );

		$result = $this->index_table->get_existing_by_attachment_ids_and_position(
			[ $this->attachment_id_1 ],
			'content',
			- 100
		);

		$this->assertCount( 1, $result, 'Negative timestamp should be treated as no filter' );
		$this->assertContains( $this->attachment_id_1, $result );
	}

	/**
	 * Test with large batch of attachment IDs (N+1 query prevention use case)
	 */
	public function test_handles_large_batch_of_attachment_ids() {
		// Create 50 attachments and insert them
		$attachment_ids = [];
		for ( $i = 0; $i < 50; $i ++ ) {
			$attachment_id    = $this->factory()->post->create( [ 'post_type' => 'attachment' ] );
			$attachment_ids[] = $attachment_id;

			// Only insert half of them
			if ( $i < 25 ) {
				$this->index_table->insert_or_update( $this->post_id_1, $attachment_id, 'content' );
			}
		}

		$result = $this->index_table->get_existing_by_attachment_ids_and_position(
			$attachment_ids,
			'content'
		);

		$this->assertCount( 25, $result, 'Should return exactly 25 existing attachments' );
	}

	/**
	 * Test returns array of integers, not strings
	 */
	public function test_returns_array_of_integers() {
		$this->index_table->insert_or_update( $this->post_id_1, $this->attachment_id_1, 'content' );

		$result = $this->index_table->get_existing_by_attachment_ids_and_position(
			[ $this->attachment_id_1 ],
			'content'
		);

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );

		foreach ( $result as $attachment_id ) {
			$this->assertIsInt( $attachment_id, 'All returned IDs should be integers' );
		}
	}
}